<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\TherapySession;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ScheduleService
{
    public function getPaginatedSchedules(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = Schedule::with(['child.family.guardians', 'therapist']);

        if (!empty($filters['day_of_week'])) {
            $query->where('day_of_week', $filters['day_of_week']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['therapy_type'])) {
            $query->where('therapy_type', $filters['therapy_type']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('child', function ($cq) use ($search) {
                    $cq->where('child_name', 'like', "%{$search}%")
                       ->orWhere('medical_record_number', 'like', "%{$search}%");
                })->orWhereHas('child.family.guardians', function ($gq) use ($search) {
                    $gq->where('guardian_name', 'like', "%{$search}%");
                });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function createSchedule(array $data): Schedule
    {
        return DB::transaction(function () use ($data) {
            $schedule = Schedule::create($data);

            // Auto-generate therapy_sessions based on day_of_week and total_meetings
            $dayOfWeek = (int) $data['day_of_week']; // 1=Mon, ..., 7=Sun
            $totalMeetings = (int) $data['total_meetings'];
            $startDate = Carbon::parse($data['period_start']);

            // Find first occurrence of specified day_of_week on or after period_start
            // ISO day of week in Carbon: 1=Mon, 7=Sun
            while ($startDate->dayOfWeekIso !== $dayOfWeek) {
                $startDate->addDay();
            }

            $currentDate = $startDate->copy();
            for ($i = 1; $i <= $totalMeetings; $i++) {
                TherapySession::create([
                    'schedule_id' => $schedule->id,
                    'session_number' => $i,
                    'session_date' => $currentDate->toDateString(),
                    'status' => 'pending',
                ]);

                $currentDate->addWeek();
            }

            return $schedule->load(['child.family.guardians', 'therapist', 'sessions']);
        });
    }

    public function getScheduleDetail(string $id): Schedule
    {
        return Schedule::with(['child.family.guardians', 'therapist', 'sessions.substituteTherapist'])
            ->findOrFail($id);
    }

    public function updateSchedule(array $data, Schedule $schedule): Schedule
    {
        $schedule->update($data);
        return $schedule->load(['child.family.guardians', 'therapist', 'sessions']);
    }

    public function deleteSchedule(Schedule $schedule): bool
    {
        return DB::transaction(function () use ($schedule) {
            $schedule->sessions()->delete();
            return $schedule->delete();
        });
    }
}
