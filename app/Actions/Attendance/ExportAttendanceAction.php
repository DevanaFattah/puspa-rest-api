<?php

namespace App\Actions\Attendance;

use App\Models\TherapySession;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ExportAttendanceAction
{
    /**
     * Retrieve all therapy sessions matching export criteria.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, TherapySession>
     */
    private function fetchSessions(array $filters = []): Collection
    {
        $query = TherapySession::query()
            ->with([
                'schedule.child.family.guardians',
                'schedule.therapist',
                'substituteTherapist',
                'rescheduleOriginal',
            ]);

        // Filter by child_id (single ULID, comma-separated, or array)
        if (!empty($filters['child_id'])) {
            $childIds = is_array($filters['child_id']) ? $filters['child_id'] : [$filters['child_id']];
            if (count($childIds) === 1 && is_string($childIds[0]) && str_contains($childIds[0], ',')) {
                $childIds = array_map('trim', explode(',', $childIds[0]));
            }

            $query->whereHas('schedule', function ($q) use ($childIds) {
                $q->whereIn('child_id', $childIds);
            });
        }

        // Filter by therapist_id (substitute or regular schedule therapist)
        if (!empty($filters['therapist_id'])) {
            $therapistIds = is_array($filters['therapist_id']) ? $filters['therapist_id'] : [$filters['therapist_id']];
            if (count($therapistIds) === 1 && is_string($therapistIds[0]) && str_contains($therapistIds[0], ',')) {
                $therapistIds = array_map('trim', explode(',', $therapistIds[0]));
            }

            $query->where(function ($sub) use ($therapistIds) {
                $sub->whereIn('therapy_sessions.therapist_id', $therapistIds)
                    ->orWhere(function ($nested) use ($therapistIds) {
                        $nested->whereNull('therapy_sessions.therapist_id')
                            ->whereHas('schedule', fn($sch) => $sch->whereIn('therapist_id', $therapistIds));
                    });
            });
        }

        // Filter by therapy_type
        if (!empty($filters['therapy_type'])) {
            $type = $filters['therapy_type'];
            $types = in_array($type, ['fisio', 'fisioterapi']) ? ['fisio', 'fisioterapi'] : [$type];

            $query->whereHas('schedule', function ($q) use ($types) {
                $q->whereIn('therapy_type', $types);
            });
        }

        // Filter by status if requested
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply period filter (daily, weekly, monthly, custom)
        $this->applyPeriodFilter($query, $filters);

        $query->orderBy('session_date', 'asc')
            ->orderBy('start_time', 'asc');

        return $query->get();
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
            $query->whereDate('session_date', $date);
            return;
        }

        // 2. Weekly: number of week
        if ($periodType === 'weekly' || (!empty($filters['week']) && !$periodType)) {
            $week = (int) ($filters['week'] ?? Carbon::now()->isoWeek());
            $year = !empty($filters['year']) ? (int) $filters['year'] : (int) Carbon::now()->year;

            $date        = Carbon::now()->setISODate($year, $week);
            $startOfWeek = $date->copy()->startOfWeek()->toDateString();
            $endOfWeek   = $date->copy()->endOfWeek()->toDateString();

            $query->whereBetween('session_date', [$startOfWeek, $endOfWeek]);
            return;
        }

        // 3. Monthly: specific month and year
        if ($periodType === 'monthly' || (!empty($filters['month']) && !$periodType && empty($filters['week']))) {
            $month = (int) ($filters['month'] ?? Carbon::now()->month);
            $year  = (int) ($filters['year'] ?? Carbon::now()->year);

            $query->whereYear('session_date', $year)
                ->whereMonth('session_date', $month);
            return;
        }

        // 4. Custom: date_from and/or date_to
        if ($periodType === 'custom' || !empty($filters['date_from']) || !empty($filters['date_to'])) {
            if (!empty($filters['date_from'])) {
                $query->whereDate('session_date', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $query->whereDate('session_date', '<=', $filters['date_to']);
            }
        }
    }

    /**
     * Normalize session status into attendance report status:
     * hadir, izin, sakit, alpa, reschedule, pending
     */
    private function normalizeStatus(TherapySession $session): string
    {
        if ($session->rescheduleOriginal !== null) {
            return 'reschedule';
        }

        return $session->status;
    }

    /**
     * Calculate stats array (total, hadir, izin, sakit, alpa, reschedule, attendance_rate)
     *
     * @param \Illuminate\Support\Collection|array $items
     * @return array<string, mixed>
     */
    private function calculateStats($items): array
    {
        $collection = collect($items);
        $total = $collection->count();
        $hadir = $collection->filter(fn($i) => ($i['status'] ?? '') === 'hadir')->count();
        $izin = $collection->filter(fn($i) => ($i['status'] ?? '') === 'izin')->count();
        $sakit = $collection->filter(fn($i) => ($i['status'] ?? '') === 'sakit')->count();
        $alpa = $collection->filter(fn($i) => ($i['status'] ?? '') === 'alpa')->count();
        $reschedule = $collection->filter(fn($i) => ($i['status'] ?? '') === 'reschedule')->count();

        $effectiveTotal = $total - $reschedule;
        $rate = $effectiveTotal > 0 ? round(($hadir / $effectiveTotal) * 100) : 0;

        return [
            'total'           => $total,
            'hadir'           => $hadir,
            'izin'            => $izin,
            'sakit'           => $sakit,
            'alpa'            => $alpa,
            'reschedule'      => $reschedule,
            'attendance_rate' => $rate . '%',
        ];
    }

    /**
     * Retrieve matching attendance records, format them for the export Blade template,
     * and construct the DomPDF instance.
     *
     * @param User $user
     * @param array<string, mixed> $filters
     * @return array{pdf: \Barryvdh\DomPDF\PDF, file_name: string}
     */
    public function execute(User $user, array $filters = []): array
    {
        $sessions = $this->fetchSessions($filters);

        if ($sessions->isEmpty()) {
            abort(404, 'Data presensi sesi terapi tidak ditemukan untuk filter yang dipilih.');
        }

        $typeLabels = [
            'okupasi'     => 'Terapi Okupasi',
            'wicara'      => 'Terapi Wicara',
            'paedagog'    => 'Paedagog',
            'fisio'       => 'Fisioterapi',
            'fisioterapi' => 'Fisioterapi',
        ];

        $children = $sessions->map(fn($s) => $s->schedule?->child)->filter()->unique('id')->values();
        $therapies = $sessions->map(fn($s) => $s->schedule?->therapy_type)->filter()->unique()->values();
        $therapists = $sessions->map(fn($s) => $s->substituteTherapist ?? $s->schedule?->therapist)->filter()->unique('id')->values();

        // 1. Determine view type
        // - Single child filtered or exactly 1 child => 'single_child_multi_therapy' (Doc 2)
        // - Single therapy filtered or exactly 1 therapy => 'single_therapy_multi_children' (Doc 1)
        // - Otherwise => 'multi_children_multi_therapy' (Doc 3)
        $isSingleChild = !empty($filters['child_id']) || $children->count() === 1;
        $isSingleTherapy = !empty($filters['therapy_type']) || $therapies->count() === 1;

        $viewType = match (true) {
            $isSingleChild   => 'single_child_multi_therapy',
            $isSingleTherapy => 'single_therapy_multi_children',
            default          => 'multi_children_multi_therapy',
        };

        // 2. Format individual sessions
        $formattedSessions = $sessions->map(function (TherapySession $session) use ($typeLabels) {
            $schedule = $session->schedule;
            $child = $schedule?->child;
            $therapist = $session->substituteTherapist ?? $schedule?->therapist;
            $status = $this->normalizeStatus($session);

            $rawTime = $session->start_time ?: $schedule?->start_time;
            $timeFormatted = $rawTime ? str_replace(':', '.', substr($rawTime, 0, 5)) : '-';

            return [
                'session_id'         => $session->id,
                'date'               => $session->session_date ? Carbon::parse($session->session_date)->format('d/m/Y') : '-',
                'time'               => $timeFormatted,
                'child_id'           => $schedule?->child_id,
                'child_name'         => $child?->child_name ?? '-',
                'medical_record_no'  => $child?->medical_record_number ?? '-',
                'therapist_id'       => $therapist?->id,
                'therapist_name'     => $therapist?->therapist_name ?? '-',
                'therapy_type_raw'   => $schedule?->therapy_type ?? '',
                'therapy_type_label' => $typeLabels[$schedule?->therapy_type] ?? 'Terapi',
                'status'             => $status,
                'method'             => $session->checked_in_at ? 'Scan QR' : 'Manual',
                'notes'              => !empty($session->notes) ? $session->notes : '–',
            ];
        })->values()->toArray();

        // 3. Stats calculations
        $stats = $this->calculateStats($formattedSessions);

        // 4. Period label
        $periodLabel = match ($filters['period_type'] ?? null) {
            'daily'   => !empty($filters['date']) ? 'Harian · ' . Carbon::parse($filters['date'])->format('d/m/Y') : 'Harian · ' . now()->format('d/m/Y'),
            'weekly'  => 'Mingguan · Minggu ke-' . ($filters['week'] ?? now()->isoWeek()) . ' Tahun ' . ($filters['year'] ?? now()->year),
            'monthly' => 'Bulanan · ' . Carbon::create($filters['year'] ?? now()->year, $filters['month'] ?? now()->month, 1)->translatedFormat('F Y'),
            'custom'  => (!empty($filters['date_from']) && !empty($filters['date_to']))
                ? 'Custom · ' . Carbon::parse($filters['date_from'])->format('d/m/Y') . " –\n" . Carbon::parse($filters['date_to'])->format('d/m/Y')
                : 'Custom',
            default   => (!empty($filters['date_from']) && !empty($filters['date_to']))
                ? 'Custom · ' . Carbon::parse($filters['date_from'])->format('d/m/Y') . " –\n" . Carbon::parse($filters['date_to'])->format('d/m/Y')
                : 'Semua Periode',
        };

        // 5. Total data label
        $totalDataLabel = match ($viewType) {
            'single_therapy_multi_children' => count($formattedSessions) . ' jadwal · ' . $children->count() . ' anak',
            'single_child_multi_therapy'    => count($formattedSessions) . ' jadwal · ' . $therapists->count() . ' terapis',
            default                         => count($formattedSessions) . ' jadwal',
        };

        $downloadedBy = $user?->admin?->admin_name
            ?? $user?->therapist?->therapist_name
            ?? $user?->username
            ?? 'Admin Puspa';

        $roleName = $user->hasRole('owner') ? 'Owner' : ($user->hasRole('admin') ? 'Administrator' : '');

        $filterData = [
            'child_name'         => $children->count() === 1 ? ($children->first()->child_name ?? '-') : 'Semua Anak',
            'therapist_name'     => $therapists->count() === 1 ? ($therapists->first()->therapist_name ?? '-') : 'Semua Terapis',
            'therapy_type'       => $therapies->count() === 1 ? ($typeLabels[$therapies->first()] ?? '-') : 'Semua Tipe Terapi',
            'period'             => $periodLabel,
            'total_data'         => $totalDataLabel,
            'downloaded_by'      => $downloadedBy,
            'downloaded_by_role' => $roleName,
        ];

        // 6. Patient info (for View 2: single_child_multi_therapy)
        $patientData = [];
        if ($isSingleChild && $children->isNotEmpty()) {
            $child = $children->first();
            $guardian = $child?->family?->guardians?->first();
            $guardianText = $guardian
                ? ($guardian->guardian_name . ($guardian->relationship_with_child ? ' (' . $guardian->relationship_with_child . ')' : ''))
                : '-';

            $ageText = '-';
            if (!empty($child?->child_birth_date)) {
                $diff = Carbon::parse($child->child_birth_date)->diff(now());
                $ageText = $diff->y . ' Tahun ' . $diff->m . ' Bulan';
            }

            $patientData = [
                'name'                  => $child?->child_name ?? '-',
                'medical_record_number' => $child?->medical_record_number ?? '-',
                'age'                   => $ageText,
                'guardian'              => $guardianText,
                'school'                => $child?->child_school ?? '-',
                'therapy_type_label'    => $therapies->count() . ' tipe terapi',
            ];
        }

        // 7. View-specific summaries and totals
        $summaryChildren = [];
        $summaryTherapies = [];
        $totals = [];

        if ($viewType === 'single_therapy_multi_children') {
            // Group by child
            $byChild = collect($formattedSessions)->groupBy('child_id');
            foreach ($byChild as $cId => $cSessions) {
                $cStats = $this->calculateStats($cSessions);
                $first = $cSessions->first();
                $summaryChildren[] = array_merge([
                    'name'                  => $first['child_name'] ?? '-',
                    'medical_record_number' => $first['medical_record_no'] ?? '-',
                ], $cStats);
            }

            $totals = array_merge($stats, [
                'children_count' => $children->count(),
            ]);
        } elseif ($viewType === 'single_child_multi_therapy') {
            // Group by therapy type
            $byTherapy = collect($formattedSessions)->groupBy('therapy_type_raw');
            foreach ($byTherapy as $thType => $thSessions) {
                $thStats = $this->calculateStats($thSessions);

                $therapistGroup = $thSessions->groupBy('therapist_name');
                $therapistList = [];
                foreach ($therapistGroup as $tName => $tSessions) {
                    $therapistList[] = [
                        'name'  => $tName,
                        'count' => $tSessions->count(),
                    ];
                }

                $summaryTherapies[] = array_merge([
                    'therapy_type_label' => $typeLabels[$thType] ?? ucfirst($thType),
                    'therapy_type_raw'   => $thType,
                    'therapists'         => $therapistList,
                ], $thStats);
            }

            $totals = array_merge($stats, [
                'therapists_count' => $therapists->count() . ' terapis',
            ]);
        } else {
            // multi_children_multi_therapy: group by child, and inside list therapy types
            $byChild = collect($formattedSessions)->groupBy('child_id');
            foreach ($byChild as $cId => $cSessions) {
                $cStats = $this->calculateStats($cSessions);
                $first = $cSessions->first();

                $cTherapies = $cSessions->groupBy('therapy_type_raw');
                $thList = [];
                foreach ($cTherapies as $thType => $group) {
                    $thList[] = [
                        'name'         => $typeLabels[$thType] ?? ucfirst($thType),
                        'count'        => $group->count(),
                        'therapy_type' => $thType,
                    ];
                }

                $summaryChildren[] = array_merge([
                    'name'      => $first['child_name'] ?? '-',
                    'therapies' => $thList,
                ], $cStats);
            }

            $totals = $stats;
        }

        // 8. Assemble view payload
        $payload = [
            'view_type'         => $viewType,
            'print_date'        => now()->format('d/m/Y'),
            'filter'            => $filterData,
            'patient'           => $patientData,
            'stats'             => $stats,
            'summary_children'  => $summaryChildren,
            'summary_therapies' => $summaryTherapies,
            'totals'            => $totals,
            'sessions'          => $formattedSessions,
        ];

        // 9. File naming
        $periodLabelFile = match ($filters['period_type'] ?? null) {
            'daily'   => !empty($filters['date']) ? Carbon::parse($filters['date'])->format('d-m-Y') : now()->format('d-m-Y'),
            'weekly'  => 'minggu-' . ($filters['week'] ?? now()->isoWeek()) . '-tahun-' . ($filters['year'] ?? now()->year),
            'monthly' => Carbon::create($filters['year'] ?? now()->year, $filters['month'] ?? now()->month, 1)->translatedFormat('M-Y'),
            'custom'  => (!empty($filters['date_from']) && !empty($filters['date_to']))
                ? Carbon::parse($filters['date_from'])->format('d-m-Y') . '_' . Carbon::parse($filters['date_to'])->format('d-m-Y')
                : 'custom',
            default   => 'semua_periode',
        };

        $viewTypeFile = match ($viewType) {
            'single_therapy_multi_children' => 'rekap_' . Str::snake($filterData['therapy_type']),
            'single_child_multi_therapy'    => Str::snake($filterData['child_name']) . '_kehadiran',
            default                         => 'rekap_semua_kehadiran',
        };

        $fileName = 'laporan_kehadiran_' . $periodLabelFile . '_' . $viewTypeFile . '.pdf';

        $pdf = Pdf::loadView('exports.kehadiran.report', $payload)
            ->setOption('defaultFont', 'sans-serif')
            ->setOption('isPhpEnabled', true)
            ->setPaper('a4', 'landscape');

        return [
            'pdf'       => $pdf,
            'file_name' => $fileName,
        ];
    }
}
