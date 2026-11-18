<?php

namespace App\Services;

use App\Actions\Admin\CreatePatientAction;
use App\Actions\Child\UpdateChildWithFamilyAction;
use App\Models\Child;
use Illuminate\Support\Collection;

class ChildService
{
    public function __construct(
        private UpdateChildWithFamilyAction $updateChildWithFamilyAction,
        private CreatePatientAction $createPatientAction,
    ) {}

    public function getAllChild(array $filters = []): Collection
    {
        return Child::with('family.guardians.user')
            ->when(!empty($filters['has_completed_assessment']), function ($q) {
                $q->whereHas('assessment', function ($sub) {
                    $sub->where('status', 'completed')
                        ->where('parent_status', 'completed');
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function store(array $data): Child
    {
        return $this->createPatientAction->execute($data);
    }

    public function update(array $data, Child $child): Child
    {
        return $this->updateChildWithFamilyAction->execute($child, $data);
    }

    public function destroy(Child $child): bool
    {
        return $child->delete();
    }
}
