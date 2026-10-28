<div class="footer-accent-bar"></div>
<div class="report-footer">
    <table class="footer-table">
        <tr>
            <td>
                Dicetak {{ $print_date ?? now()->format('d/m/Y') }} &middot; Puspa Holistic Integrative Care
            </td>
            <td style="text-align: right;">
                {{-- Native DomPDF script will render Halaman X at exact coordinate --}}
            </td>
        </tr>
    </table>
</div>

{{-- DomPDF native page numbering script for A4 Landscape (width: 841.89pt, height: 595.28pt) --}}
<script type="text/php">
    if (isset($pdf)) {
        $font = $fontMetrics->get_font('Poppins', 'normal') ?? $fontMetrics->get_font('sans-serif', 'normal');
        $size = 7.2;
        $color = [107/255, 124/255, 119/255]; // var(--gray)
        $text = "Halaman {PAGE_NUM}";
        // Position at bottom-right for Landscape A4: x ~ 745, y ~ 568
        $pdf->page_text(764, 565, $text, $font, $size, $color);
    }
</script>