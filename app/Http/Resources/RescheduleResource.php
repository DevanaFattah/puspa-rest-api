<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RescheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $original = $this->originalSession;
        $new = $this->newSession;
        $schedule = $original?->schedule;
        $child = $schedule?->child;

        return [
            'id' => $this->id,
            'requested_by' => $this->requested_by,
            'reason' => $this->reason,
            'child_id' => $child?->id,
            'child_name' => $child?->child_name ?? '-',
            'medical_record_number' => $child?->medical_record_number ?? '-',
            'therapy_type' => $schedule?->therapy_type ?? '-',
            'original_session' => $original ? [
                'id' => $original->id,
                'session_number' => $original->session_number,
                'session_date' => $original->session_date?->format('Y-m-d'),
                'therapist_name' => $original->substituteTherapist?->therapist_name ?? $schedule?->therapist?->therapist_name ?? '-',
                'status' => $original->status,
            ] : null,
            'new_session' => $new ? [
                'id' => $new->id,
                'session_number' => $new->session_number,
                'session_date' => $new->session_date?->format('Y-m-d'),
                'therapist_name' => $new->substituteTherapist?->therapist_name ?? $schedule?->therapist?->therapist_name ?? '-',
                'status' => $new->status,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
