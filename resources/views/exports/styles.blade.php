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

    /* Status Colors */
    --hadir: #2E8B6E;
    --izin: #C47A12;
    --sakit: #2F7CA3;
    --alpa: #D32F2F;
    --reschedule: #7A5BB5;

    /* Therapy Types Colors */
    --paedagog: #2E8B6E;
    --okupasi: #C47A12;
    --wicara: #2F7CA3;
    --fisioterapi: #7A5BB5;
    --fisio: #7A5BB5;
  }

  @page {
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

  body {
    font-family: 'Poppins', sans-serif;
    font-size: 8.5pt;
    line-height: 1.35;
    color: var(--black);
  }

  table {
    table-layout: fixed;
  }

  table td {
    word-wrap: break-word;
    overflow-wrap: break-word;
    word-break: break-word;
  }

  .report-header {
    position: fixed;
    left: -50px;
    right: -50px;
    background-color: var(--green);
    color: #ffffff;
  }

  .header-table {
    width: 100%;
    border-collapse: collapse;
  }

  .header-logo-cell {
    width: 6%;
    vertical-align: middle;
  }

  .header-logo {
    width: 40px;
  }

  .header-brand-cell {
    width: 47%;
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
    width: 47%;
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
    left: 0;
    right: 0;
    height: 2px;
    background-color: var(--gray);
  }

  .report-footer {
    position: fixed;
    font-size: 7.2pt;
    color: var(--gray);
  }

  .footer-table {
    position: inherit;
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
  }

  .meta-label {
    font-size: 6.5pt;
    color: var(--gray);
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 0.4px;
  }

  .meta-value {
    font-weight: 600;
    line-height: 1;
  }

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
  }

  .data-table th {
    background-color: var(--light-green);
    color: var(--gray);
    font-weight: 700;
    font-size: 7pt;
    padding: 3px 8px 6px 8px;
    border: 1px solid var(--light-green-border);
  }

  .data-table td {
    padding: 4px 8px 6px 8px;
    border: 1px solid var(--light-green-border);
    vertical-align: top;
    line-height: 1;
  }

  .data-table tr.total-row td {
    background-color: var(--light-green);
    font-weight: 800;
    padding: 2px 8px 6px 8px;
  }

  .almost-bullet {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 2px;
    margin-right: 4px;
  }

  .font-bold {
    font-weight: 800;
  }

  .text-center {
    text-align: center;
  }

  .text-left {
    text-align: left;
  }
</style>