<?php

namespace App\Http\Resources\School;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SchoolStudentDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $birthDate = $this->child_birth_date instanceof Carbon
            ? $this->child_birth_date
            : ($this->child_birth_date ? Carbon::parse($this->child_birth_date) : null);

        $guardians = $this->family?->guardians?->map(function ($g) {
            return [
                'guardian_name'  => $g->guardian_name,
                'guardian_type'  => $g->guardian_type,
                'guardian_phone' => $g->guardian_phone ?? '-',
            ];
        }) ?? [];

        $observationSummary = null;
        if ($this->observation) {
            $obs = $this->observation;
            $observationSummary = [
                'status'                     => $obs->status,
                'scheduled_date'             => $obs->scheduled_date,
                'completed_at'               => $obs->completed_at,
                'total_score'                => $obs->total_score,
                'conclusion'                 => $obs->conclusion,
                'recommendation'             => $obs->recommendation,
                'is_continued_to_assessment' => (bool) $obs->is_continued_to_assessment,
            ];
        }

        $assessmentSummary = null;
        if ($this->assessment) {
            $asm = $this->assessment;
            $assessmentSummary = [
                'status'         => $asm->status,
                'scheduled_date' => $asm->scheduled_date,
                'details'        => $asm->assessmentDetails?->map(function ($d) {
                    return [
                        'type'         => $d->type,
                        'completed_at' => $d->completed_at,
                        'status'       => $d->completed_at ? 'completed' : 'pending',
                    ];
                }) ?? [],
            ];
        }

        return [
            'id'                  => $this->id,
            'child_name'          => $this->child_name,
            'child_gender'        => $this->child_gender,
            'child_birth_place'   => $this->child_birth_place,
            'child_birth_date'    => $birthDate?->format('Y-m-d'),
            'age'                 => $birthDate?->age,
            'child_religion'      => $this->child_religion ?? '-',
            'child_address'       => $this->child_address ?? '-',
            'child_complaint'     => $this->child_complaint,
            'school'              => [
                'id'          => $this->school?->id,
                'school_name' => $this->school?->school_name,
                'npsn'        => $this->school?->npsn,
            ],
            'guardians'           => $guardians,
            'observation_summary' => $observationSummary,
            'assessment_summary'  => $assessmentSummary,
            'created_at'          => $this->created_at?->format('Y-m-d H:i'),
        ];
    }
}

