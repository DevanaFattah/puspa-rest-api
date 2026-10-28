<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Laporan Rekap Kehadiran - Puspa Holistic Care</title>
    @include('exports.styles')
    @include('exports.kehadiran.partials.styles')
</head>

<body>

    {{-- Fixed Header on Every Page --}}
    @include('exports.kehadiran.partials.header')

    {{-- Auto-detect view_type if not explicitly set --}}
    @php
    if (empty($view_type)) {
    if (!empty($patient) || (!empty($filter['child_name']) && $filter['child_name'] !== 'Semua Anak')) {
    $view_type = 'single_child_multi_therapy';
    } elseif (!empty($filter['therapy_type']) && $filter['therapy_type'] !== 'Semua Tipe Terapi' && $filter['therapy_type'] !== 'Semua Jenis Terapi') {
    $view_type = 'single_therapy_multi_children';
    } else {
    $view_type = 'multi_children_multi_therapy';
    }
    }
    @endphp

    {{-- 1. Filter Summary Card --}}
    @include('exports.kehadiran.partials.filter_summary')

    {{-- =========================================================================
         PAGE 1: SUMMARY & STATS
         ========================================================================= --}}

    {{-- If single child, show Informasi Pasien bar --}}
    @if($view_type === 'single_child_multi_therapy')
    @include('exports.kehadiran.partials.patient_info')
    @endif

    {{-- Stats Cards Grid (Total, Hadir, Izin, Sakit, Alpa, Reschedule, Tingkat Kehadiran) --}}
    @include('exports.kehadiran.partials.stats_cards')

    {{-- Summary Table per view type --}}
    @if($view_type === 'single_therapy_multi_children')
    @include('exports.kehadiran.partials.table_rekap_anak_single_therapy')
    @elseif($view_type === 'single_child_multi_therapy')
    @include('exports.kehadiran.partials.table_rekap_tipe_terapi')
    @else
    @include('exports.kehadiran.partials.table_rekap_anak_multi_therapy')
    @endif

    {{-- Legend & Formula --}}
    @include('exports.kehadiran.partials.legend')

    {{-- =========================================================================
         PAGE 2: DETAIL PRESENSI (Table of session logs)
         ========================================================================= --}}
    <div class="page-break"></div>

    @include('exports.kehadiran.partials.table_detail_presensi')

    {{-- Fixed Footer on Every Page (placed at the bottom so DomPDF script executes after all pages exist) --}}
    @include('exports.kehadiran.partials.footer')

</body>

</html>