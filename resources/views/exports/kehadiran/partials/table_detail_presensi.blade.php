<div class="section-title">Detail Presensi</div>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 4%;">NO</th>
            <th style="width: 10%;">TANGGAL</th>
            <th style="width: 7%;">JAM</th>

            @if($view_type === 'single_therapy_multi_children')
            <th class="text-left" style="width: 14%;">ANAK</th>
            @elseif($view_type === 'single_child_multi_therapy')
            <th class="text-left" style="width: 14%;">TERAPIS</th>
            <th class="text-left" style="width: 14%;">TIPE TERAPI</th>
            @elseif($view_type === 'multi_children_multi_therapy')
            <th class="text-left" style="width: 12%;">ANAK</th>
            <th class="text-left" style="width: 13%;">JENIS TERAPI</th>
            <th class="text-left" style="width: 13%;">TERAPIS</th>
            @endif

            <th class="text-center" style="width: 10%;">STATUS</th>

            @if($view_type === 'multi_children_multi_therapy')
            <th style="width: 9%;">METODE</th>
            @endif

            <th class="text-left" style="width: {{ $view_type === 'single_therapy_multi_children' ? '55%' : ($view_type === 'single_child_multi_therapy' ? '41%' : '32%') }};">KETERANGAN</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sessions ?? [] as $index => $session)
        @php
        // Resolve status badge class
        $statusRaw = strtolower($session['status_raw'] ?? $session['status'] ?? '');
        if (in_array($statusRaw, ['hadir', 'present'])) {
        $statusClass = 'badge-hadir';
        $statusText = 'Hadir';
        } elseif (in_array($statusRaw, ['izin', 'excused'])) {
        $statusClass = 'badge-izin';
        $statusText = 'Izin';
        } elseif (in_array($statusRaw, ['sakit', 'sick'])) {
        $statusClass = 'badge-sakit';
        $statusText = 'Sakit';
        } elseif (in_array($statusRaw, ['alpa', 'alpha', 'absent'])) {
        $statusClass = 'badge-alpa';
        $statusText = 'Alpa';
        } elseif (in_array($statusRaw, ['reschedule', 'rescheduled'])) {
        $statusClass = 'badge-reschedule';
        $statusText = 'Reschedule';
        } else {
        $statusClass = 'badge-hadir';
        $statusText = $session['status_label'] ?? $session['status'] ?? '-';
        }

        // Resolve therapy type dot if present
        $typeSlug = strtolower(str_replace(' ', '', $session['therapy_type_raw'] ?? $session['therapy_type'] ?? ''));
        if (str_contains($typeSlug, 'okupasi')) $dotClass = 'dot-okupasi';
        elseif (str_contains($typeSlug, 'wicara')) $dotClass = 'dot-wicara';
        elseif (str_contains($typeSlug, 'fisio')) $dotClass = 'dot-fisioterapi';
        else $dotClass = 'dot-paedagog';
        @endphp
        <tr>
            <td class="text-center">{{ $index + 1 }}</td>
            <td class="text-center">{{ $session['date'] ?? $session['session_date'] ?? '-' }}</td>
            <td class="text-center">{{ $session['time'] ?? $session['start_time'] ?? '-' }}</td>

            @if($view_type === 'single_therapy_multi_children')
            <td class="font-bold">{{ $session['child_name'] ?? $session['child']['name'] ?? '-' }}</td>
            @elseif($view_type === 'single_child_multi_therapy')
            <td>{{ $session['therapist_name'] ?? $session['therapist']['name'] ?? '-' }}</td>
            <td>
                <span class="almost-bullet {{ $dotClass }}"></span>{{ $session['therapy_type_label'] ?? $session['therapy_type'] ?? '-' }}
            </td>
            @elseif($view_type === 'multi_children_multi_therapy')
            <td class="font-bold">{{ $session['child_name'] ?? $session['child']['name'] ?? '-' }}</td>
            <td>
                <span class="almost-bullet {{ $dotClass }}"></span>{{ $session['therapy_type_label'] ?? $session['therapy_type'] ?? '-' }}
            </td>
            <td>{{ $session['therapist_name'] ?? $session['therapist']['name'] ?? '-' }}</td>
            @endif

            <td class="text-center">
                <span class="status-badge {{ $statusClass }}">{{ $statusText }}</span>
            </td>

            @if($view_type === 'multi_children_multi_therapy')
            <td class="text-center">{{ $session['method'] ?? $session['attendance_method'] ?? 'Scan QR' }}</td>
            @endif

            <td style="color: {{ !empty($session['notes']) && $session['notes'] !== '–' && $session['notes'] !== '-' ? 'var(--black)' : 'var(--gray)' }};">
                {{ !empty($session['notes']) ? $session['notes'] : '–' }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>