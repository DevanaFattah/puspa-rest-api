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
            // 1. Update original session status to cancelled
            $originalSession->status = 'cancelled';
            $originalSession->save();

            // 2. Create new therapy session for rescheduled date
            $newSession = TherapySession::create([
                'schedule_id' => $originalSession->schedule_id,
                'session_number' => $originalSession->session_number,
                'session_date' => $data['new_date'],
                'therapist_id' => $data['new_therapist_id'] ?? $originalSession->therapist_id,
                'status' => 'pending',
                'notes' => 'Reschedule dari sesi tanggal ' . $originalSession->session_date->format('Y-m-d'),
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
