<?php

namespace App\Services;

use App\Models\Reschedule;
use App\Models\TherapySession;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class AttendanceService
{
    public function getTodaySessions(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = TherapySession::with([
            'schedule.child.family.guardians',
            'schedule.therapist',
            'substituteTherapist',
            'rescheduleOriginal.newSession',
        ]);

        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $query->whereBetween('session_date', [$filters['date_from'], $filters['date_to']]);
        } elseif (!empty($filters['date_from'])) {
            $query->whereDate('session_date', '>=', $filters['date_from']);
        } elseif (!empty($filters['date_to'])) {
            $query->whereDate('session_date', '<=', $filters['date_to']);
        } else {
            $date = !empty($filters['date']) ? $filters['date'] : null;
            if ($date) {
                $query->whereDate('session_date', $date);
            }
        }

        if (!empty($filters['child_id'])) {
            $childId = $filters['child_id'];
            $query->whereHas('schedule', function ($q) use ($childId) {
                $q->where('child_id', $childId);
            });
        }

        if (!empty($filters['therapist_id'])) {
            $therapistId = $filters['therapist_id'];
            $query->where(function ($q) use ($therapistId) {
                $q->where('therapist_id', $therapistId)
                  ->orWhere(function ($sq) use ($therapistId) {
                      $sq->whereNull('therapist_id')
                         ->whereHas('schedule', function ($scq) use ($therapistId) {
                             $scq->where('therapist_id', $therapistId);
                         });
                  });
            });
        }

        if (!empty($filters['therapy_type'])) {
            $type = $filters['therapy_type'];
            $query->whereHas('schedule', function ($q) use ($type) {
                $q->where('therapy_type', $type);
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('schedule', function ($q) use ($search) {
                $q->whereHas('child', function ($cq) use ($search) {
                    $cq->where('child_name', 'like', "%{$search}%")
                       ->orWhere('medical_record_number', 'like', "%{$search}%");
                })->orWhereHas('child.family.guardians', function ($gq) use ($search) {
                    $gq->where('guardian_name', 'like', "%{$search}%");
                });
            });
        }

        return $query->orderBy('created_at', 'asc')->paginate($perPage);
    }

    public function getRescheduledSessions(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = Reschedule::with([
            'originalSession.schedule.child',
            'originalSession.substituteTherapist',
            'originalSession.schedule.therapist',
            'newSession.substituteTherapist',
            'newSession.schedule.therapist',
        ]);

        if (!empty($filters['date'])) {
            $date = $filters['date'];
            $query->where(function ($q) use ($date) {
                $q->whereHas('originalSession', function ($sq) use ($date) {
                    $sq->whereDate('session_date', $date);
                })->orWhereHas('newSession', function ($sq) use ($date) {
                    $sq->whereDate('session_date', $date);
                });
            });
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('originalSession.schedule.child', function ($cq) use ($search) {
                $cq->where('child_name', 'like', "%{$search}%")
                   ->orWhere('medical_record_number', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function markAttendance(TherapySession $session, array $data): TherapySession
    {
        $session->status = $data['status'];
        if (array_key_exists('notes', $data)) {
            $session->notes = $data['notes'];
        }

        if ($data['status'] === 'present') {
            if (!$session->checked_in_at) {
                $session->checked_in_at = Carbon::now();
            }
        } else {
            $session->checked_in_at = null;
        }

        $session->save();

        return $session->load([
            'schedule.child.family.guardians',
            'schedule.therapist',
            'substituteTherapist',
            'rescheduleOriginal.newSession',
        ]);
    }

    public function updateAttendance(TherapySession $session, array $data): TherapySession
    {
        return $this->markAttendance($session, $data);
    }

    public function generateQrToken(TherapySession $session): string
    {
        if (empty($session->attendance_token)) {
            $tokenRaw = $session->id . '_' . Str::random(16) . '_' . time();
            $session->attendance_token = hash_hmac('sha256', $tokenRaw, config('app.key'));
            $session->save();
        }

        return $session->attendance_token;
    }

    public function processQrScan(string $token): TherapySession
    {
        $session = TherapySession::with([
            'schedule.child.family.guardians',
            'schedule.therapist',
            'substituteTherapist',
        ])->where('attendance_token', $token)->first();

        if (!$session) {
            throw new \Exception('QR Code tidak valid atau tidak ditemukan.');
        }

        $childName = $session->schedule?->child?->child_name ?? 'Pasien';

        if ($session->status === 'present' && $session->checked_in_at) {
            $timeStr = $session->checked_in_at->format('H:i');
            throw new \Exception("Pasien {$childName} sudah melakukan presensi pada jam {$timeStr} WIB.");
        }

        $todayStr = Carbon::today()->toDateString();
        $sessionDateStr = $session->session_date ? $session->session_date->format('Y-m-d') : '';

        if ($sessionDateStr !== $todayStr) {
            $formattedDate = $session->session_date ? $session->session_date->format('d/m/Y') : '-';
            throw new \Exception("QR Code ini untuk sesi tanggal {$formattedDate}, bukan hari ini.");
        }

        $session->status = 'present';
        $session->checked_in_at = Carbon::now();
        $session->save();

        return $session;
    }
}
