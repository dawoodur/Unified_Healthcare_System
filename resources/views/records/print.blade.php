<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $patient->full_name }} — Full Health Record</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@300;400;500;600;700;800&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  :root {
    --print-navy: #082b43;
    --print-navy-soft: #173c54;
    --print-teal: #079b98;
    --print-teal-dark: #087f7c;
    --print-text: #18364d;
    --print-muted: #74899b;
    --print-line: #d7e4e9;
    --print-line-soft: #e7eef1;
    --print-bg: #f3f8fa;
    --print-soft: #f7fafb;
    --print-soft-teal: #edf8f8;
    --print-white: #ffffff;
  }

  * { box-sizing: border-box; }
  html { background: var(--print-bg); }
  body {
    margin: 0;
    color: var(--print-text);
    background: var(--print-bg);
    font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    font-size: 14px;
    line-height: 1.5;
  }
  button, a { font: inherit; }
  a { color: inherit; }

  /* Patient shell shown on screen only. This page stays standalone so no
     shared application layout or any other patient screen is affected. */
  .health-print-app-header {
    height: 74px;
    display: flex;
    align-items: center;
    background: #fff;
    border-bottom: 1px solid #dce7eb;
  }
  .health-print-header-inner,
  .health-print-shell {
    width: min(1310px, calc(100% - 96px));
    margin-inline: auto;
  }
  .health-print-header-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 32px;
  }
  .health-print-brand {
    display: inline-flex;
    align-items: center;
    gap: 11px;
    color: var(--print-navy);
    text-decoration: none;
  }
  .health-print-brand-mark {
    width: 40px;
    height: 40px;
    display: inline-grid;
    place-items: center;
    flex: 0 0 auto;
    border-radius: 9px;
    color: #fff;
    background: #0b7f88;
    font-size: 20px;
  }
  .health-print-brand-copy {
    display: flex;
    flex-direction: column;
    line-height: 1.05;
  }
  .health-print-brand-copy strong {
    color: #082941;
    font-size: 16px;
    font-weight: 800;
    letter-spacing: -0.02em;
  }
  .health-print-brand-copy small {
    margin-top: 4px;
    color: #6f879b;
    font-size: 8px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }
  .health-print-header-nav {
    display: flex;
    align-items: center;
    gap: 26px;
    margin-left: auto;
  }
  .health-print-header-link {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: #294a62;
    text-decoration: none;
    font-size: 12px;
    font-weight: 650;
    white-space: nowrap;
  }
  .health-print-header-link i { color: #59758a; font-size: 13px; }
  .health-print-profile {
    display: grid;
    grid-template-columns: minmax(0, auto) 38px;
    align-items: center;
    gap: 11px;
    padding-left: 20px;
    border-left: 1px solid #dbe5e9;
  }
  .health-print-profile-copy { text-align: right; line-height: 1.18; }
  .health-print-profile-copy strong {
    display: block;
    color: #0a2940;
    font-size: 12px;
    font-weight: 800;
  }
  .health-print-profile-copy small {
    display: block;
    margin-top: 2px;
    color: #7a8ea0;
    font-size: 9px;
  }
  .health-print-profile-avatar {
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    overflow: hidden;
    border-radius: 50%;
    color: #6f8aa0;
    background: #e3edf1;
    font-size: 18px;
  }
  .health-print-profile-avatar img { width: 100%; height: 100%; object-fit: cover; }

  .health-print-workspace-nav {
    min-height: 64px;
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    margin-top: 28px;
    padding: 7px;
    background: #fff;
    border: 1px solid #d7e4e9;
    border-radius: 14px;
  }
  .health-print-workspace-nav a {
    min-height: 46px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: #30516a;
    text-decoration: none;
    font-size: 12px;
    font-weight: 650;
    border-right: 1px solid #dce6ea;
  }
  .health-print-workspace-nav a:last-child { border-right: 0; }
  .health-print-workspace-nav a.active {
    color: #007f7d;
    background: #e6f5f5;
    border-right-color: transparent;
    border-radius: 10px;
    font-weight: 800;
  }
  .health-print-workspace-nav a i { color: #009b9a; font-size: 15px; }

  .health-print-main { padding: 20px 0 56px; }
  .health-print-toolbar {
    min-height: 88px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 28px;
    margin-bottom: 18px;
    padding: 18px 20px;
    background: #fff;
    border: 1px solid var(--print-line);
    border-radius: 14px;
  }
  .health-print-toolbar-copy { min-width: 0; }
  .health-print-breadcrumb {
    display: flex;
    align-items: center;
    gap: 7px;
    margin-bottom: 7px;
    color: #75899c;
    font-size: 10px;
    font-weight: 600;
  }
  .health-print-breadcrumb a { color: #058e8b; text-decoration: none; }
  .health-print-breadcrumb i { color: #9aaab5; font-size: 8px; }
  .health-print-toolbar h1 {
    margin: 0;
    color: var(--print-navy);
    font-size: 20px;
    line-height: 1.15;
    font-weight: 800;
    letter-spacing: -0.025em;
  }
  .health-print-toolbar p {
    margin: 4px 0 0;
    color: var(--print-muted);
    font-size: 11px;
  }
  .health-print-toolbar-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 0 0 auto;
  }
  .health-print-btn {
    min-height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 8px 14px;
    border-radius: 8px;
    text-decoration: none;
    cursor: pointer;
    font-size: 11px;
    font-weight: 750;
  }
  .health-print-btn-secondary {
    color: #31536a;
    background: #fff;
    border: 1px solid #c8d9e0;
  }
  .health-print-btn-primary {
    color: #fff;
    background: var(--print-teal);
    border: 1px solid var(--print-teal);
  }

  /* The document is intentionally restrained: this is a printable clinical
     summary, not another dashboard. */
  .health-print-document {
    width: min(100%, 1040px);
    margin: 0 auto;
    padding: 38px 42px 34px;
    background: #fff;
    border: 1px solid #d5e2e7;
    border-radius: 12px;
    box-shadow: 0 16px 38px rgba(20, 58, 79, 0.08);
  }
  .health-print-document-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 32px;
    padding-bottom: 20px;
    border-bottom: 2px solid #153c55;
  }
  .health-print-document-brand {
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .health-print-document-brand-mark {
    width: 36px;
    height: 36px;
    display: grid;
    place-items: center;
    border-radius: 8px;
    color: #fff;
    background: #0a8e8c;
    font-size: 18px;
  }
  .health-print-document-brand strong {
    display: block;
    color: #0a2940;
    font-size: 15px;
    font-weight: 800;
    line-height: 1.1;
  }
  .health-print-document-brand small {
    display: block;
    margin-top: 3px;
    color: #8194a3;
    font-size: 8px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }
  .health-print-document-title { text-align: right; }
  .health-print-document-title h2 {
    margin: 0;
    color: #0b2b43;
    font-size: 20px;
    font-weight: 800;
    letter-spacing: -0.025em;
  }
  .health-print-document-title p {
    margin: 5px 0 0;
    color: #778b9d;
    font-size: 9px;
    line-height: 1.45;
  }

  .health-print-patient-summary {
    display: grid;
    grid-template-columns: minmax(0, 1.45fr) minmax(270px, 0.75fr);
    gap: 24px;
    padding: 22px 0;
    border-bottom: 1px solid #dfe8ec;
  }
  .health-print-patient-name small,
  .health-print-section-kicker {
    display: block;
    margin-bottom: 5px;
    color: #078b88;
    font-size: 8px;
    font-weight: 800;
    letter-spacing: 0.11em;
    text-transform: uppercase;
  }
  .health-print-patient-name h3 {
    margin: 0;
    color: #0b2a42;
    font-size: 22px;
    line-height: 1.15;
    font-weight: 800;
    letter-spacing: -0.03em;
  }
  .health-print-patient-name p {
    margin: 7px 0 0;
    color: #6e8395;
    font-size: 10px;
  }
  .health-print-identity-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px 18px;
    align-self: center;
  }
  .health-print-identity-item span,
  .health-print-fact span {
    display: block;
    color: #8093a2;
    font-size: 8px;
    font-weight: 600;
  }
  .health-print-identity-item strong,
  .health-print-fact strong {
    display: block;
    margin-top: 2px;
    color: #173a52;
    font-size: 10px;
    font-weight: 750;
    overflow-wrap: anywhere;
  }

  .health-print-snapshot {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
    padding: 18px 0 4px;
  }
  .health-print-fact {
    min-height: 58px;
    padding: 11px 12px;
    background: #f6fafb;
    border: 1px solid #dde9ed;
    border-radius: 8px;
  }
  .health-print-fact i {
    margin-right: 5px;
    color: #079794;
    font-size: 10px;
  }

  .health-print-section {
    margin-top: 24px;
    break-inside: avoid;
    page-break-inside: avoid;
  }
  .health-print-section-heading {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 10px;
    padding-bottom: 7px;
    border-bottom: 1px solid #cfdee4;
  }
  .health-print-section-heading h3 {
    margin: 0;
    color: #12344c;
    font-size: 12px;
    line-height: 1.3;
    font-weight: 800;
    letter-spacing: -0.01em;
  }
  .health-print-section-heading span {
    color: #8b9ca9;
    font-size: 8px;
  }
  .health-print-two-column {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 9px 26px;
  }
  .health-print-field {
    display: grid;
    grid-template-columns: 128px minmax(0, 1fr);
    gap: 10px;
    padding: 5px 0;
    border-bottom: 1px solid #edf2f4;
    font-size: 9px;
  }
  .health-print-field > span { color: #7b8fa0; }
  .health-print-field > strong {
    color: #24465c;
    font-weight: 650;
    white-space: pre-wrap;
  }
  .health-print-note {
    margin: 9px 0 0;
    padding: 9px 11px;
    color: #5f778a;
    background: #f8fbfc;
    border-left: 3px solid #9cd2d0;
    font-size: 9px;
  }
  .health-print-care-team {
    margin: 0;
    color: #31536a;
    font-size: 10px;
  }
  .health-print-empty {
    margin: 0;
    padding: 10px 12px;
    color: #8395a3;
    background: #fafcfc;
    border: 1px solid #e6edef;
    border-radius: 6px;
    font-size: 9px;
    font-style: italic;
  }
  .health-print-allergy {
    padding: 12px 14px;
    background: #fff8f7;
    border: 1px solid #ecd1cc;
    border-left: 4px solid #c35a4f;
    border-radius: 7px;
  }
  .health-print-allergy .health-print-section-heading {
    margin-bottom: 8px;
    border-bottom-color: #ead7d2;
  }
  .health-print-allergy .health-print-section-heading h3 { color: #8e4038; }

  .health-print-table-wrap { width: 100%; overflow-x: auto; }
  .health-print-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: auto;
    color: #294a60;
    font-size: 8.6px;
  }
  .health-print-table th {
    padding: 7px 8px;
    color: #668093;
    background: #f5f9fa;
    border: 1px solid #dfe8ec;
    text-align: left;
    vertical-align: top;
    font-size: 7.5px;
    font-weight: 800;
    letter-spacing: 0.03em;
    text-transform: uppercase;
  }
  .health-print-table td {
    padding: 7px 8px;
    border: 1px solid #e2eaee;
    text-align: left;
    vertical-align: top;
    overflow-wrap: anywhere;
  }
  .health-print-table tbody tr:nth-child(even) td { background: #fbfcfd; }
  .health-print-table .muted { color: #7f92a1; }
  .health-print-rx-meta {
    margin: 12px 0 5px;
    color: #718697;
    font-size: 8.5px;
    font-weight: 650;
  }
  .health-print-rx-meta:first-of-type { margin-top: 0; }

  .health-print-document-footer {
    margin-top: 28px;
    padding-top: 12px;
    color: #8193a1;
    border-top: 1px solid #dce6ea;
    font-size: 8px;
    line-height: 1.55;
  }
  .health-print-document-footer strong { color: #516b7e; }

  @media (max-width: 900px) {
    .health-print-header-inner,
    .health-print-shell { width: calc(100% - 32px); }
    .health-print-header-link span { display: none; }
    .health-print-workspace-nav { overflow-x: auto; grid-template-columns: repeat(5, minmax(150px, 1fr)); }
    .health-print-document { padding: 28px 24px; }
    .health-print-patient-summary { grid-template-columns: 1fr; }
    .health-print-snapshot { grid-template-columns: repeat(2, 1fr); }
  }
  @media (max-width: 620px) {
    .health-print-profile-copy { display: none; }
    .health-print-header-nav { gap: 15px; }
    .health-print-toolbar { align-items: flex-start; flex-direction: column; }
    .health-print-toolbar-actions { width: 100%; }
    .health-print-btn { flex: 1; }
    .health-print-document-header { flex-direction: column; }
    .health-print-document-title { text-align: left; }
    .health-print-identity-grid,
    .health-print-two-column,
    .health-print-snapshot { grid-template-columns: 1fr; }
    .health-print-field { grid-template-columns: 1fr; gap: 2px; }
  }

  @page { size: A4 portrait; margin: 12mm; }
  @media print {
    html, body { background: #fff !important; }
    body { font-size: 9pt; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .no-print { display: none !important; }
    .health-print-main { padding: 0; }
    .health-print-shell { width: 100%; margin: 0; }
    .health-print-document {
      width: 100%;
      max-width: none;
      margin: 0;
      padding: 0;
      border: 0;
      border-radius: 0;
      box-shadow: none;
    }
    .health-print-document-header { padding-bottom: 12px; }
    .health-print-patient-summary { padding-block: 14px; }
    .health-print-snapshot { padding-top: 12px; }
    .health-print-section { margin-top: 16px; }
    .health-print-table-wrap { overflow: visible; }
    .health-print-table { font-size: 7.5pt; }
    .health-print-table th { font-size: 6.5pt; }
    .health-print-field,
    .health-print-note,
    .health-print-empty,
    .health-print-care-team { font-size: 7.5pt; }
    .health-print-rx-meta { font-size: 7pt; }
    .health-print-section,
    .health-print-allergy,
    .health-print-table tr { break-inside: avoid; page-break-inside: avoid; }
    .health-print-document-footer { font-size: 6.7pt; }
  }

  /* Bangla typography — Noto Sans Bengali, with Plus Jakarta Sans retained
     for English/numbers in mixed-language print content. */
  html:lang(bn) body {
    font-family: 'Plus Jakarta Sans', 'Noto Sans Bengali', sans-serif;
    font-weight: 300;
    font-synthesis: none;
  }

  html:lang(bn) p,
  html:lang(bn) small,
  html:lang(bn) td,
  html:lang(bn) dd,
  html:lang(bn) li {
    font-family: 'Plus Jakarta Sans', 'Noto Sans Bengali', sans-serif;
    font-weight: 300 !important;
  }

  html:lang(bn) label,
  html:lang(bn) th,
  html:lang(bn) button,
  html:lang(bn) [class*="badge"],
  html:lang(bn) [class*="status"],
  html:lang(bn) [class*="kicker"],
  html:lang(bn) [class*="eyebrow"] {
    font-family: 'Plus Jakarta Sans', 'Noto Sans Bengali', sans-serif;
    font-weight: 500 !important;
  }

  html:lang(bn) h3,
  html:lang(bn) h4,
  html:lang(bn) h5,
  html:lang(bn) h6,
  html:lang(bn) strong,
  html:lang(bn) b,
  html:lang(bn) [class*="card-title"],
  html:lang(bn) [class*="section-title"] {
    font-family: 'Plus Jakarta Sans', 'Noto Sans Bengali', sans-serif;
    font-weight: 600 !important;
  }

  html:lang(bn) h2,
  html:lang(bn) [class*="heading"] h2 {
    font-family: 'Plus Jakarta Sans', 'Noto Sans Bengali', sans-serif;
    font-weight: 700 !important;
  }

  html:lang(bn) h1,
  html:lang(bn) [class*="hero"] h1 {
    font-family: 'Plus Jakarta Sans', 'Noto Sans Bengali', sans-serif;
    font-weight: 800 !important;
  }

</style>
</head>
<body>

@php
  $viewer = auth()->user();
  $viewerPhoto = $viewer?->photoUrl();
  $latestVital = $vitals->first();
  $latestBloodPressure = $latestVital ? $latestVital->bloodPressureLabel() : 'Not on file';

  $careTeam = $prescriptions->pluck('doctor')->filter()->unique('doctor_id');
  $diagnoses = $prescriptions->filter(fn ($rx) => filled($rx->diagnosis_notes));
  $recommendedFacilityItems = $prescriptions->flatMap(
      fn ($rx) => $rx->facilityItems->map(fn ($item) => ['rx' => $rx, 'item' => $item])
  );
  $labAndImaging = $records->whereIn('record_type', ['lab_result', 'facility_report']);
  $notesAndDocuments = $records->whereIn('record_type', ['diagnosis_note', 'uploaded_document']);
@endphp

@if (!$printedByDoctor && $viewer?->role === 'patient')
  <header class="health-print-app-header no-print">
    <div class="health-print-header-inner">
      <a class="health-print-brand" href="{{ route('patient.dashboard') }}" aria-label="Telemedicine dashboard">
        <span class="health-print-brand-mark"><i class="bi bi-plus-lg" aria-hidden="true"></i></span>
        <span class="health-print-brand-copy">
          <strong>Telemedicine</strong>
          <small>Connected Healthcare</small>
        </span>
      </a>

      <nav class="health-print-header-nav" aria-label="Patient navigation">
        <a class="health-print-header-link" href="{{ route('patient.dashboard') }}">Dashboard</a>
        <a class="health-print-header-link" href="{{ route('notifications.index') }}"><i class="bi bi-bell" aria-hidden="true"></i><span>Notifications</span></a>
        <a class="health-print-header-link" href="{{ route('report.index') }}"><i class="bi bi-exclamation-square" aria-hidden="true"></i><span>Report an issue</span></a>
        <div class="health-print-profile">
          <span class="health-print-profile-copy">
            <strong>{{ $viewer->displayName() }}</strong>
            <small>Patient</small>
          </span>
          <span class="health-print-profile-avatar">
            @if ($viewerPhoto)
              <img src="{{ $viewerPhoto }}" alt="">
            @else
              <i class="bi bi-person-fill" aria-hidden="true"></i>
            @endif
          </span>
        </div>
      </nav>
    </div>
  </header>

  <div class="health-print-shell no-print">
    <nav class="health-print-workspace-nav" aria-label="Patient workspace navigation">
      <a href="{{ route('patient.doctors') }}"><i class="bi bi-calendar2-check" aria-hidden="true"></i><span>Appointments</span></a>
      <a href="{{ route('patient.prescriptions') }}"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span>Prescriptions</span></a>
      <a href="{{ route('patient.lab-tests') }}"><i class="bi bi-eyedropper" aria-hidden="true"></i><span>Lab Tests</span></a>
      <a href="{{ route('patient.facilities') }}"><i class="bi bi-hospital" aria-hidden="true"></i><span>Hospital Services</span></a>
      <a class="active" href="{{ route('patient.records') }}"><i class="bi bi-clipboard2" aria-hidden="true"></i><span>Records</span></a>
    </nav>
  </div>
@endif

<main class="health-print-main">
  <div class="health-print-shell">
    <section class="health-print-toolbar no-print" aria-labelledby="health-print-page-title">
      <div class="health-print-toolbar-copy">
        <div class="health-print-breadcrumb">
          @if (!$printedByDoctor && $viewer?->role === 'patient')
            <a href="{{ route('patient.dashboard') }}">Home</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <a href="{{ route('patient.records') }}">Medical Records</a>
          @else
            <a href="{{ url()->previous() }}">Medical Records</a>
          @endif
          <i class="bi bi-chevron-right" aria-hidden="true"></i>
          <span>Full Health Record</span>
        </div>
        <h1 id="health-print-page-title">Full health record preview</h1>
        <p>Review the complete record below, then print it or save it as a PDF.</p>
      </div>
      <div class="health-print-toolbar-actions">
        <a class="health-print-btn health-print-btn-secondary" href="{{ url()->previous() }}"><i class="bi bi-arrow-left" aria-hidden="true"></i>Back</a>
        <button class="health-print-btn health-print-btn-primary" type="button" onclick="window.print()"><i class="bi bi-printer" aria-hidden="true"></i>Print / Save PDF</button>
      </div>
    </section>

    <article class="health-print-document">
      <header class="health-print-document-header">
        <div class="health-print-document-brand">
          <span class="health-print-document-brand-mark"><i class="bi bi-plus-lg" aria-hidden="true"></i></span>
          <span>
            <strong>Telemedicine</strong>
            <small>Connected Healthcare</small>
          </span>
        </div>
        <div class="health-print-document-title">
          <h2>Patient Health Record</h2>
          <p>
            Generated {{ now()->format('M j, Y · g:i A') }}<br>
            @if ($printedByDoctor)
              Prepared by Dr. {{ $printedByDoctor->full_name }}
            @else
              Prepared for the patient
            @endif
          </p>
        </div>
      </header>

      <section class="health-print-patient-summary">
        <div class="health-print-patient-name">
          <small>Patient</small>
          <h3>{{ $patient->full_name }}</h3>
          <p>{{ $patient->account?->uidTag() ?: 'Patient ID not available' }} · Registered {{ $patient->account?->created_at?->format('M j, Y') ?: 'date not available' }}</p>
        </div>
        <div class="health-print-identity-grid">
          <div class="health-print-identity-item"><span>Age</span><strong>{{ $patient->age ?? 'Not on file' }}</strong></div>
          <div class="health-print-identity-item"><span>Gender</span><strong>{{ filled($patient->gender) ? ucfirst($patient->gender) : 'Not on file' }}</strong></div>
          <div class="health-print-identity-item"><span>Email</span><strong>{{ $patient->account?->email ?: 'Not on file' }}</strong></div>
          <div class="health-print-identity-item"><span>Mobile</span><strong>{{ $patient->account?->mobile ?: 'Not on file' }}</strong></div>
          <div class="health-print-identity-item"><span>Address</span><strong>{{ $patient->address ?: 'Not on file' }}</strong></div>
        </div>
      </section>

      <div class="health-print-snapshot" aria-label="Health snapshot">
        <div class="health-print-fact"><span><i class="bi bi-droplet" aria-hidden="true"></i>Blood group</span><strong>{{ $patient->blood_group ?: 'Not on file' }}</strong></div>
        <div class="health-print-fact"><span><i class="bi bi-activity" aria-hidden="true"></i>Latest blood pressure</span><strong>{{ $latestBloodPressure }}</strong></div>
        <div class="health-print-fact"><span><i class="bi bi-heart-pulse" aria-hidden="true"></i>Cholesterol</span><strong>{{ $healthProfile?->cholesterol_status ?: 'Not on file' }}</strong></div>
        <div class="health-print-fact"><span><i class="bi bi-clipboard2-pulse" aria-hidden="true"></i>Diabetes risk</span><strong>{{ $healthProfile?->diabetes_risk ?: 'Not on file' }}</strong></div>
      </div>

      <section class="health-print-section">
        <div class="health-print-section-heading">
          <h3>Personal Health Context</h3>
          <span>Patient-maintained information</span>
        </div>
        @if ($healthProfile)
          <div class="health-print-two-column">
            <div class="health-print-field"><span>Diet</span><strong>{{ $healthProfile->diet_notes ?: 'Not on file' }}</strong></div>
            <div class="health-print-field"><span>Therapy</span><strong>{{ $healthProfile->therapy_notes ?: 'Not on file' }}</strong></div>
            <div class="health-print-field"><span>Major health risks</span><strong>{{ $healthProfile->major_health_risks ?: 'Not on file' }}</strong></div>
            <div class="health-print-field"><span>Chronic conditions</span><strong>{{ $healthProfile->chronic_conditions ?: 'Not on file' }}</strong></div>
          </div>
          @if ($healthProfile->lifestyle_notes)
            <p class="health-print-note"><strong>Lifestyle notes:</strong> {{ $healthProfile->lifestyle_notes }}</p>
          @endif
        @else
          <p class="health-print-empty">No patient-maintained health context on file.</p>
        @endif
      </section>

      @if ($allergies->isNotEmpty())
        <section class="health-print-section health-print-allergy">
          <div class="health-print-section-heading">
            <h3><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Known Allergies</h3>
            <span>{{ $allergies->count() }} on file</span>
          </div>
          <div class="health-print-table-wrap">
            <table class="health-print-table">
              <thead><tr><th>Allergen</th><th>Reaction</th><th>Noted</th></tr></thead>
              <tbody>
                @foreach ($allergies as $allergy)
                  <tr>
                    <td>{{ $allergy->allergen }}</td>
                    <td>{{ $allergy->reaction ?? '—' }}</td>
                    <td>{{ $allergy->created_at->format('M j, Y') }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </section>
      @else
        <section class="health-print-section">
          <div class="health-print-section-heading"><h3>Allergies</h3></div>
          <p class="health-print-empty">No known allergies on file.</p>
        </section>
      @endif

      <section class="health-print-section">
        <div class="health-print-section-heading">
          <h3>Vital Signs History</h3>
          <span>{{ $vitals->count() }} {{ $vitals->count() === 1 ? 'reading' : 'readings' }}</span>
        </div>
        @if ($vitals->isEmpty())
          <p class="health-print-empty">No vitals recorded yet.</p>
        @else
          <div class="health-print-table-wrap">
            <table class="health-print-table">
              <thead><tr><th>Date</th><th>Blood pressure</th><th>Pulse</th><th>Temp °C</th><th>Height cm</th><th>Weight kg</th><th>BMI</th><th>Recorded by</th></tr></thead>
              <tbody>
                @foreach ($vitals as $vital)
                  @php
                    $bmi = ($vital->height_cm && $vital->weight_kg)
                        ? round($vital->weight_kg / (($vital->height_cm / 100) ** 2), 1)
                        : null;
                  @endphp
                  <tr>
                    <td>{{ $vital->recorded_at->format('M j, Y') }}</td>
                    <td>{{ $vital->bloodPressureLabel() }}</td>
                    <td>{{ $vital->heart_rate ?? '—' }}</td>
                    <td>{{ $vital->temperature_celsius ?? '—' }}</td>
                    <td>{{ $vital->height_cm ?? '—' }}</td>
                    <td>{{ $vital->weight_kg ?? '—' }}</td>
                    <td>{{ $bmi ?? '—' }}</td>
                    <td>Dr. {{ $vital->recordedByDoctor->full_name ?? '—' }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </section>

      <section class="health-print-section">
        <div class="health-print-section-heading"><h3>Care Team</h3></div>
        @if ($careTeam->isEmpty())
          <p class="health-print-empty">No treating doctors on file yet.</p>
        @else
          <p class="health-print-care-team">{{ $careTeam->map(fn ($doctor) => 'Dr. ' . $doctor->full_name)->implode(', ') }}</p>
        @endif
      </section>

      <section class="health-print-section">
        <div class="health-print-section-heading">
          <h3>Diagnosis Summary</h3>
          <span>{{ $diagnoses->count() }} {{ $diagnoses->count() === 1 ? 'entry' : 'entries' }}</span>
        </div>
        @if ($diagnoses->isEmpty())
          <p class="health-print-empty">No diagnosis notes on file.</p>
        @else
          <div class="health-print-table-wrap">
            <table class="health-print-table">
              <thead><tr><th>Date</th><th>Doctor</th><th>Diagnosis</th></tr></thead>
              <tbody>
                @foreach ($diagnoses as $rx)
                  <tr>
                    <td>{{ $rx->issued_at->format('M j, Y') }}</td>
                    <td>Dr. {{ $rx->doctor->full_name ?? '—' }}</td>
                    <td>{{ $rx->diagnosis_notes }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </section>

      <section class="health-print-section">
        <div class="health-print-section-heading">
          <h3>Current Medications</h3>
          <span>{{ $prescriptions->count() }} {{ $prescriptions->count() === 1 ? 'prescription' : 'prescriptions' }}</span>
        </div>
        @if ($prescriptions->isEmpty())
          <p class="health-print-empty">No prescriptions issued yet.</p>
        @else
          @foreach ($prescriptions as $rx)
            <p class="health-print-rx-meta">{{ $rx->issued_at->format('M j, Y') }} · Dr. {{ $rx->doctor->full_name ?? '—' }}</p>
            <div class="health-print-table-wrap">
              <table class="health-print-table">
                <thead><tr><th>Medicine</th><th>For illness</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th>Notes</th></tr></thead>
                <tbody>
                  @foreach ($rx->items as $item)
                    <tr>
                      <td>{{ $item->medicine->generic_name ?? '—' }}@if($item->medicine?->brand_name) ({{ $item->medicine->brand_name }})@endif</td>
                      <td>{{ $item->for_illness ?? '—' }}</td>
                      <td>{{ $item->dosage ?? '—' }}</td>
                      <td>{{ $item->frequency ?? '—' }}</td>
                      <td>{{ $item->duration_days ? $item->duration_days . ' day(s)' : '—' }}</td>
                      <td>{{ $item->notes ?? '—' }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endforeach
        @endif
      </section>

      <section class="health-print-section">
        <div class="health-print-section-heading">
          <h3>Recommended Tests &amp; Operations</h3>
          <span>{{ $recommendedFacilityItems->count() }} {{ $recommendedFacilityItems->count() === 1 ? 'item' : 'items' }}</span>
        </div>
        @if ($recommendedFacilityItems->isEmpty())
          <p class="health-print-empty">None recommended.</p>
        @else
          <div class="health-print-table-wrap">
            <table class="health-print-table">
              <thead><tr><th>Date</th><th>Doctor</th><th>Test / Operation</th><th>Category</th><th>Notes</th></tr></thead>
              <tbody>
                @foreach ($recommendedFacilityItems as $entry)
                  <tr>
                    <td>{{ $entry['rx']->issued_at->format('M j, Y') }}</td>
                    <td>Dr. {{ $entry['rx']->doctor->full_name ?? '—' }}</td>
                    <td>{{ $entry['item']->facilityType->name ?? '—' }}</td>
                    <td class="muted">{{ $entry['item']->facilityType->category->category_name ?? '—' }}</td>
                    <td class="muted">{{ $entry['item']->notes ?? '—' }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </section>

      <section class="health-print-section">
        <div class="health-print-section-heading">
          <h3>Lab &amp; Imaging Results</h3>
          <span>{{ $labAndImaging->count() }} {{ $labAndImaging->count() === 1 ? 'record' : 'records' }}</span>
        </div>
        @if ($labAndImaging->isEmpty())
          <p class="health-print-empty">No lab or imaging results on file.</p>
        @else
          <div class="health-print-table-wrap">
            <table class="health-print-table">
              <thead><tr><th>Type</th><th>Description</th><th>Date</th><th>File attached</th></tr></thead>
              <tbody>
                @foreach ($labAndImaging as $record)
                  <tr>
                    <td>{{ $record->typeLabel() }}</td>
                    <td>{{ $record->description ?? '—' }}</td>
                    <td>{{ $record->created_at->format('M j, Y') }}</td>
                    <td>{{ $record->file_path ? 'Yes' : 'No' }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </section>

      <section class="health-print-section">
        <div class="health-print-section-heading">
          <h3>Clinical Notes &amp; Documents</h3>
          <span>{{ $notesAndDocuments->count() }} {{ $notesAndDocuments->count() === 1 ? 'record' : 'records' }}</span>
        </div>
        @if ($notesAndDocuments->isEmpty())
          <p class="health-print-empty">No additional notes or documents on file.</p>
        @else
          <div class="health-print-table-wrap">
            <table class="health-print-table">
              <thead><tr><th>Type</th><th>Description</th><th>Date</th><th>File attached</th></tr></thead>
              <tbody>
                @foreach ($notesAndDocuments as $record)
                  <tr>
                    <td>{{ $record->typeLabel() }}</td>
                    <td>{{ $record->description ?? '—' }}</td>
                    <td>{{ $record->created_at->format('M j, Y') }}</td>
                    <td>{{ $record->file_path ? 'Yes' : 'No' }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </section>

      <footer class="health-print-document-footer">
        <strong>Record scope:</strong> This summary was generated automatically from the Telemedicine platform's records on file for {{ $patient->full_name }} ({{ $patient->account?->uidTag() }}). It reflects data entered through this platform only and may not represent a complete medical history.
      </footer>
    </article>
  </div>
</main>

</body>
</html>
