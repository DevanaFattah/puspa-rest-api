<div class="patient-title">INFORMASI PASIEN ANAK</div>
<div class="patient-card {{ $view_type }}">
    <table class="filter-table">
        <tr>
            <td>
                <div class="meta-label">NAMA LENGKAP</div>
                <div class="meta-value">{{ $patient['name'] ?? '-' }}</div>
            </td>
            <td>
                <div class="meta-label">NO. REKAM MEDIS</div>
                <div class="meta-value">{{ $patient['medical_record_number'] ?? $patient['no_rm'] ?? '-' }}</div>
            </td>
            <td>
                <div class="meta-label">USIA</div>
                <div class="meta-value">{{ $patient['age'] ?? '-' }}</div>
            </td>
        </tr>
        <tr>
            <td style="padding-top: 6px;">
                <div class="meta-label">ORANG TUA / WALI</div>
                <div class="meta-value">{{ $patient['guardian'] ?? '-' }}</div>
            </td>
            <td style="padding-top: 6px;">
                <div class="meta-label">ASAL SEKOLAH</div>
                <div class="meta-value">{{ $patient['school'] ?? '-' }}</div>
            </td>
            <td style="padding-top: 6px;">
                <div class="meta-label">JENIS TERAPI</div>
                <div class="meta-value">{{ $patient['therapy_type_label'] ?? $patient['therapy_type'] ?? '-' }}</div>
            </td>
        </tr>
    </table>
</div>