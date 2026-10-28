<div class="section-title">Rekap per Anak</div>
<table class="data-table">
    <thead>
        <tr>
            <th class="text-left" style="width: 18%;">ANAK</th>
            <th class="text-left" style="width: 22%;">JENIS TERAPI</th>
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
        @foreach($summary_children ?? [] as $child)
        <tr>
            <td class="font-bold">{{ $child['name'] ?? $child['child_name'] }}</td>
            <td>
                @if(is_array($child['therapies'] ?? null))
                @foreach($child['therapies'] as $th)
                @php
                $typeSlug = strtolower(str_replace(' ', '', $th['name'] ?? $th['therapy_type'] ?? ''));
                if (str_contains($typeSlug, 'okupasi')) $dotClass = 'dot-okupasi';
                elseif (str_contains($typeSlug, 'wicara')) $dotClass = 'dot-wicara';
                elseif (str_contains($typeSlug, 'fisio')) $dotClass = 'dot-fisioterapi';
                else $dotClass = 'dot-paedagog';
                @endphp
                <div>
                    <span class="almost-bullet {{ $dotClass }}"></span>{{ $th['name'] ?? $th['therapy_type'] }} ({{ $th['count'] ?? $th['sessions_count'] }})
                </div>
                @endforeach
                @else
                {{ $child['therapy_info'] ?? '-' }}
                @endif
            </td>
            <td class="text-center font-bold">{{ $child['total'] ?? 0 }}</td>
            <td class="text-center">{{ $child['hadir'] ?? 0 }}</td>
            <td class="text-center">{{ $child['izin'] ?? 0 }}</td>
            <td class="text-center">{{ $child['sakit'] ?? 0 }}</td>
            <td class="text-center">{{ $child['alpa'] ?? 0 }}</td>
            <td class="text-center">{{ $child['reschedule'] ?? 0 }}</td>
            <td class="text-center font-bold">{{ $child['attendance_rate'] ?? '0%' }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td>Total</td>
            <td></td>
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