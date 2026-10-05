<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Kehadiran Terapi - Puspa Holistic</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #1E5C58;
            padding-bottom: 10px;
        }
        .header h2 {
            margin: 0;
            color: #1E5C58;
            font-size: 18px;
        }
        .header p {
            margin: 4px 0 0 0;
            color: #666;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background-color: #1E5C58;
            color: #ffffff;
            font-weight: bold;
            font-size: 10px;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .status-badge {
            font-weight: bold;
            text-transform: capitalize;
        }
        .footer {
            margin-top: 20px;
            text-align: right;
            font-size: 10px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>PUSPA HOLISTIC INTEGRATIVE CARE</h2>
        <p>Rekap Kehadiran Presensi Terapi Anak</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                <th style="width: 65px;">Tanggal</th>
                <th style="width: 60px;">No. RM</th>
                <th>Nama Anak</th>
                <th>Terapis</th>
                <th style="width: 55px;">Jenis</th>
                <th style="width: 35px;">Sesi</th>
                <th style="width: 60px;">Status</th>
                <th style="width: 75px;">Waktu Scan</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sessions as $index => $session)
                @php
                    $schedule = $session->schedule;
                    $child = $schedule?->child;
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
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $session->session_date ? $session->session_date->format('d/m/Y') : '-' }}</td>
                    <td>{{ $child?->medical_record_number ?? '-' }}</td>
                    <td>{{ $child?->child_name ?? '-' }}</td>
                    <td>{{ $therapist?->therapist_name ?? '-' }}</td>
                    <td>{{ strtoupper($schedule?->therapy_type ?? '-') }}</td>
                    <td>Ke-{{ $session->session_number }}</td>
                    <td class="status-badge">{{ $statusLabels[$session->status] ?? $session->status }}</td>
                    <td>{{ $session->checked_in_at ? $session->checked_in_at->format('d/m/Y H:i') : '-' }}</td>
                    <td>{{ $session->notes ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align: center;">Tidak ada data presensi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada: {{ date('d/m/Y H:i') }}
    </div>
</body>
</html>
