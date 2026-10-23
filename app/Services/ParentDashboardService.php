<?php

namespace App\Services;

use App\Models\Child;
use App\Models\Observation;
use App\Models\Assessment;
use App\Models\AssessmentDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ParentDashboardService
{
    /**
     * Get dashboard statistics for parent
     */
    public function getStats(string $familyId): array
    {
        $now = Carbon::now();
        $lastMonth = $now->copy()->subMonth();

        $children = Child::where('family_id', $familyId)->get();

        if ($children->isEmpty()) {
            return $this->getEmptyStats();
        }

        $childIds = $children->pluck('id');

        $totalChildren = $children->count();
        $totalChildrenLastMonth = $children->filter(fn($c) => $c->created_at <= $lastMonth)->count();

        $obsStats = Observation::whereIn('child_id', $childIds)
            ->selectRaw('
            COUNT(*) as total,
            COUNT(CASE WHEN created_at <= ? THEN 1 END) as last_month
        ', [$lastMonth])
            ->first();

        $assStats = Assessment::whereIn('child_id', $childIds)
            ->selectRaw('
                COUNT(*) as total,
                COUNT(CASE WHEN created_at <= ? THEN 1 END) as last_month
            ', [$lastMonth])
            ->first();

        return [
            'total_children' => $totalChildren,
            'total_children_percentage' => $this->calculatePercentage($totalChildren, $totalChildrenLastMonth),

            'total_observations' => $obsStats->total,
            'total_observations_percentage' => $this->calculatePercentage($obsStats->total, $obsStats->last_month),

            'total_assessments' => $assStats->total,
            'total_assessments_percentage' => $this->calculatePercentage($assStats->total, $assStats->last_month),
        ];
    }

    /**
     * Get chart data for trend visualization (last 12 months)
     */
    public function getChartData(string $familyId): array
    {
        $children = Child::where('family_id', $familyId)->select('id', 'created_at')->get();

        if ($children->isEmpty()) {
            return [];
        }

        $childIds = $children->pluck('id');

        $start = Carbon::now()->subMonths(11)->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $observations = Observation::whereIn('child_id', $childIds)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->pluck('count', 'month');

        $assessments = Assessment::whereIn('child_id', $childIds)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->pluck('count', 'month');

        return collect(range(11, 0))->map(function ($i) use ($children, $observations, $assessments) {
            $month = Carbon::now()->subMonths($i);
            $endOfMonth = $month->copy()->endOfMonth();

            $cumulativeChildren = $children->filter(fn($c) => $c->created_at <= $endOfMonth)->count();
            $monthKey = $month->format('Y-m');

            return [
                'month' => $month->translatedFormat('M Y'),
                'total_children' => $cumulativeChildren,
                'total_observations' => $observations[$monthKey] ?? 0,
                'total_assessments' => $assessments[$monthKey] ?? 0,
            ];
        })->toArray();
    }

    /**
     * Get upcoming schedules (observations + assessments)
     */
    public function getUpcomingSchedules(string $familyId, array $filters = [])
    {
        $search = $filters['search'] ?? null;
        $type = $filters['type'] ?? 'all';

        $obsQuery = DB::table('observations as o')
            ->join('children as c', 'o.child_id', '=', 'c.id')
            ->leftJoin('therapists as t', 'o.therapist_id', '=', 't.id')
            ->select([
                'o.id as id',
                DB::raw("'observation' as source_type"),
                DB::raw("'observation' as sub_type"),
                'o.scheduled_date',
                'o.status',
                'c.child_name',
                't.therapist_name',
            ])
            ->where('c.family_id', $familyId)
            ->where('o.status', 'scheduled')
            ->where('o.scheduled_date', '>=', now());

        if ($search) {
            $obsQuery->where('c.child_name', 'like', "%{$search}%");
        }

        $assQuery = DB::table('assessments as a')
            ->join('assessment_details as ad', 'a.id', '=', 'ad.assessment_id')
            ->join('children as c', 'a.child_id', '=', 'c.id')
            ->leftJoin('therapists as t', 'ad.therapist_id', '=', 't.id')
            ->select([
                'a.id as id',
                DB::raw("'assessment' as source_type"),
                'ad.type as sub_type',
                'a.scheduled_date',
                'a.status',
                'c.child_name',
                't.therapist_name',
            ])
            ->where('c.family_id', $familyId)
            ->where('a.status', 'scheduled')
            ->where('a.scheduled_date', '>=', now());

        if ($search) {
            $assQuery->where('c.child_name', 'like', "%{$search}%");
        }

        $finalQuery = null;

        if ($type === 'observation') {
            $finalQuery = $obsQuery;
        } elseif ($type === 'assessment') {
            $finalQuery = $assQuery;
        } else {
            $finalQuery = $obsQuery->union($assQuery);
        }

        $schedules = $finalQuery
            ->orderBy('scheduled_date', 'asc')
            ->get();

        return $schedules->map(function ($item) {
            return [
                'id' => $item->id,
                'type' => $item->source_type,
                'type_label' => $this->getLabel($item->source_type, $item->sub_type),
                'child_name' => $item->child_name,
                'service' => $this->getLabel($item->source_type, $item->sub_type),
                'status' => $item->status,
                'status_label' => 'Terjadwal',
                'scheduled_date' => $item->scheduled_date,
                'date' => Carbon::parse($item->scheduled_date)->format('d/m/Y'),
                'time' => Carbon::parse($item->scheduled_date)->format('H:i'),
                'therapist' => $item->therapist_name ?? '-',
            ];
        });
    }

    private function getLabel(string $sourceType, string $subType): string
    {
        if ($sourceType === 'observation') {
            return 'Observasi';
        }

        $labels = [
            'umum' => 'Assessment Umum',
            'fisio' => 'Assessment Fisio',
            'okupasi' => 'Assessment Okupasi',
            'wicara' => 'Assessment Wicara',
            'paedagog' => 'Assessment Paedagog',
        ];

        return $labels[$subType] ?? 'Assessment';
    }

    /**
     * Calculate percentage change
     */
    private function calculatePercentage(int $current, int $previous): array
    {
        if ($previous == 0) {
            return [
                'value' => $current > 0 ? 100 : 0,
                'direction' => $current > 0 ? 'up' : 'neutral',
            ];
        }

        $change = (($current - $previous) / $previous) * 100;

        return [
            'value' => abs(round($change, 1)),
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'neutral'),
        ];
    }

    /**
     * Get today's therapy sessions for parent's children with QR code token
     */
    public function getTodayTherapySessions(string $familyId): array
    {
        $children = Child::where('family_id', $familyId)->pluck('id');

        if ($children->isEmpty()) {
            return [];
        }

        $sessions = \App\Models\TherapySession::with([
            'schedule.child',
            'schedule.therapist',
            'substituteTherapist'
        ])
        ->whereHas('schedule', function ($q) use ($children) {
            $q->whereIn('child_id', $children);
        })
        ->whereDate('session_date', Carbon::today())
        ->get();

        $attendanceService = app(AttendanceService::class);

        return $sessions->map(function ($session) use ($attendanceService) {
            $token = $attendanceService->generateQrToken($session);
            $therapist = $session->substituteTherapist ?? $session->schedule?->therapist;

            return [
                'session_id' => $session->id,
                'session_number' => $session->session_number,
                'session_date' => $session->session_date?->format('Y-m-d'),
                'child_id' => $session->schedule?->child_id,
                'child_name' => $session->schedule?->child?->child_name ?? '-',
                'therapy_type' => $session->schedule?->therapy_type ?? '-',
                'therapist_name' => $therapist?->therapist_name ?? '-',
                'start_time' => $session->schedule?->start_time ? substr($session->schedule->start_time, 0, 5) : '-',
                'end_time' => $session->schedule?->end_time ? substr($session->schedule->end_time, 0, 5) : '-',
                'status' => $session->status,
                'checked_in_at' => $session->checked_in_at?->toIso8601String(),
                'attendance_token' => $token,
            ];
        })->toArray();
    }

    /**
     * Get therapy history for parent's children (sessions with attendance & SOAP).
     */
    public function getTherapyHistory(string $familyId, array $filters = []): array
    {
        $children = Child::where('family_id', $familyId)->pluck('id');

        if ($children->isEmpty()) {
            return [];
        }

        $query = \App\Models\TherapySession::with([
            'schedule.child',
            'schedule.therapist',
            'substituteTherapist',
            'therapySoap',
        ])
        ->whereHas('schedule', function ($q) use ($children) {
            $q->whereIn('child_id', $children);
        })
        ->whereIn('status', ['present', 'completed', 'cancelled', 'sick', 'excused']);

        if (!empty($filters['child_id'])) {
            $query->whereHas('schedule', fn($q) => $q->where('child_id', $filters['child_id']));
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('session_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('session_date', '<=', $filters['end_date']);
        }

        $sessions = $query->orderBy('session_date', 'desc')->get();

        $therapyTypeLabels = [
            'okupasi'  => 'Terapi Okupasi',
            'wicara'   => 'Terapi Wicara',
            'paedagog' => 'PLB (Paedagog)',
            'fisio'    => 'Fisioterapi',
        ];

        return $sessions->map(function ($session) use ($therapyTypeLabels) {
            $therapist = $session->substituteTherapist ?? $session->schedule?->therapist;
            $rawType = strtolower($session->schedule?->therapy_type ?? 'wicara');
            $soap = $session->therapySoap;

            $duration = '60 menit';
            if ($session->schedule?->start_time && $session->schedule?->end_time) {
                $start = Carbon::parse($session->schedule->start_time);
                $end = Carbon::parse($session->schedule->end_time);
                $duration = $start->diffInMinutes($end) . ' menit';
            }

            return [
                'id' => $session->id,
                'namaAnak' => $session->schedule?->child?->child_name ?? '-',
                'jenisTerapi' => $therapyTypeLabels[$rawType] ?? ucfirst($rawType),
                'tanggal' => $session->session_date ? Carbon::parse($session->session_date)->format('d/m/Y') : '-',
                'tanggalRaw' => $session->session_date ? Carbon::parse($session->session_date)->format('Y-m-d') : '-',
                'jamMulai' => $session->schedule?->start_time ? substr(str_replace(':', '.', $session->schedule->start_time), 0, 5) : '09.00',
                'durasi' => $duration,
                'terapis' => $therapist?->therapist_name ?? '-',
                'diagnosisTerapi' => $soap?->therapy_diagnosis ?? 'Pemeriksaan & intervensi terapi rutin',
                'judulSesi' => "Sesi #{$session->session_number} " . ($therapyTypeLabels[$rawType] ?? ucfirst($rawType)),
                'deskripsiSingkat' => ($session->schedule?->child?->child_name ?? 'Anak') . ' • Sesi ' . ($session->status === 'present' ? 'Hadir' : ucfirst($session->status)),
                'status' => $session->status,
                'soap' => [
                    'subjektif' => $soap?->subjective ?? 'Tidak ada keluhan khusus dari orang tua.',
                    'objektif' => $soap?->objective ?? 'Anak kooperatif mengikuti seluruh rangkaian intervensi.',
                    'assessment' => $soap?->assessment ?? 'Progres intervensi berjalan positif.',
                    'plan' => $soap?->plan ?? 'Lanjutkan program stimulasi berkala.',
                    'implementasi' => $soap?->implementation ?? ($soap?->interventions ?? 'Intervensi terstruktur sesuai kurikulum terapi.'),
                    'assessmentLanjutan' => $soap?->advanced_assessment ?? 'Evaluasi respons berkelanjutan pada sesi berikutnya.',
                    'planLanjutan' => $soap?->advanced_plan ?? 'Home program disarankan dipraktikkan bersama orang tua.',
                ],
            ];
        })->toArray();
    }

    /**
     * Get empty stats structure
     */
    private function getEmptyStats(): array
    {
        return [
            'total_children' => 0,
            'total_children_percentage' => ['value' => 0, 'direction' => 'neutral'],
            'total_observations' => 0,
            'total_observations_percentage' => ['value' => 0, 'direction' => 'neutral'],
            'total_assessments' => 0,
            'total_assessments_percentage' => ['value' => 0, 'direction' => 'neutral'],
        ];
    }
}
