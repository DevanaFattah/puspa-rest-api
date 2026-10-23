<?php

namespace App\Exports;

use App\Models\TherapySession;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected array $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = TherapySession::with([
            'schedule.child.family.guardians',
            'schedule.therapist',
            'substituteTherapist',
        ]);

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('session_date', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate('session_date', '<=', $this->filters['date_to']);
        }

        // Filter Therapy Type (single or multiple)
        $therapyTypes = [];
        if (!empty($this->filters['therapy_types']) && is_array($this->filters['therapy_types'])) {
            $therapyTypes = array_filter($this->filters['therapy_types']);
        } elseif (!empty($this->filters['therapy_type_ids']) && is_array($this->filters['therapy_type_ids'])) {
            $therapyTypes = array_filter($this->filters['therapy_type_ids']);
        } elseif (!empty($this->filters['therapy_type'])) {
            $therapyTypes = is_array($this->filters['therapy_type']) ? $this->filters['therapy_type'] : [$this->filters['therapy_type']];
        }
        if (!empty($therapyTypes)) {
            $query->whereHas('schedule', function ($q) use ($therapyTypes) {
                $q->whereIn('therapy_type', $therapyTypes);
            });
        }

        // Filter Therapist (single or multiple)
        $therapistIds = [];
        if (!empty($this->filters['therapist_ids']) && is_array($this->filters['therapist_ids'])) {
            $therapistIds = array_filter($this->filters['therapist_ids']);
        } elseif (!empty($this->filters['therapist_id'])) {
            $therapistIds = is_array($this->filters['therapist_id']) ? $this->filters['therapist_id'] : [$this->filters['therapist_id']];
        }
        if (!empty($therapistIds)) {
            $query->where(function ($q) use ($therapistIds) {
                $q->whereIn('therapist_id', $therapistIds)
                  ->orWhere(function ($sq) use ($therapistIds) {
                      $sq->whereNull('therapist_id')
                         ->whereHas('schedule', function ($scq) use ($therapistIds) {
                             $scq->whereIn('therapist_id', $therapistIds);
                         });
                  });
            });
        }

        // Filter Child (single or multiple)
        $childIds = [];
        if (!empty($this->filters['child_ids']) && is_array($this->filters['child_ids'])) {
            $childIds = array_filter($this->filters['child_ids']);
        } elseif (!empty($this->filters['child_id'])) {
            $childIds = is_array($this->filters['child_id']) ? $this->filters['child_id'] : [$this->filters['child_id']];
        }
        if (!empty($childIds)) {
            $query->whereHas('schedule', function ($q) use ($childIds) {
                $q->whereIn('child_id', $childIds);
            });
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        return $query->select('therapy_sessions.*')
            ->leftJoin('schedules', 'therapy_sessions.schedule_id', '=', 'schedules.id')
            ->orderBy('session_date', 'desc')
            ->orderByRaw('COALESCE(therapy_sessions.start_time, schedules.start_time) ASC')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal Sesi',
            'No. RM',
            'Nama Anak',
            'Nama Wali',
            'Jenis Terapi',
            'Terapis',
            'Sesi Ke-',
            'Jam',
            'Status Kehadiran',
            'Waktu Check-in',
            'Catatan',
        ];
    }

    public function map($session): array
    {
        static $rowNumber = 0;
        $rowNumber++;

        $schedule = $session->schedule;
        $child = $schedule?->child;
        $guardian = $child?->family?->guardians->first();
        $therapist = $session->substituteTherapist ?? $schedule?->therapist;

        $statusLabels = [
            'pending' => 'Belum Diisi',
            'present' => 'Hadir',
            'absent' => 'Alpha',
            'excused' => 'Izin',
            'sick' => 'Sakit',
            'therapist_absent' => 'Terapis Alpha',
            'therapist_excused' => 'Terapis Izin',
            'cancelled' => 'Dibatalkan',
        ];

        return [
            $rowNumber,
            $session->session_date ? $session->session_date->format('d/m/Y') : '-',
            $child?->medical_record_number ?? '-',
            $child?->child_name ?? '-',
            $guardian?->guardian_name ?? '-',
            strtoupper($schedule?->therapy_type ?? '-'),
            $therapist?->therapist_name ?? '-',
            $session->session_number,
            $schedule ? (substr($schedule->start_time, 0, 5) . ' - ' . substr($schedule->end_time, 0, 5)) : '-',
            $statusLabels[$session->status] ?? $session->status,
            $session->checked_in_at ? $session->checked_in_at->format('d/m/Y H:i') : '-',
            $session->notes ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
