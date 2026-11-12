<?php

namespace App\Exports;

use App\Models\TherapySoap;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TherapySoapExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected array $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = TherapySoap::with([
            'therapySession.schedule.child.family.guardians',
            'therapySession.schedule.therapist',
            'therapySession.substituteTherapist',
        ]);

        $therapistId = $this->filters['therapist_id'] ?? null;
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

        // Filter Child IDs
        $childIds = [];
        if (!empty($this->filters['child_ids']) && is_array($this->filters['child_ids'])) {
            $childIds = array_filter($this->filters['child_ids']);
        } elseif (!empty($this->filters['child_id'])) {
            $childIds = is_array($this->filters['child_id']) ? $this->filters['child_id'] : [$this->filters['child_id']];
        }
        if (!empty($childIds)) {
            $query->whereHas('therapySession.schedule', function ($q) use ($childIds) {
                $q->whereIn('child_id', $childIds);
            });
        }

        // Filter Therapy Type
        $therapyType = $this->filters['therapy_type'] ?? null;
        if ($therapyType) {
            $query->whereHas('therapySession.schedule', function ($q) use ($therapyType) {
                $q->where('therapy_type', $therapyType);
            });
        }

        // Filter Period & Date
        $periodType = $this->filters['period_type'] ?? null;
        if ($periodType === 'daily' && !empty($this->filters['date'])) {
            $query->whereDate('performed_at', $this->filters['date']);
        } elseif ($periodType === 'weekly' && !empty($this->filters['week'])) {
            $year = (int) ($this->filters['year'] ?? now()->year);
            $query->whereYear('performed_at', $year)
                ->whereRaw('WEEK(performed_at, 1) = ?', [(int) $this->filters['week']]);
        } elseif ($periodType === 'monthly' && !empty($this->filters['month'])) {
            $year = (int) ($this->filters['year'] ?? now()->year);
            $query->whereYear('performed_at', $year)
                ->whereMonth('performed_at', (int) $this->filters['month']);
        } elseif ($periodType === 'custom') {
            if (!empty($this->filters['date_from'])) {
                $query->whereDate('performed_at', '>=', $this->filters['date_from']);
            }
            if (!empty($this->filters['date_to'])) {
                $query->whereDate('performed_at', '<=', $this->filters['date_to']);
            }
        } else {
            // General date_from / date_to / start_date / end_date fallback
            $start = $this->filters['date_from'] ?? $this->filters['start_date'] ?? null;
            $end = $this->filters['date_to'] ?? $this->filters['end_date'] ?? null;
            if ($start) {
                $query->whereDate('performed_at', '>=', $start);
            }
            if ($end) {
                $query->whereDate('performed_at', '<=', $end);
            }
        }

        return $query->orderBy('performed_at', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal Terapi',
            'Jam',
            'No. RM',
            'Nama Anak',
            'Terapis',
            'Jenis Terapi',
            'Diagnosis',
            'Intervensi',
            'Subjective (S)',
            'Objective (O)',
            'Assessment (A)',
            'Plan (P)',
            'Implementasi',
            'Asesmen Lanjutan',
            'Plan Lanjutan',
        ];
    }

    public function map($soap): array
    {
        static $rowNumber = 0;
        $rowNumber++;

        $session = $soap->therapySession;
        $schedule = $session?->schedule;
        $child = $schedule?->child;
        $therapist = $session?->substituteTherapist ?? $schedule?->therapist;

        $timeStr = '-';
        if ($soap->performed_at) {
            $timeStr = $soap->performed_at->format('H:i');
        } elseif ($schedule?->start_time) {
            $timeStr = substr($schedule->start_time, 0, 5) . ' - ' . substr($schedule->end_time, 0, 5);
        }

        return [
            $rowNumber,
            $soap->performed_at ? $soap->performed_at->format('d/m/Y') : ($session?->session_date ? $session->session_date->format('d/m/Y') : '-'),
            $timeStr,
            $child?->medical_record_number ?? '-',
            $child?->child_name ?? '-',
            $therapist?->therapist_name ?? '-',
            strtoupper($schedule?->therapy_type ?? '-'),
            $soap->therapy_diagnosis ?? '-',
            $soap->interventions ?? '-',
            $soap->subjective ?? '-',
            $soap->objective ?? '-',
            $soap->assessment ?? '-',
            $soap->plan ?? '-',
            $soap->implementation ?? '-',
            $soap->advanced_assessment ?? '-',
            $soap->advanced_plan ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

