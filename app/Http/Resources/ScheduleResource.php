<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $dayNames = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        $guardian = $this->child?->family?->guardians->first();

        return [
            'id' => $this->id,
            'child_id' => $this->child_id,
            'child_name' => $this->child?->child_name ?? '-',
            'medical_record_number' => $this->child?->medical_record_number ?? '-',
            'guardian_name' => $guardian?->guardian_name ?? '-',
            'guardian_phone' => $guardian?->guardian_phone ?? '-',
            'therapist_id' => $this->therapist_id,
            'therapist_name' => $this->therapist?->therapist_name ?? '-',
            'therapy_type' => $this->therapy_type,
            'day_of_week' => $this->day_of_week,
            'day_name' => $dayNames[$this->day_of_week] ?? '-',
            'start_time' => substr($this->start_time, 0, 5),
            'end_time' => substr($this->end_time, 0, 5),
            'total_meetings' => $this->total_meetings,
            'period_start' => $this->period_start?->format('Y-m-d'),
            'period_end' => $this->period_end?->format('Y-m-d'),
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
