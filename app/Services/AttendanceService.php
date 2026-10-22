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

        // Filter Child (single or multiple)
        $childIds = [];
        if (!empty($filters['child_ids']) && is_array($filters['child_ids'])) {
            $childIds = array_filter($filters['child_ids']);
        } elseif (!empty($filters['child_id'])) {
            $childIds = is_array($filters['child_id']) ? $filters['child_id'] : [$filters['child_id']];
        }
        if (!empty($childIds)) {
            $query->whereHas('schedule', function ($q) use ($childIds) {
                $q->whereIn('child_id', $childIds);
            });
        }

        // Filter Therapist (single or multiple)
        $therapistIds = [];
        if (!empty($filters['therapist_ids']) && is_array($filters['therapist_ids'])) {
            $therapistIds = array_filter($filters['therapist_ids']);
        } elseif (!empty($filters['therapist_id'])) {
            $therapistIds = is_array($filters['therapist_id']) ? $filters['therapist_id'] : [$filters['therapist_id']];
        }
        if (!empty($therapistIds)) {
            $query->where(function ($q) use ($therapistIds) {
                $q->whereIn('therapist_id', $therapistIds)
                  ->orWhere(function ($sq) use ($therapistIds) {
                      $sq->whereNull('therapist_id')
                         ->whereHas('schedule', function ($scq) use ($therapistIds) {
                             $scq->whereIn('therapist_id', $therapistIds);
                         });
                  });
            });
        }

        // Filter Therapy Type (single or multiple)
        $therapyTypes = [];
        if (!empty($filters['therapy_types']) && is_array($filters['therapy_types'])) {
            $therapyTypes = array_filter($filters['therapy_types']);
        } elseif (!empty($filters['therapy_type_ids']) && is_array($filters['therapy_type_ids'])) {
            $therapyTypes = array_filter($filters['therapy_type_ids']);
        } elseif (!empty($filters['therapy_type'])) {
            $therapyTypes = is_array($filters['therapy_type']) ? $filters['therapy_type'] : [$filters['therapy_type']];
        }
        if (!empty($therapyTypes)) {
            $query->whereHas('schedule', function ($q) use ($therapyTypes) {
                $q->whereIn('therapy_type', $therapyTypes);
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

        return $query->select('therapy_sessions.*')
            ->leftJoin('schedules', 'therapy_sessions.schedule_id', '=', 'schedules.id')
            ->orderByRaw('COALESCE(therapy_sessions.start_time, schedules.start_time) ASC')
            ->orderBy('therapy_sessions.session_date', 'asc')
            ->paginate($perPage);
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
        // 1. Cek apakah token cocok dengan anak (Permanent Child QR: medical_record_number, child_id, atau permanent hash)
        $child = \App\Models\Child::query()
            ->where('medical_record_number', $token)
            ->orWhere('id', $token)
            ->first();

        if (!$child && Str::startsWith($token, 'CHILD_')) {
            $rawChildId = Str::after($token, 'CHILD_');
            $child = \App\Models\Child::find($rawChildId);
        }

        if ($child) {
            $childName = $child->child_name;
            $today = Carbon::today()->toDateString();
            $nowTime = Carbon::now()->format('H:i');

            // Cari sesi hari ini milik anak tersebut
            $sessionsToday = TherapySession::with([
                'schedule.child.family.guardians',
                'schedule.therapist',
                'substituteTherapist',
            ])
            ->whereHas('schedule', function ($q) use ($child) {
                $q->where('child_id', $child->id);
            })
            ->whereDate('session_date', $today)
            ->where('status', '!=', 'cancelled')
            ->get();

            if ($sessionsToday->isEmpty()) {
                throw new \Exception("Pasien {$childName} tidak memiliki jadwal sesi terapi aktif hari ini.");
            }

            // Cari sesi yang pending dan cocok dengan rentang jam scan sekarang
            $targetSession = null;

            // Prioritas 1: Sesi pending yang saat ini sedang berada di dalam rentang jamnya
            foreach ($sessionsToday as $s) {
                if ($s->status === 'pending') {
                    $st = $s->start_time ?: $s->schedule?->start_time;
                    $et = $s->end_time ?: $s->schedule?->end_time;
                    if ($st && $et) {
                        $stShort = substr($st, 0, 5);
                        $etShort = substr($et, 0, 5);
                        if ($nowTime >= $stShort && $nowTime <= $etShort) {
                            $targetSession = $s;
                            break;
                        }
                    }
                }
            }

            // Prioritas 2: Jika belum tepat di jamnya atau tidak ada start_time, ambil sesi pending pertama hari ini
            if (!$targetSession) {
                $targetSession = $sessionsToday->firstWhere('status', 'pending');
            }

            // Jika semua sesi sudah hadir / bukan pending
            if (!$targetSession) {
                $alreadyPresent = $sessionsToday->firstWhere('status', 'present');
                if ($alreadyPresent && $alreadyPresent->checked_in_at) {
                    $timeStr = $alreadyPresent->checked_in_at->format('H:i');
                    throw new \Exception("Pasien {$childName} sudah melakukan presensi hari ini pada jam {$timeStr} WIB.");
                }
                throw new \Exception("Tidak ada sesi terapi anak {$childName} yang dapat dipresensi hari ini.");
            }

            // Validasi jam sesi jika ada batasan waktu
            $sStartTime = $targetSession->start_time ?: $targetSession->schedule?->start_time;
            $sEndTime = $targetSession->end_time ?: $targetSession->schedule?->end_time;
            if ($sStartTime && $sEndTime) {
                $stShort = substr($sStartTime, 0, 5);
                $etShort = substr($sEndTime, 0, 5);
                if ($nowTime < $stShort || $nowTime > $etShort) {
                    throw new \Exception("Presensi hadir untuk {$childName} hanya valid dicatat di rentang jam sesi berlangsung ({$stShort} - {$etShort}). Jam saat ini: {$nowTime} WIB.");
                }
            }

            $targetSession->status = 'present';
            $targetSession->checked_in_at = Carbon::now();
            $targetSession->save();

            return $targetSession;
        }

        // 2. Fallback: Cocokkan sebagai session attendance_token individual
        $session = TherapySession::with([
            'schedule.child.family.guardians',
            'schedule.therapist',
            'substituteTherapist',
        ])->where('attendance_token', $token)->first();

        if (!$session) {
            throw new \Exception('QR Code kartu atau sesi tidak valid / tidak ditemukan.');
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

        // Validasi rentang jam sesi hadir
        $sStartTime = $session->start_time ?: $session->schedule?->start_time;
        $sEndTime = $session->end_time ?: $session->schedule?->end_time;
        if ($sStartTime && $sEndTime) {
            $nowTime = Carbon::now()->format('H:i');
            $stShort = substr($sStartTime, 0, 5);
            $etShort = substr($sEndTime, 0, 5);
            if ($nowTime < $stShort || $nowTime > $etShort) {
                throw new \Exception("Presensi hadir untuk {$childName} hanya valid dicatat di rentang jam sesi berlangsung ({$stShort} - {$etShort}). Jam saat ini: {$nowTime} WIB.");
            }
        }

        $session->status = 'present';
        $session->checked_in_at = Carbon::now();
        $session->save();

        return $session;
    }
}
