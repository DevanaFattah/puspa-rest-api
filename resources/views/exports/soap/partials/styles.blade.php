<style>
    @page {
        margin-top: 160px;
    }

    /* Fixed Header on every page */
    .report-header {
        top: -160px;
        height: 125px;
    }

    .header-table {
        padding: 40px 50px;
    }

    /* Fixed Footer on every page */
    .footer-accent-bar {
        bottom: 0;
    }

    .report-footer {
        bottom: 0px;
    }

    .filter-card-container {
        margin-top: -10px;
    }

    .filter-table td {
        width: 33.33%;
    }

    /* Therapy Legend Pills */
    .legend-table {
        width: calc(100% + 6px);
        border-spacing: 3px 0;
        border-collapse: separate;
        margin: 0 -3px;
    }

    .legend-table td {
        width: 20%;
    }

    .legend-title-cell {
        font-size: 7.5pt;
        font-weight: 800;
        color: var(--gray);
        line-height: 6px;
    }

    .legend-badge {
        display: inline-block;
        border-radius: 4px;
        padding-bottom: 6px;
        color: #ffffff;
        font-size: 7.5pt;
        font-weight: 700;
        text-align: center;
        width: 135px;
        margin-top: -2px;
    }

    .bg-paedagog {
        background-color: var(--paedagog);
    }

    .bg-okupasi {
        background-color: var(--okupasi);
    }

    .bg-wicara {
        background-color: var(--wicara);
    }

    .bg-fisioterapi {
        background-color: var(--fisio);
    }

    /* Patient Info Card */
    .patient-card {
        background-color: var(--light-orange);
        border: 1px solid var(--light-green-border);
        border-radius: 4px;
        padding: 5px 5px 10px;
        margin-bottom: 14px;
    }

    .one_child_multiple_therapy {
        background-color: var(--light-green);
    }

    /* Summary Tables */
    .data-table {
        margin-bottom: 14px;
    }

    /* Child Group Banner (View 3: All Children) */
    .child-group-banner {
        background-color: var(--green);
        color: #ffffff;
        border-top-left-radius: 3px;
        border-top-right-radius: 3px;
        padding: 0px 12px 7px 12px;
        page-break-after: avoid;
    }

    .child-banner-accent-bar {
        height: 3px;
        margin-bottom: 10px;
        background-color: var(--yellow);
        border-bottom-left-radius: 3px;
        border-bottom-right-radius: 3px;
        page-break-after: avoid;
    }

    .child-banner-table {
        width: 100%;
        border-collapse: collapse;
    }

    .child-banner-name {
        font-size: 10pt;
        font-weight: 800;
        color: #ffffff;
    }

    .child-banner-info {
        font-size: 7.5pt;
        color: #ffffff;
        text-align: center;
    }

    .child-banner-count {
        font-size: 10pt;
        font-weight: 700;
        color: #ffffff;
        text-align: right;
    }

    /* Session SOAP Card */
    .session-card {
        border-radius: 4px;
        margin-bottom: 12px;
        page-break-inside: avoid;
        background-color: #ffffff;
    }

    .session-header-bar {
        padding: 0px 14px;
        color: #ffffff;
        border-top-left-radius: 3px;
        border-top-right-radius: 3px;
    }

    .session-header-table td {
        width: 50%;
    }

    .session-header-table {
        width: 100%;
        border-collapse: collapse;
    }

    .session-badge-title {
        font-weight: 800;
        color: #ffffff;
        padding-bottom: 5px;
    }

    .session-header-meta {
        font-size: 7.5pt;
        font-weight: 600;
        color: #ffffff;
        text-align: right;
        padding-bottom: 5px;
    }

    /* Diagnosis & Tindakan Box */
    .diagnosis-box {
        background-color: var(--light-orange);
        border: 1px solid var(--light-green-border);
        border-bottom-left-radius: 3px;
        border-bottom-right-radius: 3px;
        padding: 1px 5px 8px 5px;
        margin-bottom: 5px;
    }

    .diagnosis-box.bg-paedagog {
        background-color: var(--light-green);
    }

    .diagnosis-box.bg-okupasi {
        background-color: var(--light-orange);
    }

    .diagnosis-box.bg-wicara {
        background-color: var(--light-blue);
    }

    .diagnosis-box.bg-fisioterapi {
        background-color: var(--light-purple);
    }

    .diagnosis-table {
        width: 100%;
        border-collapse: collapse;
    }

    .diagnosis-table td {
        width: 50%;
        vertical-align: top;
        padding: 2px 9px;
    }

    .diag-label {
        font-size: 7.5pt;
        color: var(--gray);
        font-weight: 800;
        text-transform: uppercase;
    }

    .diag-value {
        font-weight: 600;
        line-height: 1;
        margin-top: -4px;
    }

    /* SOAP Content Grid */
    .soap-content-container {
        border: 1px solid var(--light-green-border);
        border-radius: 3px;
        padding: 7px 0;
    }

    .soap-content-table {
        width: 100%;
        border-collapse: collapse;
        padding: 6px 10px;
    }

    .soap-content-table td {
        vertical-align: top;
        padding: 5px 0;
        width: 50%;
    }

    td.soap-cell-left {
        width: 50%;
        padding-right: 10px;
        padding-left: 5px;
    }

    td.soap-cell-right {
        width: 50%;
        padding-left: 10px;
    }

    .soap-badge-icon {
        line-height: 13px;
        display: inline-block;
        width: 24px;
        height: 24px;
        text-align: center;
        border-radius: 4px;
        color: #ffffff;
        font-size: 10pt;
        font-weight: 800;
    }

    .soap-badge-title {
        display: inline-block;
        margin-left: 5px;
        padding-bottom: 2px;
        font-size: 9pt;
        font-weight: 800;
        color: var(--green);
    }

    .soap-body-text {
        line-height: 1;
        margin-left: 2px;
        margin-top: -6px;
    }

    /* Signature Box (Single Therapy View) */
    .signature-container {
        margin-top: 25px;
        page-break-inside: avoid;
    }

    .signature-table {
        width: 100%;
        border-collapse: collapse;
    }

    .sig-date {
        color: var(--gray);
        margin-bottom: 40px;
    }

    .sig-name {
        font-weight: 600;
        border-bottom: 2px solid var(--gray);
    }

    .sig-role {
        color: var(--gray);
        margin-top: 2px;
    }

    .page-break {
        page-break-after: always;
    }
</style>