<div class="section-title">Rekap per Tipe Terapi</div>
<table class="data-table">
    <thead>
        <tr>
            <th class="text-left" style="width: 18%;">TIPE TERAPI</th>
            <th class="text-left" style="width: 22%;">TERAPIS (JUMLAH SESI)</th>
            <th style="width: 7%;">TOTAL</th>
            <th style="width: 7%;">HADIR</th>
            <th style="width: 7%;">IZIN</th>
            <th style="width: 7%;">SAKIT</th>
            <th style="width: 7%;">ALPA</th>
            <th style="width: 11%;">RESCHEDULE</th>
            <th style="width: 14%;">% KEHADIRAN</th>
        </tr>
    </thead>
    <tbody>
        @foreach($summary_therapies ?? [] as $therapy)
        @php
        $typeSlug = strtolower(str_replace(' ', '', $therapy['therapy_type_raw'] ?? $therapy['therapy_type'] ?? ''));
        if (str_contains($typeSlug, 'okupasi')) $dotClass = 'dot-okupasi';
        elseif (str_contains($typeSlug, 'wicara')) $dotClass = 'dot-wicara';
        elseif (str_contains($typeSlug, 'fisio')) $dotClass = 'dot-fisioterapi';
        else $dotClass = 'dot-paedagog';
        @endphp
        <tr>
            <td class="font-bold">
                <span class="almost-bullet {{ $dotClass }}"></span>
                {{ $therapy['therapy_type_label'] ?? $therapy['therapy_type'] }}
            </td>
            <td>
                @if(is_array($therapy['therapists'] ?? null))
                @foreach($therapy['therapists'] as $t)
                <div>{{ $t['name'] ?? $t['therapist_name'] }} ({{ $t['sessions_count'] ?? $t['count'] }})</div>
                @endforeach
                @else
                {{ $therapy['therapist_info'] ?? '-' }}
                @endif
            </td>
            <td class="text-center font-bold">{{ $therapy['total'] ?? 0 }}</td>
            <td class="text-center">{{ $therapy['hadir'] ?? 0 }}</td>
            <td class="text-center">{{ $therapy['izin'] ?? 0 }}</td>
            <td class="text-center">{{ $therapy['sakit'] ?? 0 }}</td>
            <td class="text-center">{{ $therapy['alpa'] ?? 0 }}</td>
            <td class="text-center">{{ $therapy['reschedule'] ?? 0 }}</td>
            <td class="text-center font-bold">{{ $therapy['attendance_rate'] ?? '0%' }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td>Total semua tipe</td>
            <td>{{ $totals['therapists_count'] ?? '-' }}</td>
            <td class="text-center">{{ $totals['total'] ?? 0 }}</td>
            <td class="text-center">{{ $totals['hadir'] ?? 0 }}</td>
            <td class="text-center">{{ $totals['izin'] ?? 0 }}</td>
            <td class="text-center">{{ $totals['sakit'] ?? 0 }}</td>
            <td class="text-center">{{ $totals['alpa'] ?? 0 }}</td>
            <td class="text-center">{{ $totals['reschedule'] ?? 0 }}</td>
            <td class="text-center">{{ $totals['attendance_rate'] ?? '0%' }}</td>
        </tr>
    </tfoot>
</table>