<style>
    :root {
        --green: #1F5C4D;
        --yellow: #F5B83D;
        --gray: #6B7C77;
        --black: #1B2B27;
        --light-green: #E8F4F0;
        --light-green-border: #D5E5DF;
        --light-orange: #FDF1DC;
        --light-blue: #E4F0F7;
        --light-purple: #EEE9F7;
        --orange: #C47A12;
        --paedagog: #2E8B6E;
        --okupasi: #C47A12;
        --wicara: #2F7CA3;
        --fisio: #7A5BB5;
    }

    @page {
        margin-top: 160px;
        margin-bottom: 52px;
        margin-left: 50px;
        margin-right: 50px;
    }

    * {
        box-sizing: border-box;
    }

    @font-face {
        font-family: 'Poppins';
        src: url("{{ storage_path('fonts/Poppins-Regular.ttf') }}") format('truetype');
        font-weight: normal;
        font-style: normal;
    }

    @font-face {
        font-family: 'Poppins';
        src: url("{{ storage_path('fonts/Poppins-SemiBold.ttf') }}") format('truetype');
        font-weight: 600;
        font-style: normal;
    }

    @font-face {
        font-family: 'Poppins';
        src: url("{{ storage_path('fonts/Poppins-Bold.ttf') }}") format('truetype');
        font-weight: 700;
        font-style: normal;
    }

    @font-face {
        font-family: 'Poppins';
        src: url("{{ storage_path('fonts/Poppins-ExtraBold.ttf') }}") format('truetype');
        font-weight: 800;
        font-style: normal;
    }

    @font-face {
        font-family: 'Playpen Sans';
        src: url("{{ storage_path('fonts/PlaypenSans-Regular.ttf') }}") format('truetype');
        font-weight: normal;
        font-style: normal;
    }

    @font-face {
        font-family: 'Playpen Sans';
        src: url("{{ storage_path('fonts/PlaypenSans-SemiBold.ttf') }}") format('truetype');
        font-weight: 600;
        font-style: normal;
    }

    @font-face {
        font-family: 'Playpen Sans';
        src: url("{{ storage_path('fonts/PlaypenSans-Bold.ttf') }}") format('truetype');
        font-weight: 700;
        font-style: normal;
    }

    @font-face {
        font-family: 'Playpen Sans';
        src: url("{{ storage_path('fonts/PlaypenSans-ExtraBold.ttf') }}") format('truetype');
        font-weight: 800;
        font-style: normal;
    }

    body {
        font-family: 'Poppins', sans-serif;
        font-size: 8.5pt;
        line-height: 1.35;
        color: var(--black);
    }

    /* Fixed Header on every page */
    .report-header {
        position: fixed;
        top: -160px;
        left: -50px;
        right: -50px;
        height: 125px;
        background-color: var(--green);
        color: #ffffff;
    }

    .header-table {
        width: 100%;
        border-collapse: collapse;
        padding: 40px 50px;
    }

    .header-logo-cell {
        width: 42px;
        vertical-align: middle;
    }

    .header-logo {
        width: 40px;
    }

    .header-brand-cell {
        vertical-align: middle;
        padding-left: 10px;
    }

    .brand-title,
    .report-main-title {
        font-size: 13pt;
        font-weight: 800;
        margin-top: -10px;
        line-height: 1.1;
        color: #ffffff;
    }

    .brand-subtitle,
    .report-confidential {
        font-size: 7.5pt;
        margin-top: 2px;
        line-height: 1;
    }

    .header-title-cell {
        vertical-align: middle;
        text-align: right;
    }

    .header-accent-bar {
        position: fixed;
        top: -35px;
        left: -50px;
        right: -50px;
        height: 5px;
        background-color: var(--yellow);
    }

    /* Fixed Footer on every page */
    .footer-accent-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        height: 2px;
        background-color: var(--gray);
    }

    .report-footer {
        position: fixed;
        bottom: -50px;
        left: -50px;
        font-size: 7.2pt;
        color: var(--gray);
    }

    .footer-table {
        width: 100%;
        border-collapse: collapse;
    }

    /* Filter Box */
    .filter-title,
    .patient-title {
        font-size: 7.5pt;
        font-weight: 800;
        color: var(--gray);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
    }

    .filter-card-container {
        margin-top: -10px;
    }

    .filter-card {
        background-color: var(--light-green);
        border: 1px solid var(--light-green-border);
        border-radius: 4px;
        padding: 5px 5px 10px;
        margin-bottom: 12px;
    }

    .filter-table {
        width: 100%;
        border-collapse: collapse;
    }

    .filter-table td {
        padding: 0px 6px;
        vertical-align: top;
        width: 33.33%;
    }

    .meta-label {
        font-size: 6.5pt;
        color: var(--gray);
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 0.4px;
    }

    .meta-value {
        font-size: 8.5pt;
        color: var(--black);
        font-weight: 600;
        margin-top: -10px;
    }

    /* Therapy Legend Pills */
    .legend-table {
        width: 100%;
        border-collapse: collapse;
    }

    .legend-title-cell {
        width: 120px;
        font-size: 7pt;
        font-weight: 800;
        color: var(--gray);
        text-transform: uppercase;
        line-height: 6px;
    }

    .legend-badge {
        display: inline-block;
        border-radius: 3px;
        padding-bottom: 5px;
        color: #ffffff;
        font-size: 7.5pt;
        font-weight: 700;
        text-align: center;
        width: 140px;
        margin-right: 2px;
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
    .section-title {
        font-size: 10pt;
        font-weight: 800;
        color: var(--green);
        margin-bottom: 8px;
        page-break-after: avoid;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 14px;
        font-size: 7.8pt;
    }

    .data-table th {
        background-color: var(--light-green);
        color: var(--gray);
        font-weight: 700;
        text-transform: uppercase;
        font-size: 7pt;
        padding: 3px 8px 6px 8px;
        border: 1px solid #d1e2db;
        text-align: left;
    }

    .data-table td {
        padding: 4px 8px 6px 8px;
        border: 1px solid var(--light-green-border);
        vertical-align: top;
        line-height: 1;
    }

    .summary-data {
        display: inline-block;
        font-weight: 800;
        height: 14px;
    }

    .data-table tr.total-row td {
        background-color: var(--light-green);
        font-weight: 800;
        padding: 0px 8px 6px 8px;
    }

    /* Child Group Banner (View 3: All Children) */
    .child-group-banner {
        background-color: var(--green);
        color: #ffffff;
        border-top-left-radius: 3px;
        border-top-right-radius: 3px;
        padding: 0px 12px 7px 12px;
        page-break-after: avoid;
        /* border-bottom: 5px solid var(--orange); */
    }

    .child-banner-accent-bar {
        height: 3px;
        margin-bottom: 10px;
        background-color: var(--yellow);
        border-bottom-left-radius: 3px;
        border-bottom-right-radius: 3px;
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
        color: var(--light-green-border);
        text-align: center;
    }

    .child-banner-count {
        font-size: 8pt;
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

    .session-header-table {
        width: 100%;
        border-collapse: collapse;
    }

    .session-badge-title {
        font-size: 8.5pt;
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
        font-size: 6.5pt;
        color: var(--gray);
        font-weight: 800;
        text-transform: uppercase;
    }

    .diag-value {
        font-size: 7.8pt;
        color: #1e293b;
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
        font-size: 7.6pt;
        color: var(--black);
        line-height: 1;
        margin-left: 2px;
    }

    .badge-bg-paedagog {
        background-color: var(--paedagog);
    }

    .badge-bg-okupasi {
        background-color: var(--okupasi);
    }

    .badge-bg-wicara {
        background-color: var(--wicara);
    }

    .badge-bg-fisioterapi {
        background-color: var(--fisio);
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
        font-size: 8pt;
        color: var(--gray);
        margin-bottom: 40px;
    }

    .sig-name {
        font-size: 8.5pt;
        font-weight: 600;
        color: var(--black);
        border-bottom: 2px solid var(--gray);
    }

    .sig-role {
        font-size: 7.5pt;
        color: #64748b;
        margin-top: 2px;
    }

    .page-break {
        page-break-after: always;
    }
</style>