<?php

namespace App\Actions\TherapySession;

use App\Models\TherapySession;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class GetTherapySessionsAction
{
    /**
     * Execute the action to retrieve paginated therapy sessions with filters.
     *
     * Supported filters:
     *   - therapy_type : 'okupasi' | 'wicara' | 'paedagog' | 'fisio'
     *   - status       : 'scheduled' | 'completed' | 'cancelled' | 'pending'
     *   - search       : partial match on child_name or guardian_name
     *   - date_range   : 'today' | 'tomorrow' | 'this_week' | 'this_month'
     *   - date         : specific date string in 'Y-m-d' format (overrides date_range)
     *   - per_page     : items per page (default: 15)
     *
     * @param  array<string, mixed>  $filters
     */
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        return TherapySession::query()
            ->with([
                'schedule:id,child_id,therapist_id,therapy_type,day_of_week,start_time,end_time,status',
                'schedule.child:id,family_id,child_name,child_birth_date,child_gender,medical_record_number',
                'schedule.child.family.guardians:id,family_id,guardian_name,guardian_phone,guardian_type',
                'schedule.therapist:id,therapist_name',
                'substituteTherapist:id,therapist_name',
            ])
            // Filter by therapy_type (via related schedule)
            ->when($filters['therapy_type'] ?? null, function ($q, $type) {
                $q->whereHas('schedule', fn($s) => $s->where('therapy_type', $type));
            })
            // Filter by session status
            ->when($filters['status'] ?? null, fn($q, $status) => $q->where('status', $status))
            // Search by child name or guardian name
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->whereHas('schedule.child', function ($c) use ($search) {
                    $c->where('child_name', 'like', "%{$search}%");
                })->orWhereHas('schedule.child.family.guardians', function ($g) use ($search) {
                    $g->where('guardian_name', 'like', "%{$search}%");
                });
            })
            // Specific date input takes priority over date_range
            ->when($filters['date'] ?? null, fn($q, $date) => $q->whereDate('session_date', $date))
            ->when(
                !isset($filters['date']) && isset($filters['date_range']),
                function ($q) use ($filters) {
                    $this->applyDateRangeFilter($q, $filters['date_range']);
                }
            )
            ->orderBy('session_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);
    }

    /**
     * Apply a named date-range filter to the query.
     */
    private function applyDateRangeFilter($query, string $range): void
    {
        $now = Carbon::now();

        match ($range) {
            'today'      => $query->whereDate('session_date', $now->toDateString()),
            'tomorrow'   => $query->whereDate('session_date', $now->copy()->addDay()->toDateString()),
            'this_week'  => $query->whereBetween('session_date', [
                $now->copy()->startOfWeek()->toDateString(),
                $now->copy()->endOfWeek()->toDateString(),
            ]),
            'this_month' => $query->whereYear('session_date', $now->year)
                                  ->whereMonth('session_date', $now->month),
            default      => null,
        };
    }
}
