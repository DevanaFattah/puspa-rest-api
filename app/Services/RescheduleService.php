<?php

namespace App\Services;

use App\Models\Reschedule;
use App\Models\TherapySession;
use Illuminate\Support\Facades\DB;

class RescheduleService
{
    public function createReschedule(TherapySession $originalSession, array $data): Reschedule
    {
        return DB::transaction(function () use ($originalSession, $data) {
            // 1. Update original session status (preserve excused/izin if already marked excused, else set cancelled)
            if ($originalSession->status !== 'excused' && $originalSession->status !== 'sick') {
                $originalSession->status = 'cancelled';
                $originalSession->save();
            }

            // 2. Create new therapy session for rescheduled date
            $startTime = !empty($data['start_time']) 
                ? substr($data['start_time'], 0, 5) 
                : ($originalSession->start_time ?: $originalSession->schedule?->start_time);
            $endTime = !empty($data['end_time']) 
                ? substr($data['end_time'], 0, 5) 
                : ($originalSession->end_time ?: $originalSession->schedule?->end_time);

            if ($startTime) {
                $startTime = substr($startTime, 0, 5);
            }
            if ($endTime) {
                $endTime = substr($endTime, 0, 5);
            }

            $notes = 'Reschedule dari sesi tanggal ' . $originalSession->session_date->format('Y-m-d');
            if ($startTime && $endTime) {
                $notes .= ' pukul ' . $startTime . ' - ' . $endTime;
            }

            $newSession = TherapySession::create([
                'schedule_id' => $originalSession->schedule_id,
                'session_number' => $originalSession->session_number,
                'session_date' => $data['new_date'],
                'start_time' => $startTime,
                'end_time' => $endTime,
                'therapist_id' => $data['new_therapist_id'] ?? $originalSession->therapist_id,
                'status' => 'pending',
                'notes' => $notes,
            ]);

            // 3. Create Reschedule record
            $reschedule = Reschedule::create([
                'original_session_id' => $originalSession->id,
                'new_session_id' => $newSession->id,
                'requested_by' => $data['requested_by'],
                'reason' => $data['reason'],
            ]);

            return $reschedule->load([
                'originalSession.schedule.child',
                'originalSession.substituteTherapist',
                'originalSession.schedule.therapist',
                'newSession.substituteTherapist',
                'newSession.schedule.therapist',
            ]);
        });
    }
}
