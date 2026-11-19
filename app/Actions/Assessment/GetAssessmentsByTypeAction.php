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

    $type = $filters['type'] ?? null;
    $status = $filters['status'] ?? null;

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
      ->when(!empty($type), function ($q) use ($type, $status, $filterMyAssessments, $myTherapistId) {
        $q->whereHas('assessmentDetails', function ($d) use ($type, $status, $filterMyAssessments, $myTherapistId) {
          $d->where('type', $type);
          if ($filterMyAssessments) {
            $d->where('therapist_id', $myTherapistId);
          }
          if ($status === 'completed') {
            $d->whereNotNull('completed_at');
          } elseif ($status === 'scheduled') {
            $d->whereNull('completed_at');
          }
        });
      }, function ($q) use ($status, $filterMyAssessments, $myTherapistId) {
        if ($status) {
          $q->where('status', $status);
        }
        if ($filterMyAssessments) {
          $q->whereHas('assessmentDetails', fn($d) => $d->where('therapist_id', $myTherapistId));
        }
      })
      ->when(isset($filters['status']) && $filters['status'] === 'completed', fn($q) => $q->orderBy('scheduled_date', 'desc'))
      ->when(isset($filters['date']), fn($q) => $q->whereDate('scheduled_date', $filters['date']))
      ->when(isset($filters['search']), function ($q) use ($filters) {
        $q->whereHas('child', fn($c) => $c->where('child_name', 'like', "%{$filters['search']}%"));
      })
      ->orderBy('scheduled_date', 'asc')
      ->get();
  }
}
