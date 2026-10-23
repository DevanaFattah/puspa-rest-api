<?php

namespace App\Actions\TherapySoap;

use App\Models\Child;
use App\Models\Therapist;
use App\Models\User;

class GetTherapySoapExportFilterOptionsAction
{
    /**
     * Retrieve filter options for SOAP export.
     *
     * For therapist/assessor users, options are scoped to their own profile and handled children.
     * For admin users, all therapists and children are returned, with optional therapy_type/therapist filtering.
     *
     * @param User $user
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function execute(User $user, array $filters = []): array
    {
        $therapyTypeLabels = [
            'okupasi'  => 'Terapi Okupasi',
            'wicara'   => 'Terapi Wicara',
            'paedagog' => 'Terapi Paedagogik',
            'fisio'    => 'Terapi Fisio',
        ];

        $therapyTypesList = [
            ['value' => 'okupasi',  'label' => 'Terapi Okupasi'],
            ['value' => 'wicara',   'label' => 'Terapi Wicara'],
            ['value' => 'paedagog', 'label' => 'Terapi Paedagogik'],
            ['value' => 'fisio',    'label' => 'Terapi Fisio'],
        ];

        $isAdmin = $user->hasRole('admin');

        // Admin behavior: all children & therapists open, with optional pairing by therapy_type / therapist_id
        if ($isAdmin) {
            $therapistQuery = Therapist::select('id', 'therapist_name', 'therapist_section')
                ->orderBy('therapist_name');

            if (!empty($filters['therapy_type'])) {
                $therapistQuery->where('therapist_section', $filters['therapy_type']);
            }

            if (!empty($filters['therapist_id'])) {
                $therapistQuery->where('id', $filters['therapist_id']);
            }

            $allTherapists = $therapistQuery->get()
                ->map(fn($t) => [
                    'id'                 => $t->id,
                    'therapist_name'     => $t->therapist_name,
                    'therapy_type'       => $t->therapist_section,
                    'therapy_type_label' => $therapyTypeLabels[$t->therapist_section] ?? ucfirst((string) $t->therapist_section),
                ])->values()->all();

            $allChildren = Child::select('id', 'child_name', 'medical_record_number')
                ->orderBy('child_name')
                ->get()
                ->map(fn($c) => [
                    'id'                    => $c->id,
                    'child_name'            => $c->child_name,
                    'medical_record_number' => $c->medical_record_number,
                ])->values()->all();

            return [
                'scope'         => 'all',
                'therapy_types' => $therapyTypesList,
                'therapists'    => $allTherapists,
                'children'      => $allChildren,
            ];
        }

        // Terapis / Asesor behavior: only their own profile and handled children
        $therapist   = $user->therapist;
        $therapistId = $therapist->id;
        $rawSection  = $therapist->therapist_section;

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

        $therapistData = [
            'id'                 => $therapist->id,
            'therapist_name'     => $therapist->therapist_name,
            'therapy_type'       => $rawSection,
            'therapy_type_label' => $therapyTypeLabels[$rawSection] ?? ucfirst((string) $rawSection),
        ];

        return [
            'scope'              => 'therapist',
            'therapist_id'       => $therapist->id,
            'therapist_name'     => $therapist->therapist_name,
            'therapy_type'       => $rawSection,
            'therapy_type_label' => $therapyTypeLabels[$rawSection] ?? ucfirst((string) $rawSection),
            'therapists'         => [$therapistData],
            'therapy_types'      => [
                [
                    'value' => $rawSection,
                    'label' => $therapyTypeLabels[$rawSection] ?? ucfirst((string) $rawSection),
                ],
            ],
            'children'           => $children->map(fn($c) => [
                'id'                    => $c->id,
                'child_name'            => $c->child_name,
                'medical_record_number' => $c->medical_record_number,
            ])->values()->all(),
        ];
    }
}
