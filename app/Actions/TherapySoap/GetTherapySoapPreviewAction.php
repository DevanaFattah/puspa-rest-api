<?php

namespace App\Actions\TherapySoap;

use App\Models\TherapySoap;
use Illuminate\Database\Eloquent\Collection;

class GetTherapySoapPreviewAction
{
    /**
     * Retrieve preview rows (max 10 rows) for SOAP export view.
     *
     * @param array $filters
     * @param int $limit
     * @return Collection
     */
    public function execute(array $filters = [], int $limit = 10): Collection
    {
        $query = TherapySoap::with([
            'therapySession.schedule.child:id,child_name,medical_record_number',
            'therapySession.schedule.therapist:id,therapist_name',
            'therapySession.substituteTherapist:id,therapist_name',
        ]);

        $therapistId = $filters['therapist_id'] ?? null;
        if ($therapistId) {
            $query->whereHas('therapySession', function ($sessionQuery) use ($therapistId) {
                $sessionQuery->where(function ($sub) use ($therapistId) {
                    $sub->where('therapy_sessions.therapist_id', $therapistId)
                        ->orWhere(function ($nested) use ($therapistId) {
                            $nested->whereNull('therapy_sessions.therapist_id')
                                ->whereHas('schedule', fn($sch) => $sch->where('therapist_id', $therapistId));
                        });
                });
            });
        }

        // Child IDs filter
        $childIds = [];
        if (!empty($filters['child_ids']) && is_array($filters['child_ids'])) {
            $childIds = array_filter($filters['child_ids']);
        } elseif (!empty($filters['child_id'])) {
            $childIds = is_array($filters['child_id']) ? $filters['child_id'] : [$filters['child_id']];
        }
        if (!empty($childIds)) {
            $query->whereHas('therapySession.schedule', function ($q) use ($childIds) {
                $q->whereIn('child_id', $childIds);
            });
        }

        // Therapy Type filter
        $therapyType = $filters['therapy_type'] ?? null;
        if ($therapyType) {
            $query->whereHas('therapySession.schedule', function ($q) use ($therapyType) {
                $q->where('therapy_type', $therapyType);
            });
        }

        // Date range filter
        $start = $filters['date_from'] ?? $filters['start_date'] ?? null;
        $end = $filters['date_to'] ?? $filters['end_date'] ?? null;
        if ($start) {
            $query->whereDate('performed_at', '>=', $start);
        }
        if ($end) {
            $query->whereDate('performed_at', '<=', $end);
        }

        return $query->orderBy('performed_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
