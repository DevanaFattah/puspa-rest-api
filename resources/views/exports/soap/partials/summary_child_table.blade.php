<div class="section-title">Ringkasan per Anak</div>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 25%;">ANAK</th>
            <th style="width: 20%;">NO. REKAM MEDIS</th>
            <th style="width: 40%;">JENIS TERAPI (JUMLAH SESI)</th>
            <th style="width: 15%;">TOTAL</th>
        </tr>
    </thead>
    <tbody>
        @php
        $grandTotalSessions = 0;
        @endphp
        @forelse($summary_children ?? [] as $item)
        @php
        $grandTotalSessions += (int)($item['total_sessions'] ?? 0);
        @endphp
        <tr>
            <td>
                <div class="summary-data">{{ $item['child_name'] }}</div>
            </td>
            <td>{{ $item['medical_record_number'] ?? $item['no_rm'] ?? '-' }}</td>
            <td>
                @if(is_array($item['therapies'] ?? null))
                @foreach($item['therapies'] as $th)
                <div>{{ $th['name'] }} ({{ $th['count'] }})</div>
                @endforeach
                @else
                {!! nl2br(e($item['therapies'] ?? '-')) !!}
                @endif
            </td>
            <td>{{ $item['total_sessions'] ?? 0 }} sesi</td>
        </tr>
        @empty
        <tr>
            <td colspan="4" style="text-align: center; color: #6B7C77;">Tidak ada data anak</td>
        </tr>
        @endforelse
        <tr class="total-row">
            <td colspan="3">Total</td>
            <td>{{ $summary_totals['total_sessions'] ?? ($grandTotalSessions . ' sesi') }}</td>
        </tr>
    </tbody>
</table>