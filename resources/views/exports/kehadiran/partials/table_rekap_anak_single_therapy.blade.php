<div class="section-title">Rekap per Anak</div>
<table class="data-table">
    <thead>
        <tr>
            <th class="text-left" style="width: 20%;">ANAK</th>
            <th class="text-left" style="width: 18%;">NO. REKAM MEDIS</th>
            <th style="width: 7%;">TOTAL</th>
            <th style="width: 7%;">HADIR</th>
            <th style="width: 7%;">IZIN</th>
            <th style="width: 7%;">SAKIT</th>
            <th style="width: 7%;">ALPA</th>
            <th style="width: 11%;">RESCHEDULE</th>
            <th style="width: 16%;">% KEHADIRAN</th>
        </tr>
    </thead>
    <tbody>
        @foreach($summary_children ?? [] as $child)
        <tr>
            <td class="font-bold">{{ $child['name'] ?? $child['child_name'] }}</td>
            <td>{{ $child['medical_record_number'] ?? $child['no_rm'] ?? '-' }}</td>
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
            <td>{{ $totals['children_count'] ?? count($summary_children ?? []) }} anak</td>
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