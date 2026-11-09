<?php

namespace App\Actions\Observation;

use App\Models\Assessment;
use App\Models\AssessmentDetail;
use App\Models\Observation;
use App\Models\Therapist;
use Illuminate\Support\Collection;

class TherapistOptionsAction
{
    public function execute(Observation $observation): Collection
    {
        // 1. Ambil semua assessment details untuk observasi ini
        $details = AssessmentDetail::whereHas('assessment', fn($q) => $q->where('observation_id', $observation->id))
            ->select(['id', 'type', 'therapist_id'])
            ->get();

        return $this->formatDetails($details);
    }

    public function executeForAssessment(Assessment $assessment): Collection
    {
        // 1. Ambil semua assessment details untuk assessment ini
        $details = $assessment->assessmentDetails()
            ->select(['id', 'type', 'therapist_id'])
            ->get();

        return $this->formatDetails($details);
    }

    private function formatDetails(Collection $details): Collection
    {
        // 2. Ambil tipe-tipe spesialis yang ada di details
        $sections = $details->pluck('type')->unique();

        // 3. Ambil terapis yang relevan
        $therapists = Therapist::query()
            ->when(!$sections->contains('umum'), function ($query) use ($sections) {
                $query->whereIn('therapist_section', $sections);
            })
            ->select(['id', 'therapist_name', 'therapist_section'])
            ->get();

        // 4. Petakan terapis ke setiap assessment detail
        return $details->map(function ($detail) use ($therapists) {
            $matchingTherapists = ($detail->type === 'umum')
                ? $therapists
                : $therapists->where('therapist_section', $detail->type)->values();

            return [
                'assessment_detail_id' => $detail->id,
                'type'                 => $detail->type,
                'current_therapist_id' => $detail->therapist_id,
                'therapists'           => $matchingTherapists->map(fn($t) => [
                    'id'   => $t->id,
                    'name' => $t->therapist_name,
                ])->values(),
            ];
        });
    }
}
