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
     *   - status       : 'scheduled' | 'pending' | 'rescheduled' | 'completed'
     *   - search       : partial match on child_name or guardian_name
     *   - date_range   : 'today' | 'tomorrow' | 'this_week' | 'this_month'
     *   - date         : specific date string in 'Y-m-d' format (overrides date_range)
     *   - per_page     : items per page (default: 15)
     *
     * Status filter semantics (these are virtual, not DB enum values):
     *   - scheduled    : sessions with NO soap yet (all DB statuses shown, date filter applies)
     *   - pending      : sessions with session_date < today, DB status pending|present, NO soap
     *   - rescheduled  : sessions that are the original_session_id in the reschedules table
     *   - completed    : sessions where DB status = 'present' AND a soap record exists
     *
     * @param  array<string, mixed>  $filters
     */
    public function execute(array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        $status = $filters['status'] ?? null;

        return TherapySession::query()
            ->with([
                'schedule:id,child_id,therapist_id,therapy_type,day_of_week,start_time,end_time,status',
                'schedule.child:id,family_id,child_name,child_birth_date,child_gender,medical_record_number',
                'schedule.child.family.guardians:id,family_id,guardian_name,guardian_phone,guardian_type',
                'schedule.therapist:id,therapist_name',
                'substituteTherapist:id,therapist_name',
                // Needed to check whether SOAP exists
                'soap:id,therapy_session_id',
                // Needed to detect if session was rescheduled (as original)
                'rescheduleOriginal:id,original_session_id,new_session_id,requested_by,reason',
                'rescheduleOriginal.newSession:id,session_date,therapist_id',
                'rescheduleOriginal.newSession.substituteTherapist:id,therapist_name',
            ])
            // Filter by therapy_type (via related schedule)
            ->when($filters['therapy_type'] ?? null, function ($q, $type) {
                $q->whereHas('schedule', fn($s) => $s->where('therapy_type', $type));
            })
            // Virtual status filters
            ->when($status === 'scheduled', function ($q) {
                // All sessions that do NOT yet have a SOAP record
                $q->whereDoesntHave('soap');
            })
            ->when($status === 'pending', function ($q) {
                // Overdue sessions: past date, pending/present DB status, no SOAP
                $q->whereDate('session_date', '<', Carbon::today())
                  ->whereIn('status', ['pending', 'present'])
                  ->whereDoesntHave('soap');
            })
            ->when($status === 'rescheduled', function ($q) {
                // Sessions that appear as the original in a reschedule record
                $q->whereHas('rescheduleOriginal');
            })
            ->when($status === 'completed', function ($q) {
                // Sessions where child was present AND soap has been written
                $q->where('status', 'present')
                  ->whereHas('soap');
            })
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
