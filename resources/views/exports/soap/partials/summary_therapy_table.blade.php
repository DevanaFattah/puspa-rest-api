<div class="section-title">Ringkasan per Jenis Terapi</div>
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 25%;">JENIS TERAPI</th>
            <th style="width: 38%;">TERAPIS</th>
            <th style="width: 17%;">JUMLAH SESI</th>
            <th style="width: 20%;">SESI TERAKHIR</th>
        </tr>
    </thead>
    <tbody>
        @php
        $totalTherapistsCount = 0;
        $totalSessionsCount = 0;
        @endphp
        @forelse($summary_therapies ?? [] as $summary)
        @php
        $therapyType = strtolower($summary['therapy_type'] ?? '');
        $colorClass = match(true) {
        str_contains($therapyType, 'paedagog') => 'bg-paedagog',
        str_contains($therapyType, 'okupasi') => 'bg-okupasi',
        str_contains($therapyType, 'wicara') => 'bg-wicara',
        str_contains($therapyType, 'fisio') => 'bg-fisioterapi',
        default => 'bg-okupasi'
        };
        $totalSessionsCount += (int)($summary['session_count'] ?? 0);
        if (!empty($summary['therapist_count'])) {
        $totalTherapistsCount += (int)$summary['therapist_count'];
        }
        @endphp
        <tr>
            <td>
                <span style="display: inline-block; width: 8px; height: 8px; border-radius: 2px; margin-right: 4px;" class="{{ $colorClass }}"></span>
                <div class="summary-data">{{ $summary['therapy_type'] }}</div>
            </td>
            <td>{!! nl2br(e($summary['therapists'])) !!}</td>
            <td>{{ $summary['session_count'] }} sesi</td>
            <td>{{ $summary['last_session'] ?? '-' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="4" style="text-align: center; color: #6B7C77;">Tidak ada data ringkasan terapi</td>
        </tr>
        @endforelse
        <tr class="total-row">
            <td>Total</td>
            <td>{{ $summary_totals['therapists'] ?? ($totalTherapistsCount . ' terapis') }}</td>
            <td>{{ $summary_totals['sessions'] ?? ($totalSessionsCount . ' sesi') }}</td>
            <td></td>
        </tr>
    </tbody>
</table>