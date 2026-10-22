<?php

namespace App\Actions\TherapySoap;

use App\Models\TherapySoap;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

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
    /**
     * Fetch SOAP records from DB matching the given filters.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, TherapySoap>
     */
    private function fetchSoaps(array $filters = [])
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

        // Scope to therapy type (if provided)
        if (!empty($filters['therapy_type'])) {
            $therapyType = $filters['therapy_type'];
            $query->whereHas('therapySession.schedule', function ($q) use ($therapyType) {
                $q->where('therapy_type', $therapyType);
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

        $query->orderBy('performed_at', 'asc')
              ->orderBy('created_at', 'asc');

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
            $query->whereDate('performed_at', $date);
            return;
        }

        // 2. Weekly: number of week
        if ($periodType === 'weekly' || (!empty($filters['week']) && !$periodType)) {
            $week  = (int) ($filters['week'] ?? Carbon::now()->isoWeek());
            $year  = !empty($filters['year']) ? (int) $filters['year'] : (int) Carbon::now()->year;

            $this->applyWeeklyFilter($query, $week, $year);
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
     * Apply weekly filter by ISO week (1-53)
     */
    private function applyWeeklyFilter($query, int $week, int $year, ?int $month = null): void
    {
        $date        = Carbon::now()->setISODate($year, $week);
        $startOfWeek = $date->copy()->startOfWeek();
        $endOfWeek   = $date->copy()->endOfWeek();

        $query->whereBetween('performed_at', [
            $startOfWeek->startOfDay()->toDateTimeString(),
            $endOfWeek->endOfDay()->toDateTimeString(),
        ]);
    }

    /**
     * Retrieve matching SOAP records, format them for the export Blade template,
     * and construct the DomPDF instance.
     *
     * @param array<string, mixed> $filters
     * @param \App\Models\User $user
     * @return array{pdf: PDF, view_type: string}
     */
    public function execute(User $user, array $filters = []): array
    {
        $soaps = $this->fetchSoaps($filters);

        if ($soaps->isEmpty()) {
            abort(404, 'Data catatan SOAP tidak ditemukan untuk filter yang dipilih.');
        }

        $children = $soaps->map(fn($s) => $s->therapySession?->schedule?->child)->filter()->unique('id')->values();
        $therapies = $soaps->map(fn($s) => $s->therapySession?->schedule?->therapy_type)->filter()->unique()->values();
        $therapists = $soaps->map(fn($s) => $s->therapySession?->substituteTherapist ?? $s->therapySession?->schedule?->therapist)->filter()->unique('id')->values();

        // 1. Determine view type
        $viewType = match (true) {
            $children->count() > 1  => 'all_children_all_therapy',
            $therapies->count() > 1 => 'one_child_multiple_therapy',
            default                 => 'one_child_one_therapy',
        };

        $typeLabels = [
            'okupasi'  => 'Terapi Okupasi',
            'wicara'   => 'Terapi Wicara',
            'paedagog' => 'Terapi Paedagogik',
            'fisio'    => 'Fisioterapi',
        ];

        // 2. Format individual sessions
        $formattedSessions = $soaps->map(function ($soap) use ($typeLabels) {
            $session = $soap->therapySession;
            $schedule = $session?->schedule;
            $therapist = $session?->substituteTherapist ?? $schedule?->therapist;

            $startTime = $schedule?->start_time ? Carbon::parse($schedule->start_time)->format('H.i') : '';
            $endTime   = $schedule?->end_time ? Carbon::parse($schedule->end_time)->format('H.i') : '';
            $timeFormatted = ($startTime && $endTime) ? "{$startTime} – {$endTime}" : ($startTime ?: '-');

            return [
                'session_number'      => $session?->session_number ?? 1,
                'therapy_type'        => $typeLabels[$schedule?->therapy_type] ?? 'Terapi',
                'date_formatted'      => $session?->session_date ? Carbon::parse($session->session_date)->format('d/m/Y') : '-',
                'time'                => $timeFormatted,
                'therapist_name'      => $therapist?->therapist_name ?? '-',
                'therapy_diagnosis'   => $soap->therapy_diagnosis ?? '-',
                'interventions'       => $soap->interventions ?? '-',
                'subjective'          => $soap->subjective ?? '-',
                'objective'           => $soap->objective ?? '-',
                'assessment'          => $soap->assessment ?? '-',
                'plan'                => $soap->plan ?? '-',
                'implementation'      => $soap->implementation ?? '-',
                'advanced_assessment' => $soap->advanced_assessment ?? '-',
                'advanced_plan'       => $soap->advanced_plan ?? '-',
                'child_id'            => $schedule?->child_id,
            ];
        })->values()->toArray();

        // 3. Format period label
        $periodLabel = match ($filters['period_type'] ?? null) {
            'daily'   => !empty($filters['date']) ? 'Harian · ' . Carbon::parse($filters['date'])->format('d/m/Y') : 'Harian · ' . now()->format('d/m/Y'),
            'weekly'  => 'Mingguan · Minggu ke-' . ($filters['week'] ?? now()->isoWeek()) . ' Tahun ' . ($filters['year'] ?? now()->year),
            'monthly' => 'Bulanan · ' . Carbon::create($filters['year'] ?? now()->year, $filters['month'] ?? now()->month, 1)->translatedFormat('F Y'),
            'custom'  => (!empty($filters['date_from']) && !empty($filters['date_to']))
                ? 'Custom · ' . Carbon::parse($filters['date_from'])->format('d/m/Y') . ' – ' . Carbon::parse($filters['date_to'])->format('d/m/Y')
                : 'Custom',
            default   => 'Semua Periode',
        };

        // 4. Filter metadata
        $totalDataLabel = match ($viewType) {
            'all_children_all_therapy'   => count($formattedSessions) . ' sesi · ' . $children->count() . ' anak',
            'one_child_multiple_therapy' => count($formattedSessions) . ' sesi · ' . $therapists->count() . ' terapis',
            default                      => count($formattedSessions) . ' sesi',
        };

        $downloadedBy = $user?->admin?->admin_name
            ?? $user?->therapist?->therapist_name
            ?? $user?->username
            ?? 'Admin Puspa';

        $filterData = [
            'child_name'     => $children->count() === 1 ? ($children->first()->child_name ?? '-') : 'Semua anak',
            'therapist_name' => $therapists->count() === 1 ? ($therapists->first()->therapist_name ?? '-') : 'Semua terapis',
            'therapy_type'   => $therapies->count() === 1 ? ($typeLabels[$therapies->first()] ?? '-') : 'Semua jenis terapi',
            'period'         => $periodLabel,
            'total_data'     => $totalDataLabel,
            'downloaded_by'  => $downloadedBy,
        ];

        // 5. Patient info (for single child views)
        $patientData = [];
        if ($children->count() === 1) {
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
                'therapy_type'          => $therapies->count() > 1 ? ($therapies->count() . ' jenis terapi') : ($typeLabels[$therapies->first()] ?? '-'),
            ];
        }

        // 6. Summary per therapy (for View 2: One child multiple therapy)
        $summaryTherapies = [];
        if ($viewType === 'one_child_multiple_therapy') {
            $byTherapy = $soaps->groupBy(fn($s) => $s->therapySession?->schedule?->therapy_type ?? 'Lainnya');
            foreach ($byTherapy as $type => $group) {
                $groupTherapists = $group->groupBy(fn($s) => $s->therapySession?->substituteTherapist?->therapist_name ?? $s->therapySession?->schedule?->therapist?->therapist_name ?? 'Terapis');
                $therapistsFormatted = [];
                foreach ($groupTherapists as $tName => $tSoaps) {
                    $therapistsFormatted[] = $tName . ' (' . $tSoaps->count() . ')';
                }
                $lastDate = $group->pluck('therapySession.session_date')->filter()->sortDesc()->first();

                $summaryTherapies[] = [
                    'therapy_type'    => $typeLabels[$type],
                    'therapists'      => implode("\n", $therapistsFormatted),
                    'session_count'   => $group->count(),
                    'last_session'    => $lastDate ? Carbon::parse($lastDate)->format('d/m/Y') : '-',
                    'therapist_count' => $groupTherapists->count(),
                ];
            }
        }

        // 7. Summary per child and grouped children (for View 3: All children all therapy)
        $summaryChildren = [];
        $groupedChildren = [];
        if ($viewType === 'all_children_all_therapy') {
            $byChild = $soaps->groupBy(fn($s) => $s->therapySession?->schedule?->child_id ?? 'unknown');
            foreach ($byChild as $cId => $cSoaps) {
                $child = $cSoaps->first()?->therapySession?->schedule?->child;
                $childTherapies = $cSoaps->groupBy(fn($s) => $s->therapySession?->schedule?->therapy_type ?? 'Terapi');
                $thBreakdown = [];
                foreach ($childTherapies as $tName => $tSoaps) {
                    $thBreakdown[] = $typeLabels[$tName] . ' (' . $tSoaps->count() . ')';
                }

                $summaryChildren[] = [
                    'child_name'            => $child?->child_name ?? '-',
                    'medical_record_number' => $child?->medical_record_number ?? '-',
                    'therapies'             => implode("\n", $thBreakdown),
                    'total_sessions'        => $cSoaps->count(),
                ];

                $guardian = $child?->family?->guardians?->first();
                $guardianText = $guardian
                    ? ($guardian->guardian_name . ($guardian->relationship_with_child ? ' (' . $guardian->relationship_with_child . ')' : ''))
                    : '-';
                $ageText = '-';
                if (!empty($child?->child_birth_date)) {
                    $diff = Carbon::parse($child->child_birth_date)->diff(now());
                    $ageText = $diff->y . ' Tahun ' . $diff->m . ' Bulan';
                }

                $childSessions = array_values(array_filter($formattedSessions, fn($s) => $s['child_id'] === $cId));

                $groupedChildren[] = [
                    'child' => [
                        'name'                  => $child?->child_name ?? '-',
                        'medical_record_number' => $child?->medical_record_number ?? '-',
                        'age'                   => $ageText,
                        'guardian'              => $guardianText,
                        'school'                => $child?->child_school ?? '-',
                    ],
                    'sessions' => $childSessions,
                ];
            }
        }

        // 8. Signature (for View 1)
        $signature = [
            'city'           => 'Surakarta',
            'date'           => now()->format('d/m/Y'),
            'therapist_name' => $therapists->first()?->therapist_name ?? ($user?->therapist?->therapist_name ?? $user?->admin?->admin_name ?? $user?->username ?? 'Terapis'),
            'therapy_type'   => $therapies->first() ?? '',
        ];

        // 9. Assemble payload for Blade template
        $payload = [
            'view_type'         => $viewType,
            'print_date'        => now()->format('d/m/Y'),
            'filter'            => $filterData,
            'patient'           => $patientData,
            'sessions'          => $formattedSessions,
            'summary_therapies' => $summaryTherapies,
            'summary_totals'    => [
                'therapists'     => $therapists->count() . ' terapis',
                'sessions'       => count($formattedSessions) . ' sesi',
                'total_sessions' => count($formattedSessions) . ' sesi',
            ],
            'summary_children'  => $summaryChildren,
            'grouped_children'  => $groupedChildren,
            'signature'         => $signature,
        ];

        $periodLabelFile = match ($filters['period_type'] ?? null) {
            'daily'   => !empty($filters['date']) ? Carbon::parse($filters['date'])->format('d-m-Y') : now()->format('d-m-Y'),
            'weekly'  => 'minggu-' . ($filters['week'] ?? now()->isoWeek()) . '-tahun-' . ($filters['year'] ?? now()->year),
            'monthly' => Carbon::create($filters['year'] ?? now()->year, $filters['month'] ?? now()->month, 1)->translatedFormat('M-Y'),
            'custom'  => (!empty($filters['date_from']) && !empty($filters['date_to']))
                ? Carbon::parse($filters['date_from'])->format('d-m-Y') . '_' . Carbon::parse($filters['date_to'])->format('d-m-Y')
                : 'Custom',
            default   => 'semua_periode',
        };

        $viewTypeFile = match ($viewType) {
            'all_children_all_therapy'   => 'semua_anak_' . ($filterData['therapy_type'] === 'Semua jenis terapi' ? 'semua_terapi' : Str::snake($filterData['therapy_type'])),
            'one_child_multiple_therapy' => Str::snake($filterData['child_name']) . '_banyak_terapi',
            default                      => Str::snake($filterData['child_name']) . '_' . Str::snake($filterData['therapy_type']) . '_' . Str::snake($filterData['therapist_name']),
        };

        $fileName = 'laporan_soap' . '_' . $periodLabelFile . '_' . $viewTypeFile . '.pdf';

        $pdf = Pdf::loadView('exports.soap.report', $payload)
            ->setOption([
                'defaultFont'  => 'sans-serif',
                'isPhpEnabled' => true,
            ])
            ->setPaper('a4', 'portrait');

        return [
            'pdf'       => $pdf,
            'file_name' => $fileName,
        ];
    }
}
