<?php

namespace App\Actions\Assessment;

use App\Models\Assessment;

class GetAssessmentsAction
{
  public function execute(array $filters): Assessment
  {
    return Assessment::query()
      ->with([
        'child:id,family_id,child_name,child_birth_date,child_gender',
        'child.family.guardians:id,family_id,guardian_name,guardian_phone,guardian_type',
        'assessmentDetails' => function ($q) use ($filters) {
          $q->with(['therapist:id,therapist_name', 'admin:id,admin_name'])
            ->when($filters['type'] ?? null, fn($sub) => $sub->where('type', $filters['type']));
        },
      ])
      ->whereNotNull('scheduled_date')
      ->when($filters['status'] ?? null, function ($q, $status) {
        $q->where('parent_status', $status);
      })
      ->when($filters['date'] ?? null, fn($q, $date) => $q->whereDate('scheduled_date', $date))
      ->when(
        $filters['search'] ?? null,
        fn($q, $search) =>
        $q->whereHas('child', fn($c) => $c->where('child_name', 'like', "%{$search}%"))
      )
      ->when($filters['type'] ?? null, function ($q, $type) {
        $q->whereHas('assessmentDetails', fn($d) => $d->where('type', $type));
      })
      ->orderBy('scheduled_date', 'asc')
      ->get();
  }
}
