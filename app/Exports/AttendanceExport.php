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

        if (!empty($this->filters['therapy_type'])) {
            $type = $this->filters['therapy_type'];
            $query->whereHas('schedule', function ($q) use ($type) {
                $q->where('therapy_type', $type);
            });
        }

        if (!empty($this->filters['therapist_id'])) {
            $therapistId = $this->filters['therapist_id'];
            $query->where(function ($q) use ($therapistId) {
                $q->where('therapist_id', $therapistId)
                  ->orWhere(function ($sq) use ($therapistId) {
                      $sq->whereNull('therapist_id')
                         ->whereHas('schedule', function ($scq) use ($therapistId) {
                             $scq->where('therapist_id', $therapistId);
                         });
                  });
            });
        }

        if (!empty($this->filters['child_id'])) {
            $childId = $this->filters['child_id'];
            $query->whereHas('schedule', function ($q) use ($childId) {
                $q->where('child_id', $childId);
            });
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        return $query->orderBy('session_date', 'desc')->get();
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
