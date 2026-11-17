<?php

namespace App\Actions\Guardian;

use App\Models\Child;
use App\Models\Guardian;
use App\Models\Observation;
use Illuminate\Support\Facades\DB;

class AddChildAction
{
    public function execute(Guardian $guardian, array $data): Child
    {
        return DB::transaction(function () use ($guardian, $data) {
            $schoolId = $data['school_id'] ?? null;
            $schoolName = $data['child_school'] ?? null;
            if (!$schoolId && !empty($schoolName)) {
                $schoolId = \App\Models\School::where('school_name', $schoolName)->value('id');
            }
            if ($schoolId && empty($schoolName)) {
                $schoolName = \App\Models\School::find($schoolId)?->school_name;
            }

            $child = Child::create(
                array_merge($data, [
                    'family_id' => $guardian->family_id,
                    'school_id' => $schoolId,
                    'child_school' => $schoolName,
                ])
            );

            Observation::create([
                'child_id' => $child->id,
                'scheduled_date' => $this->nextWeekDay(),
                'age_category' => Child::calculateAgeAndCategory($data['child_birth_date'])['category'],
                'status' => 'pending',
            ]);

            return $child;
        });
    }

    private function nextWeekDay()
    {
        $date = now()->addDay();
        while ($date->isWeekend()) $date->addDay();

        return $date;
    }
}
