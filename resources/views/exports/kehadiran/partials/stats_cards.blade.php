{{--
    $stats = [
        'total' => 22,
        'hadir' => 15,
        'izin' => 2,
        'sakit' => 2,
        'alpa' => 1,
        'reschedule' => 2,
        'attendance_rate' => '75%' // or calculated: round($hadir / ($total - $reschedule) * 100) . '%'
    ]
--}}
<table class="stats-table">
    <tr>
        <td class="stat-card border-total" style="width: 14%;">
            <div class="stat-label">TOTAL JADWAL</div>
            <div class="stat-value color-total">{{ $stats['total'] ?? 0 }}</div>
        </td>
        <td class="stat-card border-hadir" style="width: 14%;">
            <div class="stat-label">HADIR</div>
            <div class="stat-value color-hadir">{{ $stats['hadir'] ?? 0 }}</div>
        </td>
        <td class="stat-card border-izin" style="width: 14%;">
            <div class="stat-label">IZIN</div>
            <div class="stat-value color-izin">{{ $stats['izin'] ?? 0 }}</div>
        </td>
        <td class="stat-card border-sakit" style="width: 14%;">
            <div class="stat-label">SAKIT</div>
            <div class="stat-value color-sakit">{{ $stats['sakit'] ?? 0 }}</div>
        </td>
        <td class="stat-card border-alpa" style="width: 14%;">
            <div class="stat-label">ALPA</div>
            <div class="stat-value color-alpa">{{ $stats['alpa'] ?? 0 }}</div>
        </td>
        <td class="stat-card border-reschedule" style="width: 15%;">
            <div class="stat-label">RESCHEDULE</div>
            <div class="stat-value color-reschedule">{{ $stats['reschedule'] ?? 0 }}</div>
        </td>
        <td class="stat-card border-rate" style="width: 15%;">
            <div class="stat-label">TINGKAT KEHADIRAN</div>
            <div class="stat-value color-rate">{{ $stats['attendance_rate'] ?? '0%' }}</div>
        </td>
    </tr>
</table>
