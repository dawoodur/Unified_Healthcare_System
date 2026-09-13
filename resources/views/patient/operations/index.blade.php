@extends('layouts.app')
@section('title', __('patient.operations.title'))
@section('content')
@php
  $allRequests = $pending->concat($completed);
  $requestedCount = $allRequests->where('status', 'requested')->count();
  $offeredCount = $allRequests->where('status', 'offered')->count();
  $acceptedCount = $allRequests->where('status', 'accepted')->count();
  $activeCount = $pending->count();
  $historyCount = $completed->count();
@endphp

<div class="patient-surgery-page">
  <nav class="patient-appointments-workspace-nav" aria-label="{{ __('patient.operations.patient_navigation') }}">
    <a href="{{ route('patient.doctors') }}"><i class="bi bi-calendar2-check" aria-hidden="true"></i><span>{{ __('dashboard.patient.appointments_title') }}</span></a>
    <a href="{{ route('patient.prescriptions') }}"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span>{{ __('dashboard.patient.stat_prescriptions') }}</span></a>
    <a href="{{ route('patient.lab-tests') }}"><i class="bi bi-eyedropper" aria-hidden="true"></i><span>{{ __('dashboard.patient.lab_tests') }}</span></a>
    <a class="active" href="{{ route('patient.facilities') }}"><i class="bi bi-hospital" aria-hidden="true"></i><span>{{ __('dashboard.patient.hospital_services') }}</span></a>
    <a href="{{ route('patient.records') }}"><i class="bi bi-clipboard2" aria-hidden="true"></i><span>{{ __('dashboard.patient.records_short') }}</span></a>
  </nav>

  <section class="patient-surgery-hero" aria-labelledby="patient-surgery-title">
    <div class="patient-surgery-hero-copy">
      <div class="patient-surgery-breadcrumb">
        <a href="{{ route('patient.dashboard') }}">{{ __('patient.operations.home') }}</a>
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
        <a href="{{ route('patient.facilities') }}">{{ __('patient.operations.hospital_services') }}</a>
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
        <span>{{ __('patient.operations.title') }}</span>
      </div>

      <h1 id="patient-surgery-title">{{ __('patient.operations.title') }}</h1>
      <p>{{ __('patient.operations.intro_plain') }}</p>
    </div>

    <div class="patient-surgery-hero-note" aria-label="{{ __('patient.operations.separate_flow_title') }}">
      <span><i class="bi bi-scissors" aria-hidden="true"></i></span>
      <div>
        <strong>{{ __('patient.operations.separate_flow_title') }}</strong>
        <p>{{ __('patient.operations.separate_flow_desc') }}</p>
      </div>
    </div>
  </section>

  <div class="patient-surgery-layout">
    <main class="patient-surgery-main">
      <section class="patient-surgery-summary" aria-label="{{ __('patient.operations.status_summary') }}">
        <div class="patient-surgery-summary-item patient-surgery-summary-item-active">
          <span><i class="bi bi-activity" aria-hidden="true"></i></span>
          <div><strong>{{ __('patient.operations.active') }}</strong><small>{{ __('patient.operations.active_desc') }}</small></div>
          <em>{{ $activeCount }}</em>
        </div>
        <div class="patient-surgery-summary-item">
          <span><i class="bi bi-hourglass-split" aria-hidden="true"></i></span>
          <div><strong>{{ __('patient.operations.awaiting_response') }}</strong><small>{{ __('patient.operations.awaiting_response_desc') }}</small></div>
          <em>{{ $requestedCount }}</em>
        </div>
        <div class="patient-surgery-summary-item">
          <span><i class="bi bi-envelope-check" aria-hidden="true"></i></span>
          <div><strong>{{ __('patient.operations.offer_received') }}</strong><small>{{ __('patient.operations.offer_received_desc') }}</small></div>
          <em>{{ $offeredCount }}</em>
        </div>
        <div class="patient-surgery-summary-item">
          <span><i class="bi bi-calendar2-check" aria-hidden="true"></i></span>
          <div><strong>{{ __('patient.operations.confirmed') }}</strong><small>{{ __('patient.operations.confirmed_desc') }}</small></div>
          <em>{{ $acceptedCount }}</em>
        </div>
        <div class="patient-surgery-summary-item">
          <span><i class="bi bi-clock-history" aria-hidden="true"></i></span>
          <div><strong>{{ __('patient.operations.history') }}</strong><small>{{ __('patient.operations.history_desc') }}</small></div>
          <em>{{ $historyCount }}</em>
        </div>
      </section>

      <section class="patient-surgery-section" id="surgery-active" aria-labelledby="surgery-active-title">
        <header class="patient-surgery-section-header">
          <span class="patient-surgery-section-icon"><i class="bi bi-activity" aria-hidden="true"></i></span>
          <div>
            <h2 id="surgery-active-title">{{ __('patient.operations.active_requests') }}</h2>
            <p>{{ __('patient.operations.active_requests_desc') }}</p>
          </div>
          <strong>{{ $activeCount }}</strong>
        </header>

        @if ($pending->isEmpty())
          <div class="patient-surgery-empty">
            <span><i class="bi bi-scissors" aria-hidden="true"></i></span>
            <div>
              <strong>{{ __('patient.operations.no_pending') }}</strong>
              <p>{{ __('patient.operations.no_active_desc') }}</p>
            </div>
            <a href="{{ route('patient.facilities') }}">{{ __('patient.operations.find_surgery_services') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
          </div>
        @else
          <div class="patient-surgery-list">
            @foreach ($pending as $req)
              @include('patient.operations.partials.row', ['req' => $req])
            @endforeach
          </div>
        @endif
      </section>

      <section class="patient-surgery-section patient-surgery-section-history" id="surgery-history" aria-labelledby="surgery-history-title">
        <header class="patient-surgery-section-header">
          <span class="patient-surgery-section-icon"><i class="bi bi-clock-history" aria-hidden="true"></i></span>
          <div>
            <h2 id="surgery-history-title">{{ __('patient.operations.request_history') }}</h2>
            <p>{{ __('patient.operations.request_history_desc') }}</p>
          </div>
          <strong>{{ $historyCount }}</strong>
        </header>

        @if ($completed->isEmpty())
          <div class="patient-surgery-empty">
            <span><i class="bi bi-folder2-open" aria-hidden="true"></i></span>
            <div>
              <strong>{{ __('patient.operations.no_completed') }}</strong>
              <p>{{ __('patient.operations.no_history_desc') }}</p>
            </div>
          </div>
        @else
          <div class="patient-surgery-list">
            @foreach ($completed as $req)
              @include('patient.operations.partials.row', ['req' => $req])
            @endforeach
          </div>
        @endif
      </section>
    </main>

    <aside class="patient-surgery-sidebar">
      <section class="patient-surgery-side-card patient-surgery-side-card-primary">
        <span class="patient-surgery-side-icon"><i class="bi bi-hospital" aria-hidden="true"></i></span>
        <div>
          <h2>{{ __('patient.operations.need_service_title') }}</h2>
          <p>{{ __('patient.operations.need_service_desc') }}</p>
        </div>
        <a href="{{ route('patient.facilities') }}">{{ __('patient.operations.find_hospitals') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
      </section>

      <section class="patient-surgery-side-card">
        <span class="patient-surgery-side-icon"><i class="bi bi-calendar2-check" aria-hidden="true"></i></span>
        <div>
          <h2>{{ __('patient.operations.facility_bookings_title') }}</h2>
          <p>{{ __('patient.operations.facility_bookings_desc') }}</p>
        </div>
        <a href="{{ route('patient.facility-bookings') }}">{{ __('patient.operations.view_facility_bookings') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
      </section>

      <section class="patient-surgery-side-card">
        <span class="patient-surgery-side-icon"><i class="bi bi-chat-square-text" aria-hidden="true"></i></span>
        <div>
          <h2>{{ __('patient.operations.help_title') }}</h2>
          <p>{{ __('patient.operations.help_desc') }}</p>
        </div>
        <a href="{{ route('help.index') }}">{{ __('patient.operations.visit_help') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
      </section>

      <section class="patient-surgery-info-card">
        <div class="patient-surgery-info-heading">
          <span><i class="bi bi-info-circle-fill" aria-hidden="true"></i></span>
          <h2>{{ __('patient.operations.about_title') }}</h2>
        </div>
        <ol>
          <li>{{ __('patient.operations.about_step_1') }}</li>
          <li>{{ __('patient.operations.about_step_2') }}</li>
          <li>{{ __('patient.operations.about_step_3') }}</li>
          <li>{{ __('patient.operations.about_step_4') }}</li>
        </ol>
      </section>
    </aside>
  </div>
</div>

<footer class="patient-dashboard-footer patient-surgery-footer">
  <div class="patient-dashboard-footer-brand">
    <span class="patient-dashboard-footer-dot"></span>
    <div><strong>{{ __('dashboard.patient.platform_name') }}</strong><small>{{ __('home.brand_tagline') }}</small></div>
  </div>
  <p>{{ __('dashboard.patient.footer_tagline') }}</p>
</footer>
@endsection
