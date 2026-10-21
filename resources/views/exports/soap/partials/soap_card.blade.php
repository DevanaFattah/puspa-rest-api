@php
$therapyType = strtolower($session['therapy_type'] ?? '');

// Determine header bar background color
if ($view_type === 'one_child_one_therapy') {
$bgClass = 'bg-okupasi';
} else {
$bgClass = match(true) {
str_contains($therapyType, 'paedagog') => 'bg-paedagog',
str_contains($therapyType, 'okupasi') => 'bg-okupasi',
str_contains($therapyType, 'wicara') => 'bg-wicara',
str_contains($therapyType, 'fisio') => 'bg-fisioterapi',
default => 'bg-okupasi'
};
}
@endphp

<div class="session-card">
    {{-- Header Bar --}}
    <div class="session-header-bar {{ $bgClass }}">
        <table class="session-header-table">
            <tr>
                <td>
                    <div class="session-badge-title">
                        Sesi&nbsp; {{ $session['session_number'] ?? 1 }}
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        @if($view_type === 'one_child_one_therapy')
                        {{ $session['therapist_name'] ?? '' }}
                        @else
                        {{ $session['therapy_type'] ?? 'Terapi' }}
                        @endif
                    </div>
                </td>
                <td class="session-header-meta">
                    @if($view_type === 'one_child_one_therapy')
                    {{ $session['date_formatted'] ?? $session['date'] }} &middot; {{ $session['time'] ?? '' }}
                    @else
                    {{ $session['date_formatted'] ?? $session['date'] }} &middot; {{ $session['time'] ?? '' }} &middot; {{ $session['therapist_name'] ?? '' }}
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- Diagnosis & Tindakan Box --}}
    <div class="diagnosis-box {{ $bgClass }}">
        <table class="diagnosis-table">
            <tr>
                <td>
                    <div class="diag-label">DIAGNOSIS TERAPI</div>
                    <div class="diag-value">{{ $session['therapy_diagnosis'] ?? $session['soap']['therapy_diagnosis'] ?? '-' }}</div>
                </td>
                <td>
                    <div class="diag-label">TINDAKAN TERAPI</div>
                    <div class="diag-value">{{ $session['interventions'] ?? $session['soap']['interventions'] ?? '-' }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- SOAP Fields 2-Column Table --}}
    <div class="soap-content-container">
        <table class="soap-content-table">
            {{-- Row 1: S & O --}}
            <tr>
                <td class="soap-cell-left">
                    <div>
                        <span class="soap-badge-icon {{ $bgClass }}">S</span>
                        <div class="soap-badge-title">Subjektif</div>
                    </div>
                    <div class="soap-body-text">
                        {{ $session['subjective'] ?? $session['soap']['subjective'] ?? '-' }}
                    </div>
                </td>
                <td class="soap-cell-right">
                    <div>
                        <span class="soap-badge-icon {{ $bgClass }}">O</span>
                        <span class="soap-badge-title">Objektif</span>
                    </div>
                    <div class="soap-body-text">
                        {{ $session['objective'] ?? $session['soap']['objective'] ?? '-' }}
                    </div>
                </td>
            </tr>

            {{-- Row 2: A & P --}}
            <tr>
                <td class="soap-cell-left" style="padding-top: 8px;">
                    <div>
                        <span class="soap-badge-icon {{ $bgClass }}">A</span>
                        <span class="soap-badge-title">Assessment</span>
                    </div>
                    <div class="soap-body-text">
                        {{ $session['assessment'] ?? $session['soap']['assessment'] ?? '-' }}
                    </div>
                </td>
                <td class="soap-cell-right" style="padding-top: 8px;">
                    <div>
                        <span class="soap-badge-icon {{ $bgClass }}">P</span>
                        <span class="soap-badge-title">Plan</span>
                    </div>
                    <div class="soap-body-text">
                        {{ $session['plan'] ?? $session['soap']['plan'] ?? '-' }}
                    </div>
                </td>
            </tr>

            {{-- Row 3: I & A Lanjutan --}}
            <tr>
                <td class="soap-cell-left" style="padding-top: 8px;">
                    <div>
                        <span class="soap-badge-icon {{ $bgClass }}">I</span>
                        <span class="soap-badge-title">Implementasi</span>
                    </div>
                    <div class="soap-body-text">
                        {{ $session['implementation'] ?? $session['soap']['implementation'] ?? '-' }}
                    </div>
                </td>
                <td class="soap-cell-right" style="padding-top: 8px;">
                    <div>
                        <span class="soap-badge-icon {{ $bgClass }}">A</span>
                        <span class="soap-badge-title">Assessment Lanjutan</span>
                    </div>
                    <div class="soap-body-text">
                        {{ $session['advanced_assessment'] ?? $session['soap']['advanced_assessment'] ?? '-' }}
                    </div>
                </td>
            </tr>

            {{-- Row 4: Plan Lanjutan (Full width) --}}
            <tr>
                <td colspan="2" style="padding-top: 8px; padding-left: 5px; width: 100%;">
                    <div>
                        <span class="soap-badge-icon {{ $bgClass }}">P</span>
                        <span class="soap-badge-title">Plan Lanjutan</span>
                    </div>
                    <div class="soap-body-text">
                        {{ $session['advanced_plan'] ?? $session['soap']['advanced_plan'] ?? '-' }}
                    </div>
                </td>
            </tr>
        </table>
    </div>
</div>