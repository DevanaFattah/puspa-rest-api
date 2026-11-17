<?php

namespace App\Actions\Child;

use App\Actions\Guardian\UpdateFamilyGuardiansAction;
use App\Models\Child;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateChildWithFamilyAction
{
    public function __construct(
        protected UpdateFamilyGuardiansAction $updateGuardians
    ) {}

    public function execute(Child $child, array $data): Child
    {
        return DB::transaction(function () use ($child, $data) {
            $childData = Arr::only($data, [
                'child_name',
                'child_gender',
                'child_religion',
                'child_birth_place',
                'child_birth_date',
                'school_id',
                'child_school',
                'child_address',
                'child_complaint',
                'child_service_choice'
            ]);

            // Auto-resolve school_id if child_school name is provided but school_id is missing, or sync child_school name
            if (empty($childData['school_id']) && !empty($childData['child_school'])) {
                $childData['school_id'] = \App\Models\School::where('school_name', $childData['child_school'])->value('id');
            } elseif (!empty($childData['school_id']) && empty($childData['child_school'])) {
                $childData['child_school'] = \App\Models\School::find($childData['school_id'])?->school_name;
            }

            $child->update(Arr::whereNotNull($childData));

            if ($this->hasGuardianData($data)) {
                $primaryGuardian = $child->family->guardians()->whereNotNull('user_id')->first()
                    ?? $child->family->guardians()->first();

                $this->updateGuardians->execute($primaryGuardian, $data);
            }

            return $child->load('family.guardians');
        });
    }

    private function hasGuardianData(array $data): bool
    {
        return collect($data)->keys()->contains(
            fn($key) =>
            str($key)->startsWith(['father_', 'mother_', 'guardian_'])
        );
    }
}
