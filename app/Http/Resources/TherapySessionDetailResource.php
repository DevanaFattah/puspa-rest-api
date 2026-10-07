<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TherapySessionDetailResource extends JsonResource
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

        // ── Child age & category ──────────────────────────────────────────
        $birthDate    = $child?->child_birth_date;
        $age          = $birthDate ? Carbon::parse($birthDate)->age : null;

        // ── Reschedule info ───────────────────────────────────────────────
        // rescheduleOriginal: this session WAS rescheduled → the new date lives on newSession
        $rescheduleInfo = null;
        if ($this->rescheduleOriginal) {
            $newSession       = $this->rescheduleOriginal->newSession;
            $newDate          = $newSession?->session_date;
            $rescheduleInfo   = [
                'reschedule_id'   => $this->rescheduleOriginal->id,
                'reason'          => $this->rescheduleOriginal->reason,
                'requested_by'    => $this->rescheduleOriginal->requested_by,
                'new_session_id'  => $this->rescheduleOriginal->new_session_id,
                'new_session_date' => $newDate
                    ? Carbon::parse($newDate)->format('Y-m-d')
                    : null,
                'new_session_date_formatted' => $newDate
                    ? Carbon::parse($newDate)->translatedFormat('d F Y')
                    : null,
            ];
        }

        return [
            // ── Session ───────────────────────────────────────────────────
            'id'                        => $this->id,
            'session_number'            => $this->session_number,
            'session_date'              => $sessionDate?->format('Y-m-d'),
            'session_date_formatted'    => $sessionDate?->translatedFormat('d F Y'),
            'day'                       => $sessionDate ? $dayLabels[$sessionDate->dayOfWeekIso] ?? '-' : '-',
            'status'                    => $this->status,
            'notes'                     => $this->notes,
            'checked_in_at'             => $this->checked_in_at?->toIso8601String(),

            // ── Schedule ──────────────────────────────────────────────────
            'schedule_id'               => $this->schedule_id,
            'start_time'                => $schedule?->start_time ? substr($schedule->start_time, 0, 5) : '-',
            'end_time'                  => $schedule?->end_time   ? substr($schedule->end_time, 0, 5)   : '-',
            'therapy_type'              => $rawType,
            'therapy_type_label'        => $therapyTypeLabels[$rawType] ?? ucfirst((string) $rawType),

            // ── Therapist ─────────────────────────────────────────────────
            'therapist_id'              => $therapist?->id,
            'therapist_name'            => $therapist?->therapist_name ?? '-',
            'is_substitute'             => $this->therapist_id !== null,

            // ── Child detail ──────────────────────────────────────────────
            'child_id'                  => $child?->id,
            'child_name'                => $child?->child_name               ?? '-',
            'medical_record_number'     => $child?->medical_record_number    ?? '-',
            'child_gender'              => $child?->child_gender              ?? '-',
            'child_age'                 => $age,
            'child_birth_date'          => $birthDate
                ? Carbon::parse($birthDate)->format('Y-m-d')
                : null,
            'child_birth_date_formatted' => $birthDate
                ? Carbon::parse($birthDate)->translatedFormat('d F Y')
                : null,
            'child_birth_place'         => $child?->child_birth_place        ?? '-',
            'child_school'              => $child?->child_school              ?? '-',
            'child_complaint'           => $child?->child_complaint           ?? '-',

            // ── Guardian / parent detail ──────────────────────────────────
            'guardian_name'             => $guardian?->guardian_name         ?? '-',
            'guardian_phone'            => $guardian?->guardian_phone        ?? '-',
            'guardian_type'             => $guardian?->guardian_type         ?? '-',
            'relationship_with_child'   => $guardian?->relationship_with_child ?? '-',

            // ── Reschedule (null when not rescheduled) ────────────────────
            'is_rescheduled'            => $rescheduleInfo !== null,
            'reschedule_info'           => $rescheduleInfo,

            'created_at'                => $this->created_at?->toIso8601String(),
            'updated_at'                => $this->updated_at?->toIso8601String(),
        ];
    }
}
