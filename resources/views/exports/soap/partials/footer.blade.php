<div class="footer-accent-bar"></div>
<div class="report-footer">
    <table class="footer-table">
        <tr>
            <td style="text-align: left; padding-bottom: 30px; padding-left: 50px;">
                Dicetak {{ $print_date ?? now()->format('d/m/Y') }} &middot; Puspa Holistic Integrative Care
            </td>
            <td style="text-align: right;">
                {{-- Fallback if DomPDF script is disabled --}}
            </td>
        </tr>
    </table>
</div>

{{-- DomPDF native page numbering script --}}
<script type="text/php">
    if (isset($pdf)) {
        // Resolve the document's font (Poppins) with fallback to default sans-serif
        $font = $fontMetrics->get_font('Poppins', 'normal') ?? $fontMetrics->get_font('sans-serif', 'normal');
        $size = 7.5;
        $color = [107/255, 124/255, 119/255]; // matches --gray (#6B7C77)

        // $pdf is already the canvas (Dompdf\Adapter\CPDF), so call page_text directly
        $pdf->page_text(517, 805, "Halaman {PAGE_NUM}", $font, $size, $color);
    }
</script>