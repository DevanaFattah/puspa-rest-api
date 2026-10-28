<style>
    :root {
        /* Status Colors */
        --total: #1F5C4D;
        --hadir: #2E8B6E;
        --izin: #C47A12;
        --sakit: #2F7CA3;
        --alpa: #C0392B;
        --reschedule: #7A5BB5;
    }

    @page {
        margin-top: 150px;
    }

    /* Fixed Header on every page */
    .report-header {
        top: -150px;
        height: 115px;
    }

    .header-table {
        padding: 36px 50px;
    }

    /* Fixed Footer on every page */
    .footer-accent-bar {
        bottom: -10px;
    }

    .report-footer {
        bottom: -7px;
    }

    .filter-card-container {
        margin-top: -15px;
    }

    .filter-table td {
        width: 16.66%;
    }

    /* Stats Grid */
    .stats-table {
        width: calc(100% + 20px);
        border-spacing: 10px 0;
        border-collapse: separate;
        margin: 0 -10px 5px;
    }

    .stats-table td {
        width: 14.28%;
    }

    .stat-card {
        border: 1px solid #e2ece8;
        border-top: 3px solid;
        border-radius: 4px;
        padding: 3px 11px 8px;
    }

    .stat-card.border-total {
        border-top-color: var(--total);
    }

    .stat-card.border-hadir {
        border-top-color: var(--hadir);
    }

    .stat-card.border-izin {
        border-top-color: var(--izin);
    }

    .stat-card.border-sakit {
        border-top-color: var(--sakit);
    }

    .stat-card.border-alpa {
        border-top-color: var(--alpa);
    }

    .stat-card.border-reschedule {
        border-top-color: var(--reschedule);
    }

    .stat-card.border-rate {
        border-top-color: var(--total);
    }

    .stat-label {
        font-size: 6.2pt;
        font-weight: 700;
        color: var(--gray);
    }

    .stat-value {
        font-size: 15pt;
        font-weight: 800;
        line-height: 1;
    }

    .stat-value.color-total {
        color: var(--total);
    }

    .stat-value.color-hadir {
        color: var(--hadir);
    }

    .stat-value.color-izin {
        color: var(--izin);
    }

    .stat-value.color-sakit {
        color: var(--sakit);
    }

    .stat-value.color-alpa {
        color: var(--alpa);
    }

    .stat-value.color-reschedule {
        color: var(--reschedule);
    }

    .stat-value.color-rate {
        color: var(--total);
    }

    /* Data Tables */
    .data-table {
        margin-bottom: 8px;
    }

    /* Therapy bullet dot */
    .dot-paedagog {
        background-color: var(--paedagog);
    }

    .dot-okupasi {
        background-color: var(--okupasi);
    }

    .dot-wicara {
        background-color: var(--wicara);
    }

    .dot-fisioterapi {
        background-color: var(--fisioterapi);
    }

    /* Status Pills / Badges in Table */
    .status-badge {
        display: inline-block;
        border-radius: 3px;
        padding: 2px 8px;
        font-size: 6.8pt;
        font-weight: 700;
        text-align: center;
    }

    .status-badge.badge-hadir {
        background-color: #d7ede5;
        color: var(--hadir);
    }

    .status-badge.badge-izin {
        background-color: #fcebd2;
        color: var(--izin);
    }

    .status-badge.badge-sakit {
        background-color: #d6e8f4;
        color: var(--sakit);
    }

    .status-badge.badge-alpa {
        background-color: #fcdbd9;
        color: var(--alpa);
    }

    .status-badge.badge-reschedule {
        background-color: #e8dff4;
        color: var(--reschedule);
    }

    /* Legend Row below Table */
    .legend-table {
        width: 100%;
        margin-top: 6px;
        margin-bottom: 12px;
        border-collapse: collapse;
    }

    .legend-table td {
        font-size: 7pt;
        font-weight: 800;
        vertical-align: middle;
    }

    .almost-bullet.bullet-hadir {
        background-color: var(--hadir);
    }

    .almost-bullet.bullet-izin {
        background-color: var(--izin);
    }

    .almost-bullet.bullet-sakit {
        background-color: var(--sakit);
    }

    .almost-bullet.bullet-alpa {
        background-color: var(--alpa);
    }

    .almost-bullet.bullet-reschedule {
        background-color: var(--reschedule);
    }

    .formula-text {
        text-align: right;
        color: var(--gray);
        font-size: 7pt;
    }

    .page-break {
        page-break-after: always;
    }
</style>