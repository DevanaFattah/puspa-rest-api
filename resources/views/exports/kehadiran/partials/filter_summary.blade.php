<div class="filter-card-container">
    <div class="filter-title">FILTER DATA</div>
    <div class="filter-card">
        <table class="filter-table">
            <tr>
                <td style="width: 17%;">
                    <div class="meta-label">ANAK / PASIEN</div>
                    <div class="meta-value">{{ $filter['child_name'] ?? 'Semua Anak' }}</div>
                </td>
                <td style="width: 17%;">
                    <div class="meta-label">TERAPIS</div>
                    <div class="meta-value">{{ $filter['therapist_name'] ?? 'Semua Terapis' }}</div>
                </td>
                <td style="width: 18%;">
                    <div class="meta-label">TIPE TERAPI</div>
                    <div class="meta-value">{{ $filter['therapy_type'] ?? 'Semua Tipe Terapi' }}</div>
                </td>
                <td style="width: 18%;">
                    <div class="meta-label">PERIODE</div>
                    <div class="meta-value">{{ $filter['period'] ?? '-' }}</div>
                </td>
                <td style="width: 15%;">
                    <div class="meta-label">TOTAL DATA</div>
                    <div class="meta-value">{{ $filter['total_data'] ?? '-' }}</div>
                </td>
                <td style="width: 15%;">
                    <div class="meta-label">DIEKSPOR OLEH</div>
                    <div class="meta-value">{{ $filter['downloaded_by'] ?? 'Annisa Koirus' }}</div>
                    @if(!empty($filter['downloaded_by_role']))
                    <div class="meta-value">({{ $filter['downloaded_by_role'] }})</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>
</div>