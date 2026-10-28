<div class="signature-container">
    <table class="signature-table">
        <tr>
            <td style="width: 65%;"></td>
            <td class="text-center" style="width: 35%;">
                <div class="sig-date">{{ $signature['city'] ?? 'Surakarta' }}, {{ $signature['date'] ?? now()->format('d/m/Y') }}</div>
                <div style="height: 45px;"></div>
                <div class="sig-name">{{ $signature['therapist_name'] ?? ($filter['therapist_name'] ?? 'Terapis') }}</div>
                <div class="sig-role">Terapis {{ $signature['therapy_type'] ?? ($filter['therapy_type'] ?? '') }}</div>
            </td>
        </tr>
    </table>
</div>