<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TherapySoapExportFilterOptionsResource extends JsonResource
{
    private const THERAPY_TYPE_LABELS = [
        'okupasi'  => 'Terapi Okupasi',
        'wicara'   => 'Terapi Wicara',
        'paedagog' => 'Terapi Paedagogik',
        'fisio'    => 'Terapi Fisio',
    ];

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $therapist = $this->resource['therapist'] ?? null;
        $therapyTypes = $this->resource['therapy_types'] ?? [];

        $data = [
            'scope'         => $this->resource['scope'],
            'therapy_types' => $therapyTypes,
            'therapists'    => collect($this->resource['therapists'] ?? [])->map(function ($t) {
                $section = $t->therapist_section;
                return [
                    'id'                 => $t->id,
                    'therapist_name'     => $t->therapist_name,
                    'therapy_type'       => $section,
                    'therapy_type_label' => self::THERAPY_TYPE_LABELS[$section] ?? ucfirst((string) $section),
                ];
            })->values()->all(),
            'children'      => collect($this->resource['children'] ?? [])->map(function ($c) {
                return [
                    'id'                    => $c->id,
                    'child_name'            => $c->child_name,
                    'medical_record_number' => $c->medical_record_number,
                ];
            })->values()->all(),
        ];

        // Include therapist-level shortcut fields if scoped to a specific therapist
        if (($this->resource['scope'] ?? null) === 'therapist' && !empty($this->resource['therapist'])) {
            $therapist = $this->resource['therapist'];
            $section   = $therapist->therapist_section;
            $data['therapist_id']       = $therapist->id;
            $data['therapist_name']     = $therapist->therapist_name;
            $data['therapy_type']       = $section;
            $data['therapy_type_label'] = self::THERAPY_TYPE_LABELS[$section] ?? ucfirst((string) $section);
        }

        return $data;
    }
}
