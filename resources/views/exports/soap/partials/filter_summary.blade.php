<div class="filter-card-container">
    <div class="filter-title">RINGKASAN FILTER</div>
    <div class="filter-card">
        <table class="filter-table">
            <tr>
                <td>
                    <div class="meta-label">ANAK</div>
                    <div class="meta-value">{{ $filter['child_name'] ?? 'Semua anak' }}</div>
                </td>
                <td>
                    <div class="meta-label">TERAPIS</div>
                    <div class="meta-value">{{ $filter['therapist_name'] ?? 'Semua terapis' }}</div>
                </td>
                <td>
                    <div class="meta-label">JENIS TERAPI</div>
                    <div class="meta-value">{{ $filter['therapy_type'] ?? 'Semua jenis terapi' }}</div>
                </td>
            </tr>
            <tr>
                <td style="padding-top: 6px;">
                    <div class="meta-label">PERIODE</div>
                    <div class="meta-value">{{ $filter['period'] ?? '-' }}</div>
                </td>
                <td style="padding-top: 6px;">
                    <div class="meta-label">TOTAL DATA</div>
                    <div class="meta-value">{{ $filter['total_data'] ?? '-' }}</div>
                </td>
                <td style="padding-top: 6px;">
                    <div class="meta-label">DIUNDUH OLEH</div>
                    <div class="meta-value">{{ $filter['downloaded_by'] ?? 'Admin Puspa' }}</div>
                </td>
            </tr>
        </table>
    </div>
</div>