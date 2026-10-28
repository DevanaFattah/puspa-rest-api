<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Laporan Catatan SOAP Terapi - Puspa Holistic Care</title>
    @include('exports.styles')
    @include('exports.soap.partials.styles')
</head>

<body>

    {{-- 1. Repeated Header & Footer on Every Page --}}
    @include('exports.soap.partials.header')

    {{-- Auto-detect view_type if not explicitly passed --}}
    @php
    if (empty($view_type)) {
    if (!empty($grouped_children) || !empty($summary_children)) {
    $view_type = 'all_children_all_therapy';
    } elseif (!empty($summary_therapies)) {
    $view_type = 'one_child_multiple_therapy';
    } else {
    $view_type = 'one_child_one_therapy';
    }
    }
    @endphp

    {{-- 2. Filter Summary (Present in all 3 views) --}}
    @include('exports.soap.partials.filter_summary')

    {{-- =========================================================================
         VIEW 1: Satu Anak Satu Terapi (One Child One Therapy)
         ========================================================================= --}}
    @if($view_type === 'one_child_one_therapy')

    {{-- Patient Info Card --}}
    @include('exports.soap.partials.patient_info')

    <div class="section-title">Detail Catatan SOAP</div>

    {{-- Session Cards --}}
    @foreach($sessions ?? [] as $session)
    @include('exports.soap.partials.soap_card', [
    'session' => $session,
    'view_type' => 'one_child_one_therapy'
    ])
    @endforeach

    {{-- Signature at bottom of document --}}
    @include('exports.soap.partials.signature')

    {{-- =========================================================================
         VIEW 2: Satu Anak Banyak Terapi (One Child Multiple Therapy)
         ========================================================================= --}}
    @elseif($view_type === 'one_child_multiple_therapy')

    {{-- Therapy Color Legend --}}
    @include('exports.soap.partials.therapy_legend')

    {{-- Patient Info Card --}}
    @include('exports.soap.partials.patient_info')

    {{-- Summary Table per Therapy --}}
    @include('exports.soap.partials.summary_therapy_table')

    <div class="section-title">Riwayat Sesi (urut tanggal)</div>

    {{-- Session Cards --}}
    @foreach($sessions ?? [] as $session)
    @include('exports.soap.partials.soap_card', [
    'session' => $session,
    'view_type' => 'one_child_multiple_therapy'
    ])
    @endforeach

    {{-- =========================================================================
         VIEW 3: Semua Anak Semua Terapi (All Children All Therapy)
         ========================================================================= --}}
    @elseif($view_type === 'all_children_all_therapy')

    {{-- Therapy Color Legend --}}
    @include('exports.soap.partials.therapy_legend')

    {{-- Summary Table per Child --}}
    @include('exports.soap.partials.summary_child_table')

    {{-- Grouped by Child --}}
    @foreach($grouped_children ?? [] as $i=>$childGroup)

    @if ($i === 0)
    <div class="child-group-banner">
        @else
        <div class="child-group-banner" style="margin-top: 25px;">
            @endif
            <table class="child-banner-table">
                <tr>
                    <td style="width: 20%;" class="child-banner-name">
                        {{ $childGroup['child']['name'] ?? $childGroup['child']['child_name'] }}
                    </td>
                    <td style="width: 65%;" class="child-banner-info">
                        {{ $childGroup['child']['medical_record_number'] ?? $childGroup['child']['no_rm'] ?? '-' }}
                        &middot;
                        {{ $childGroup['child']['age'] ?? '-' }}
                        &middot;
                        {{ $childGroup['child']['guardian'] ?? '-' }}
                        &middot;
                        {{ $childGroup['child']['school'] ?? '-' }}
                    </td>
                    <td style="width: 15%;" class="child-banner-count">
                        {{ count($childGroup['sessions'] ?? []) }} sesi
                    </td>
                </tr>
            </table>
        </div>
        <div class="child-banner-accent-bar"></div>

        {{-- Sessions for this child --}}
        @foreach($childGroup['sessions'] ?? [] as $session)
        @include('exports.soap.partials.soap_card', [
        'session' => $session,
        'view_type' => 'all_children_all_therapy'
        ])
        @endforeach
        @endforeach

        @endif

        @include('exports.soap.partials.footer')


</body>

</html>