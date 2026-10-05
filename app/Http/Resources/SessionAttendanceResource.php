<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionAttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $schedule = $this->schedule;
        $child = $schedule?->child;
        $guardian = $child?->family?->guardians->first();
        $therapist = $this->substituteTherapist ?? $schedule?->therapist;

        return [
            'id' => $this->id,
            'session_number' => $this->session_number,
            'session_date' => $this->session_date?->format('Y-m-d'),
            'schedule_id' => $this->schedule_id,
            'child_id' => $schedule?->child_id,
            'child_name' => $child?->child_name ?? '-',
            'medical_record_number' => $child?->medical_record_number ?? '-',
            'guardian_name' => $guardian?->guardian_name ?? '-',
            'guardian_phone' => $guardian?->guardian_phone ?? '-',
            'therapist_id' => $therapist?->id,
            'therapist_name' => $therapist?->therapist_name ?? '-',
            'therapy_type' => $schedule?->therapy_type ?? '-',
            'start_time' => $schedule?->start_time ? substr($schedule->start_time, 0, 5) : '-',
            'end_time' => $schedule?->end_time ? substr($schedule->end_time, 0, 5) : '-',
            'status' => $this->status,
            'notes' => $this->notes,
            'checked_in_at' => $this->checked_in_at?->toIso8601String(),
            'attendance_token' => $this->attendance_token,
            'is_rescheduled' => $this->rescheduleOriginal !== null,
            'reschedule_info' => $this->rescheduleOriginal ? [
                'reschedule_id' => $this->rescheduleOriginal->id,
                'new_session_id' => $this->rescheduleOriginal->new_session_id,
                'new_session_date' => $this->rescheduleOriginal->newSession?->session_date?->format('Y-m-d'),
                'requested_by' => $this->rescheduleOriginal->requested_by,
                'reason' => $this->rescheduleOriginal->reason,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
