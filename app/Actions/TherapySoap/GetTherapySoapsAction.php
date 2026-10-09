<?php

namespace App\Actions\TherapySoap;

use App\Models\TherapySoap;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class GetTherapySoapsAction
{
    /**
     * Execute the action to retrieve paginated therapy SOAP notes with filters.
     *
     * Supported filters:
     *   - therapy_type : 'okupasi' | 'wicara' | 'paedagog' | 'fisio'
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

        return TherapySoap::query()
            ->with([
                'therapySession:id,schedule_id,session_number,session_date,therapist_id,status',
                'therapySession.schedule:id,child_id,therapist_id,therapy_type,day_of_week,start_time,end_time,total_meetings',
                'therapySession.schedule.child:id,family_id,child_name,child_birth_place,child_birth_date,child_gender,child_school,child_address,medical_record_number',
                'therapySession.schedule.child.family.guardians:id,family_id,guardian_name,guardian_phone,guardian_type,relationship_with_child',
                'therapySession.schedule.therapist:id,therapist_name',
                'therapySession.substituteTherapist:id,therapist_name',
            ])
            // Filter by therapy_type (via related schedule)
            ->when($filters['therapy_type'] ?? null, function ($q, $type) {
                $q->whereHas('therapySession.schedule', fn($s) => $s->where('therapy_type', $type));
            })
            // Search by child name or guardian name
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->whereHas('therapySession.schedule.child', function ($c) use ($search) {
                    $c->where('child_name', 'like', "%{$search}%");
                })->orWhereHas('therapySession.schedule.child.family.guardians', function ($g) use ($search) {
                    $g->where('guardian_name', 'like', "%{$search}%");
                });
            })
            // Specific date input takes priority over date_range (filter on performed_at)
            ->when($filters['date'] ?? null, fn($q, $date) => $q->whereDate('performed_at', $date))
            ->when(
                !isset($filters['date']) && isset($filters['date_range']),
                function ($q) use ($filters) {
                    $this->applyDateRangeFilter($q, $filters['date_range']);
                }
            )
            ->orderBy('performed_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Apply a named date-range filter to the query (based on performed_at).
     */
    private function applyDateRangeFilter($query, string $range): void
    {
        $now = Carbon::now();

        match ($range) {
            'today'      => $query->whereDate('performed_at', $now->toDateString()),
            'tomorrow'   => $query->whereDate('performed_at', $now->copy()->addDay()->toDateString()),
            'this_week'  => $query->whereBetween('performed_at', [
                $now->copy()->startOfWeek()->toDateString(),
                $now->copy()->endOfWeek()->toDateString(),
            ]),
            'this_month' => $query->whereYear('performed_at', $now->year)
                                  ->whereMonth('performed_at', $now->month),
            default      => null,
        };
    }
}
