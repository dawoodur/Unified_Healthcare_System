@extends('layouts.app')
@section('title', 'My Facility Bookings')
@section('content')
@php
  $pendingCount = $pending->count();
  $completedCount = $completed->count();
  $cancelledCount = $cancelled->count();
@endphp

<div class="patient-facility-bookings-page">
  <nav class="patient-appointments-workspace-nav" aria-label="Patient navigation">
    <a href="{{ route('patient.doctors') }}"><i class="bi bi-calendar2-check" aria-hidden="true"></i><span>{{ __('dashboard.patient.appointments_title') }}</span></a>
    <a href="{{ route('patient.prescriptions') }}"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span>{{ __('dashboard.patient.stat_prescriptions') }}</span></a>
    <a href="{{ route('patient.lab-tests') }}"><i class="bi bi-eyedropper" aria-hidden="true"></i><span>{{ __('dashboard.patient.lab_tests') }}</span></a>
    <a class="active" href="{{ route('patient.facilities') }}"><i class="bi bi-hospital" aria-hidden="true"></i><span>{{ __('dashboard.patient.hospital_services') }}</span></a>
    <a href="{{ route('patient.records') }}"><i class="bi bi-clipboard2" aria-hidden="true"></i><span>{{ __('dashboard.patient.records_short') }}</span></a>
  </nav>

  <section class="patient-facility-bookings-hero" aria-labelledby="patient-facility-bookings-title">
    <div class="patient-facility-bookings-breadcrumb">
      <a href="{{ route('patient.dashboard') }}">Home</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <a href="{{ route('patient.facilities') }}">Hospital Services</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <span>My Facility Bookings</span>
    </div>

    <h1 id="patient-facility-bookings-title">My Facility Bookings</h1>
    <p>View and manage your facility, test, and service bookings at hospitals. Track your upcoming visits, completed bookings, and cancelled bookings.</p>
  </section>

  <div class="patient-facility-bookings-layout">
    <main class="patient-facility-bookings-main">
      <nav class="patient-facility-bookings-status-nav" aria-label="Booking status sections">
        <a class="active" href="#facility-upcoming">
          <i class="bi bi-calendar2-check" aria-hidden="true"></i>
          <span>Upcoming / Active</span>
          <em>{{ $pendingCount }}</em>
        </a>
        <a href="#facility-completed">
          <i class="bi bi-check-circle" aria-hidden="true"></i>
          <span>Completed</span>
          <em>{{ $completedCount }}</em>
        </a>
        <a href="#facility-cancelled">
          <i class="bi bi-x-circle" aria-hidden="true"></i>
          <span>Cancelled</span>
          <em>{{ $cancelledCount }}</em>
        </a>
      </nav>

      <section class="patient-facility-bookings-section" id="facility-upcoming" aria-labelledby="facility-upcoming-title">
        <header class="patient-facility-bookings-section-header">
          <span class="patient-facility-bookings-section-icon"><i class="bi bi-calendar2-check" aria-hidden="true"></i></span>
          <div>
            <h2 id="facility-upcoming-title">Upcoming / Active Bookings</h2>
            <p>Your confirmed and ongoing facility bookings.</p>
          </div>
          <strong>{{ $pendingCount }}</strong>
        </header>

        @if ($pending->isEmpty())
          <div class="patient-facility-bookings-empty">
            <span><i class="bi bi-calendar2" aria-hidden="true"></i></span>
            <div>
              <strong>No upcoming facility bookings</strong>
              <p>When you book a hospital service, it will appear here.</p>
            </div>
            <a href="{{ route('patient.facilities') }}">Browse hospital services <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
          </div>
        @else
          @include('patient.partials.facility-booking-table', ['bookings' => $pending])
        @endif
      </section>

      <section class="patient-facility-bookings-section patient-facility-bookings-section-completed" id="facility-completed" aria-labelledby="facility-completed-title">
        <header class="patient-facility-bookings-section-header">
          <span class="patient-facility-bookings-section-icon"><i class="bi bi-check-lg" aria-hidden="true"></i></span>
          <div>
            <h2 id="facility-completed-title">Completed Bookings</h2>
            <p>Your completed hospital facility visits and services.</p>
          </div>
          <strong>{{ $completedCount }}</strong>
        </header>

        @if ($completed->isEmpty())
          <div class="patient-facility-bookings-empty">
            <span><i class="bi bi-check2-circle" aria-hidden="true"></i></span>
            <div>
              <strong>No completed bookings yet</strong>
              <p>Completed facility visits will be kept here for reference.</p>
            </div>
          </div>
        @else
          @include('patient.partials.facility-booking-table', ['bookings' => $completed])
        @endif
      </section>

      <section class="patient-facility-bookings-section patient-facility-bookings-section-cancelled" id="facility-cancelled" aria-labelledby="facility-cancelled-title">
        <header class="patient-facility-bookings-section-header">
          <span class="patient-facility-bookings-section-icon"><i class="bi bi-x-lg" aria-hidden="true"></i></span>
          <div>
            <h2 id="facility-cancelled-title">Cancelled Bookings</h2>
            <p>Your cancelled facility bookings.</p>
          </div>
          <strong>{{ $cancelledCount }}</strong>
        </header>

        @if ($cancelled->isEmpty())
          <div class="patient-facility-bookings-empty">
            <span><i class="bi bi-calendar-x" aria-hidden="true"></i></span>
            <div>
              <strong>No cancelled bookings</strong>
              <p>Cancelled facility bookings will appear here.</p>
            </div>
          </div>
        @else
          @include('patient.partials.facility-booking-table', ['bookings' => $cancelled])
        @endif
      </section>
    </main>

    <aside class="patient-facility-bookings-sidebar">
      <section class="patient-facility-bookings-side-card patient-facility-bookings-side-card-primary">
        <span class="patient-facility-bookings-side-icon"><i class="bi bi-hospital" aria-hidden="true"></i></span>
        <div>
          <h2>Book a new facility</h2>
          <p>Explore hospitals and book tests, procedures, and other available services.</p>
        </div>
        <a href="{{ route('patient.facilities') }}">Find hospitals <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
      </section>

      <section class="patient-facility-bookings-side-card">
        <span class="patient-facility-bookings-side-icon"><i class="bi bi-bandaid" aria-hidden="true"></i></span>
        <div>
          <h2>Surgery Requests</h2>
          <p>Surgery requests are managed separately from standard facility bookings.</p>
        </div>
        <a href="{{ route('patient.operations') }}">View surgery requests <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
      </section>

      <section class="patient-facility-bookings-side-card">
        <span class="patient-facility-bookings-side-icon"><i class="bi bi-chat-square-text" aria-hidden="true"></i></span>
        <div>
          <h2>Need help with your booking?</h2>
          <p>Visit the help center if you have questions about a facility booking.</p>
        </div>
        <a href="{{ route('help.index') }}">Visit Help Center <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
      </section>

      <section class="patient-facility-bookings-info-card">
        <div class="patient-facility-bookings-info-heading">
          <span><i class="bi bi-info-circle-fill" aria-hidden="true"></i></span>
          <h2>Booking information</h2>
        </div>
        <ul>
          <li>Same-day facility bookings use a hospital queue or serial number.</li>
          <li>Bed and occupancy bookings show the requested stay and expected discharge date.</li>
          <li>When a hospital provides a test or procedure report, it becomes part of your Medical Records.</li>
          <li>Surgery requests remain in the separate Surgery Requests workflow.</li>
        </ul>
      </section>
    </aside>
  </div>
</div>

<footer class="patient-dashboard-footer patient-facility-bookings-footer">
  <div class="patient-dashboard-footer-brand">
    <span class="patient-dashboard-footer-dot"></span>
    <div><strong>{{ __('dashboard.patient.platform_name') }}</strong><small>{{ __('home.brand_tagline') }}</small></div>
  </div>
  <p>{{ __('dashboard.patient.footer_tagline') }}</p>
</footer>
@endsection
