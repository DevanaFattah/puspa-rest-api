<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TherapySoapResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $session   = $this->therapySession;
        $schedule  = $session?->schedule;
        $child     = $schedule?->child;
        $guardian  = $child?->family?->guardians?->first();

        // Use substitute therapist if present, otherwise fall back to schedule's therapist
        $therapist = $session?->substituteTherapist ?? $schedule?->therapist;

        // ── Child age ─────────────────────────────────────────────────────
        $birthDate = $child?->child_birth_date;
        $age       = $birthDate ? Carbon::parse($birthDate)->age : null;

        // ── Therapy type label ────────────────────────────────────────────
        $rawType = $schedule?->therapy_type;
        $therapyTypeLabels = [
            'okupasi'  => 'Terapi Okupasi',
            'wicara'   => 'Terapi Wicara',
            'paedagog' => 'Terapi Paedagogik',
            'fisio'    => 'Terapi Fisio',
        ];

        // ── performed_at ──────────────────────────────────────────────────
        $performedAt = $this->performed_at instanceof Carbon
            ? $this->performed_at
            : ($this->performed_at ? Carbon::parse($this->performed_at) : null);

        // ── Total therapy sessions recorded for this child ────────────────
        // Count all SOAPs linked to the same child (via session → schedule → child)
        $totalSoapRecorded = null;
        if ($child) {
            $totalSoapRecorded = \App\Models\TherapySoap::whereHas(
                'therapySession.schedule',
                fn($q) => $q->where('child_id', $child->id)
            )->count();
        }

        // ── Last intervention from the previous SOAP for the same child ───
        $lastIntervention = null;
        if ($child) {
            $previous = \App\Models\TherapySoap::whereHas(
                'therapySession.schedule',
                fn($q) => $q->where('child_id', $child->id)
            )
                ->where('id', '!=', $this->id)
                ->orderBy('performed_at', 'desc')
                ->value('interventions');

            $lastIntervention = $previous;
        }

        return [
            // ── SOAP record ───────────────────────────────────────────────
            'id'                    => $this->id,
            'therapy_session_id'    => $this->therapy_session_id,
            'performed_at'          => $performedAt?->toIso8601String(),
            'performed_date'        => $performedAt?->format('Y-m-d'),
            'performed_date_formatted' => $performedAt?->translatedFormat('d F Y'),
            'performed_time'        => $performedAt?->format('H:i'),

            // ── Session info ──────────────────────────────────────────────
            'session_number'        => $session?->session_number,
            'session_date'          => $session?->session_date?->format('Y-m-d'),
            'session_status'        => $session?->status,

            // ── Schedule / therapy info ───────────────────────────────────
            'schedule_id'           => $session?->schedule_id,
            'therapy_type'          => $rawType,
            'therapy_type_label'    => $therapyTypeLabels[$rawType] ?? ucfirst((string) $rawType),
            'start_time'            => $schedule?->start_time ? substr($schedule->start_time, 0, 5) : '-',
            'end_time'              => $schedule?->end_time   ? substr($schedule->end_time, 0, 5)   : '-',
            'total_meetings'        => $schedule?->total_meetings,

            // ── Therapist info ────────────────────────────────────────────
            'therapist_id'          => $therapist?->id,
            'therapist_name'        => $therapist?->therapist_name ?? '-',
            'is_substitute'         => $session?->therapist_id !== null,

            // ── Child info ────────────────────────────────────────────────
            'child_id'              => $child?->id,
            'child_name'            => $child?->child_name               ?? '-',
            'medical_record_number' => $child?->medical_record_number    ?? '-',
            'child_gender'          => $child?->child_gender             ?? '-',
            'child_age'             => $age,
            'child_birth_date'      => $birthDate ? Carbon::parse($birthDate)->format('Y-m-d') : null,
            'child_birth_date_formatted' => $birthDate
                ? Carbon::parse($birthDate)->translatedFormat('d F Y')
                : null,
            'child_birth_place'     => $child?->child_birth_place        ?? '-',
            'child_address'         => $child?->child_address            ?? '-',
            'child_school'          => $child?->child_school             ?? '-',

            // ── Guardian / parent info ────────────────────────────────────
            'guardian_name'         => $guardian?->guardian_name             ?? '-',
            'guardian_phone'        => $guardian?->guardian_phone            ?? '-',
            'guardian_type'         => $guardian?->guardian_type             ?? '-',
            'relationship_with_child' => $guardian?->relationship_with_child ?? '-',

            // ── Aggregate stats for child ─────────────────────────────────
            'total_soap_recorded'   => $totalSoapRecorded,
            'last_intervention'     => $lastIntervention,

            // ── S.O.A.P core ──────────────────────────────────────────────
            'therapy_diagnosis'     => $this->therapy_diagnosis,
            'interventions'         => $this->interventions,
            'subjective'            => $this->subjective,
            'objective'             => $this->objective,
            'assessment'            => $this->assessment,
            'plan'                  => $this->plan,
            'implementation'        => $this->implementation,
            'advanced_assessment'   => $this->advanced_assessment,
            'advanced_plan'         => $this->advanced_plan,

            'created_at'            => $this->created_at?->toIso8601String(),
            'updated_at'            => $this->updated_at?->toIso8601String(),
        ];
    }
}
