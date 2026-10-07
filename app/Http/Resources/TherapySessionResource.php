<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TherapySessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $schedule  = $this->schedule;
        $child     = $schedule?->child;
        $guardian  = $child?->family?->guardians?->first();

        // Use substitute therapist if set, otherwise fall back to schedule's therapist
        $therapist = $this->substituteTherapist ?? $schedule?->therapist;

        $sessionDate = $this->session_date instanceof Carbon
            ? $this->session_date
            : ($this->session_date ? Carbon::parse($this->session_date) : null);

        $therapyTypeLabels = [
            'okupasi'  => 'Terapi Okupasi',
            'wicara'   => 'Terapi Wicara',
            'paedagog' => 'Terapi Paedagogik',
            'fisio'    => 'Terapi Fisio',
        ];

        $dayLabels = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        $rawType = $schedule?->therapy_type;

        return [
            'id'                     => $this->id,
            'session_number'         => $this->session_number,
            'session_date'           => $sessionDate?->format('Y-m-d'),
            'session_date_formatted' => $sessionDate?->translatedFormat('d F Y'),
            'day'                    => $sessionDate ? $dayLabels[$sessionDate->dayOfWeekIso] ?? '-' : '-',

            // Schedule info
            'schedule_id'            => $this->schedule_id,
            'start_time'             => $schedule?->start_time ? substr($schedule->start_time, 0, 5) : '-',
            'end_time'               => $schedule?->end_time   ? substr($schedule->end_time, 0, 5)   : '-',
            'therapy_type'           => $rawType,
            'therapy_type_label'     => $therapyTypeLabels[$rawType] ?? ucfirst((string) $rawType),

            // Child info
            'child_id'               => $child?->id,
            'child_name'             => $child?->child_name             ?? '-',
            'medical_record_number'  => $child?->medical_record_number  ?? '-',

            // Guardian / parent info
            'guardian_name'          => $guardian?->guardian_name  ?? '-',
            'guardian_phone'         => $guardian?->guardian_phone ?? '-',
            'guardian_type'          => $guardian?->guardian_type  ?? '-',

            // Therapist info
            'therapist_id'           => $therapist?->id,
            'therapist_name'         => $therapist?->therapist_name ?? '-',
            'is_substitute'          => $this->therapist_id !== null,

            // Session state
            'status'                 => $this->status,
            'notes'                  => $this->notes,
            'checked_in_at'          => $this->checked_in_at?->toIso8601String(),

            'created_at'             => $this->created_at?->toIso8601String(),
            'updated_at'             => $this->updated_at?->toIso8601String(),
        ];
    }
}
