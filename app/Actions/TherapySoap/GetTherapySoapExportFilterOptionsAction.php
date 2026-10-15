<?php

namespace App\Actions\TherapySoap;

use App\Models\Child;
use App\Models\Therapist;

class GetTherapySoapExportFilterOptionsAction
{
    /**
     * Retrieve filter options for SOAP export scoped to the given therapist.
     *
     * @param string $therapistId
     * @return array<string, mixed>
     */
    public function execute(string $therapistId): array
    {
        $therapist = Therapist::find($therapistId);
        if (!$therapist) {
            return [];
        }

        $therapyTypeLabels = [
            'okupasi'  => 'Terapi Okupasi',
            'wicara'   => 'Terapi Wicara',
            'paedagog' => 'Terapi Paedagogik',
            'fisio'    => 'Terapi Fisio',
        ];

        $rawSection = $therapist->therapist_section;

        // Children assigned to this therapist via primary schedule or substitute session
        $children = Child::query()
            ->where(function ($query) use ($therapistId) {
                $query->whereHas('schedules', function ($q) use ($therapistId) {
                    $q->where('therapist_id', $therapistId);
                })->orWhereHas('schedules.sessions', function ($q) use ($therapistId) {
                    $q->where('therapist_id', $therapistId);
                });
            })
            ->select('id', 'child_name', 'medical_record_number')
            ->orderBy('child_name', 'asc')
            ->get();

        return [
            'therapist_id'       => $therapist->id,
            'therapist_name'     => $therapist->therapist_name,
            'therapy_type'       => $rawSection,
            'therapy_type_label' => $therapyTypeLabels[$rawSection] ?? ucfirst((string) $rawSection),
            'children'           => $children->map(fn($c) => [
                'id'                    => $c->id,
                'child_name'            => $c->child_name,
                'medical_record_number' => $c->medical_record_number,
            ])->values()->all(),
        ];
    }
}
