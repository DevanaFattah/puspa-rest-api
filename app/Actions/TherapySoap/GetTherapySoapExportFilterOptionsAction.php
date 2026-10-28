<?php

namespace App\Actions\TherapySoap;

use App\Models\Child;
use App\Models\Therapist;
use App\Models\User;

class GetTherapySoapExportFilterOptionsAction
{
    public const THERAPY_TYPES_LIST = [
        ['value' => 'okupasi',  'label' => 'Terapi Okupasi'],
        ['value' => 'wicara',   'label' => 'Terapi Wicara'],
        ['value' => 'paedagog', 'label' => 'Terapi Paedagogik'],
        ['value' => 'fisio',    'label' => 'Terapi Fisio'],
    ];

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
        $isAdmin = $user->hasRole('admin');

        // Admin behavior: all children & therapists open, with optional pairing by therapy_type / therapist_id
        if ($isAdmin) {
            $therapists = Therapist::query()
                ->select('id', 'therapist_name', 'therapist_section')
                ->when(!empty($filters['therapy_type']), fn($q) => $q->where('therapist_section', $filters['therapy_type']))
                ->orderBy('therapist_name')
                ->get();

            $children = Child::query()
                ->select('id', 'child_name', 'medical_record_number')
                ->when(!empty($filters['therapist_id']), fn($q) => $q->whereRelation('schedules', 'therapist_id', $filters['therapist_id']))
                ->when(!empty($filters['therapy_type']) && empty($filters['therapist_id']), fn($q) => $q->whereRelation('schedules', 'therapy_type', $filters['therapy_type']))
                ->orderBy('child_name')
                ->get();

            return [
                'scope'         => 'all',
                'therapy_types' => self::THERAPY_TYPES_LIST,
                'therapists'    => $therapists,
                'children'      => $children,
            ];
        }

        // Terapis / Asesor behavior: only their own profile and handled children
        $therapist   = $user->therapist;
        $therapistId = $therapist->id;
        $rawSection  = $therapist->therapist_section;

        // Children assigned to this therapist via primary schedule or substitute session
        $children = Child::query()
            ->where(function ($query) use ($therapistId) {
                $query->whereRelation('schedules', 'therapist_id', $therapistId)
                    ->orWhereRelation('schedules.sessions', 'therapist_id', $therapistId);
            })
            ->whereHas('schedules.sessions.soap')
            ->select('id', 'child_name', 'medical_record_number')
            ->orderBy('child_name')
            ->get();

        return [
            'scope'         => 'therapist',
            'therapist'     => $therapist,
            'therapists'    => collect([$therapist]),
            'children'      => $children,
            'therapy_types' => collect(self::THERAPY_TYPES_LIST)
                ->where('value', $rawSection)
                ->values()
                ->all(),
        ];
    }
}
