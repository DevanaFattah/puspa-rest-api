<?php

namespace App\Http\Resources\School;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SchoolStudentListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $birthDate = $this->child_birth_date instanceof Carbon
            ? $this->child_birth_date
            : ($this->child_birth_date ? Carbon::parse($this->child_birth_date) : null);

        $guardian = $this->family?->guardians?->first();

        return [
            'id'                 => $this->id,
            'child_name'         => $this->child_name,
            'child_gender'       => $this->child_gender,
            'child_birth_date'   => $birthDate?->format('Y-m-d'),
            'age'                => $birthDate?->age,
            'guardian_name'      => $guardian?->guardian_name ?? '-',
            'guardian_phone'     => $guardian?->guardian_phone ?? '-',
            'guardian_type'      => $guardian?->guardian_type ?? '-',
            'observation_status' => $this->observation?->status,
            'assessment_status'  => $this->assessment?->status,
            'created_at'         => $this->created_at?->format('Y-m-d H:i'),
        ];
    }
}

