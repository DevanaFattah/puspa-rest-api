<?php

namespace App\Services;

use App\Actions\Admin\CreatePatientAction;
use App\Actions\Child\UpdateChildWithFamilyAction;
use App\Models\Child;
use App\Models\Guardian;
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

    public function searchGuardians(string $search): Collection
    {
        $cleanSearch = trim($search);

        $guardians = Guardian::with([
            'family.children:id,family_id,child_name',
            'user:id,email'
        ])
        ->where(function ($query) use ($cleanSearch) {
            $query->where('guardian_name', 'like', "%{$cleanSearch}%")
                ->orWhere('temp_email', 'like', "%{$cleanSearch}%")
                ->orWhereHas('user', fn($q) => $q->where('email', 'like', "%{$cleanSearch}%"))
                ->orWhereHas('family.children', fn($q) => $q->where('child_name', 'like', "%{$cleanSearch}%"));
        })
        ->limit(15)
        ->get();

        // Jika pencarian berupa angka (nomor telepon), cocokkan juga dengan nomor telepon terdekripsi
        if (preg_match('/^[0-9+]+$/', $cleanSearch) && $guardians->count() < 15) {
            $phoneDigits = preg_replace('/[^0-9]/', '', $cleanSearch);
            $phoneMatches = Guardian::with([
                'family.children:id,family_id,child_name',
                'user:id,email'
            ])
            ->whereNotIn('id', $guardians->pluck('id'))
            ->get()
            ->filter(function ($g) use ($phoneDigits) {
                $phone = preg_replace('/[^0-9]/', '', (string) $g->guardian_phone);
                return !empty($phone) && str_contains($phone, $phoneDigits);
            })
            ->take(15 - $guardians->count());

            $guardians = $guardians->merge($phoneMatches);
        }

        return $guardians;
    }
}
