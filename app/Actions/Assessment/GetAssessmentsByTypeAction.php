<?php

namespace App\Actions\Assessment;

use App\Models\Assessment;
use Illuminate\Database\Eloquent\Collection;

class GetAssessmentsByTypeAction
{
  public function execute(array $filters): Collection
  {
    $myTherapistId = auth()->user()?->therapist?->id;
    $filterMyAssessments = !empty($filters['my_assessments']) && $myTherapistId;

    return Assessment::query()
      ->with([
        'assessmentDetails' => function ($q) use ($filters) {
          $q->with(['therapist:id,therapist_name', 'admin:id,admin_name'])
            ->when(isset($filters['type']), fn($sub) => $sub->where('type', $filters['type']));
        },
        'child:id,family_id,child_name',
        'child.family:id',
        'child.family.guardians',
      ])
      ->whereNotNull('scheduled_date')
      ->when(isset($filters['status']), function ($q) use ($filters) {
        $q->where('status', $filters['status']);
      })
      ->when(isset($filters['status']) && $filters['status'] === 'completed', fn($q) => $q->orderBy('scheduled_date', 'desc'))
      ->when(isset($filters['type']), function ($q) use ($filters, $filterMyAssessments, $myTherapistId) {
        $q->whereHas('assessmentDetails', function ($d) use ($filters, $filterMyAssessments, $myTherapistId) {
          $d->where('type', $filters['type']);
          if ($filterMyAssessments) {
            $d->where('therapist_id', $myTherapistId);
          }
        });
      }, function ($q) use ($filterMyAssessments, $myTherapistId) {
        if ($filterMyAssessments) {
          $q->whereHas('assessmentDetails', fn($d) => $d->where('therapist_id', $myTherapistId));
        }
      })
      ->when(isset($filters['date']), fn($q) => $q->whereDate('scheduled_date', $filters['date']))
      ->when(isset($filters['search']), function ($q) use ($filters) {
        $q->whereHas('child', fn($c) => $c->where('child_name', 'like', "%{$filters['search']}%"));
      })
      ->orderBy('scheduled_date', 'asc')
      ->get();
  }
}
