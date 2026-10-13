<?php

namespace App\Actions\TherapySoap;

use App\Models\TherapySoap;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class ExportTherapySoapsAction
{
    /**
     * Retrieve all therapy SOAP notes matching export criteria.
     *
     * Supported filters:
     *   - therapist_id : therapist ULID (required to scope records)
     *   - child_id     : string or array of child ULIDs
     *   - period_type  : 'daily' | 'weekly' | 'monthly' | 'custom'
     *   - date         : 'Y-m-d' for daily
     *   - week         : integer (1-53) for weekly
     *   - month        : integer (1-12) for monthly or week-of-month
     *   - year         : integer (e.g. 2026)
     *   - date_from    : 'Y-m-d' for custom range
     *   - date_to      : 'Y-m-d' for custom range
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, TherapySoap>
     */
    public function execute(array $filters = []): Collection
    {
        $therapistId = $filters['therapist_id'] ?? null;

        $query = TherapySoap::query()
            ->with([
                'therapySession:id,schedule_id,session_number,session_date,therapist_id,status',
                'therapySession.schedule:id,child_id,therapist_id,therapy_type,day_of_week,start_time,end_time,total_meetings',
                'therapySession.schedule.child:id,family_id,child_name,child_birth_place,child_birth_date,child_gender,child_school,child_address,medical_record_number',
                'therapySession.schedule.child.family.guardians:id,family_id,guardian_name,guardian_phone,guardian_type,relationship_with_child',
                'therapySession.schedule.therapist:id,therapist_name',
                'therapySession.substituteTherapist:id,therapist_name',
            ]);

        // Scope to therapist (substitute or regular schedule therapist)
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

        // Filter by child_id (can be string, comma-separated string, or array)
        if (!empty($filters['child_id'])) {
            $childIds = is_array($filters['child_id']) ? $filters['child_id'] : [$filters['child_id']];
            if (count($childIds) === 1 && is_string($childIds[0]) && str_contains($childIds[0], ',')) {
                $childIds = array_map('trim', explode(',', $childIds[0]));
            }

            $query->whereHas('therapySession.schedule', function ($q) use ($childIds) {
                $q->whereIn('child_id', $childIds);
            });
        }

        // Apply period filter (daily, weekly, monthly, custom)
        $this->applyPeriodFilter($query, $filters);

        return $query
            ->orderBy('performed_at', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Apply period filtering based on period_type or individual parameters.
     */
    private function applyPeriodFilter($query, array $filters): void
    {
        $periodType = $filters['period_type'] ?? null;

        // 1. Daily: specific date
        if ($periodType === 'daily' || (!empty($filters['date']) && !$periodType)) {
            $date = $filters['date'] ?? Carbon::today()->toDateString();
            $query->whereDate('performed_at', $date);
            return;
        }

        // 2. Weekly: number of week
        if ($periodType === 'weekly' || (!empty($filters['week']) && !$periodType)) {
            $week  = (int) ($filters['week'] ?? Carbon::now()->isoWeek());
            $year  = !empty($filters['year']) ? (int) $filters['year'] : (int) Carbon::now()->year;
            $month = !empty($filters['month']) ? (int) $filters['month'] : null;

            $this->applyWeeklyFilter($query, $week, $year, $month);
            return;
        }

        // 3. Monthly: specific month and year
        if ($periodType === 'monthly' || (!empty($filters['month']) && !$periodType && empty($filters['week']))) {
            $month = (int) ($filters['month'] ?? Carbon::now()->month);
            $year  = (int) ($filters['year'] ?? Carbon::now()->year);

            $query->whereYear('performed_at', $year)
                  ->whereMonth('performed_at', $month);
            return;
        }

        // 4. Custom: date_from and/or date_to
        if ($periodType === 'custom' || !empty($filters['date_from']) || !empty($filters['date_to'])) {
            if (!empty($filters['date_from'])) {
                $query->whereDate('performed_at', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $query->whereDate('performed_at', '<=', $filters['date_to']);
            }
        }
    }

    /**
     * Apply weekly filter by ISO week (1-53) or week of month (1-5 if month is provided).
     */
    private function applyWeeklyFilter($query, int $week, int $year, ?int $month = null): void
    {
        if ($month !== null && $week >= 1 && $week <= 5) {
            // Week of month
            $startOfMonth = Carbon::create($year, $month, 1)->startOfDay();
            $startOfWeek  = $startOfMonth->copy()->addWeeks($week - 1)->startOfWeek();
            $endOfWeek    = $startOfWeek->copy()->endOfWeek();

            $query->whereBetween('performed_at', [
                $startOfWeek->startOfDay()->toDateTimeString(),
                $endOfWeek->endOfDay()->toDateTimeString(),
            ]);
        } else {
            // ISO week of year (1-53)
            $date        = Carbon::now()->setISODate($year, $week);
            $startOfWeek = $date->copy()->startOfWeek();
            $endOfWeek   = $date->copy()->endOfWeek();

            $query->whereBetween('performed_at', [
                $startOfWeek->startOfDay()->toDateTimeString(),
                $endOfWeek->endOfDay()->toDateTimeString(),
            ]);
        }
    }
}
