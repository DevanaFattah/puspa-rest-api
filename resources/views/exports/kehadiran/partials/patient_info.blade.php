@if(!empty($patient))
<div class="patient-title">INFORMASI PASIEN</div>
<div class="filter-card">
    <table class="filter-table">
        <tr>
            <td style="width: 17%;">
                <div class="meta-label">NAMA LENGKAP</div>
                <div class="meta-value">{{ $patient['name'] ?? $patient['child_name'] ?? '-' }}</div>
            </td>
            <td style="width: 17%;">
                <div class="meta-label">NO. REKAM MEDIS</div>
                <div class="meta-value">{{ $patient['medical_record_number'] ?? $patient['no_rm'] ?? '-' }}</div>
            </td>
            <td style="width: 18%;">
                <div class="meta-label">USIA</div>
                <div class="meta-value">{{ $patient['age'] ?? '-' }}</div>
            </td>
            <td style="width: 18%;">
                <div class="meta-label">ORANG TUA / WALI</div>
                <div class="meta-value">{{ $patient['guardian'] ?? '-' }}</div>
            </td>
            <td style="width: 15%;">
                <div class="meta-label">ASAL SEKOLAH</div>
                <div class="meta-value">{{ $patient['school'] ?? '-' }}</div>
            </td>
            <td style="width: 15%;">
                <div class="meta-label">TIPE TERAPI DIIKUTI</div>
                <div class="meta-value">{{ $patient['therapy_type_label'] ?? $patient['therapy_count_label'] ?? '-' }}</div>
            </td>
        </tr>
    </table>
</div>
@endif