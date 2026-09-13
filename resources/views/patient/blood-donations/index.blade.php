@extends('layouts.app')
@section('title', __('patient.blood.page_title'))
@section('content')
@php
  $patient = auth()->user()->patient;
  $lastDonation = $history->first();
  $donationCount = $history->count();
@endphp

<div class="patient-blood-page">
  <nav class="patient-appointments-workspace-nav" aria-label="{{ __('patient.blood.patient_navigation') }}">
    <a href="{{ route('patient.doctors') }}"><i class="bi bi-calendar2-check" aria-hidden="true"></i><span>{{ __('dashboard.patient.appointments_title') }}</span></a>
    <a href="{{ route('patient.prescriptions') }}"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span>{{ __('dashboard.patient.stat_prescriptions') }}</span></a>
    <a href="{{ route('patient.lab-tests') }}"><i class="bi bi-eyedropper" aria-hidden="true"></i><span>{{ __('dashboard.patient.lab_tests') }}</span></a>
    <a href="{{ route('patient.facilities') }}"><i class="bi bi-hospital" aria-hidden="true"></i><span>{{ __('dashboard.patient.hospital_services') }}</span></a>
    <a href="{{ route('patient.records') }}"><i class="bi bi-clipboard2" aria-hidden="true"></i><span>{{ __('dashboard.patient.records_short') }}</span></a>
  </nav>

  <section class="patient-blood-hero" aria-labelledby="patient-blood-title">
    <div class="patient-blood-breadcrumb">
      <a href="{{ route('patient.dashboard') }}">{{ __('patient.blood.home') }}</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <span>{{ __('patient.blood.title') }}</span>
    </div>

    <div class="patient-blood-hero-grid">
      <div>
        <span class="patient-blood-eyebrow">
          <i class="bi bi-droplet-half" aria-hidden="true"></i>
          {{ __('patient.blood.community_care') }}
        </span>
        <h1 id="patient-blood-title">{{ __('patient.blood.hero_title') }}</h1>
        <p>{{ __('patient.blood.hero_desc') }}</p>
      </div>

      <div class="patient-blood-group-card">
        <span class="patient-blood-group-icon"><i class="bi bi-droplet-fill" aria-hidden="true"></i></span>
        <div>
          <small>{{ __('patient.blood.your_blood_group') }}</small>
          <strong>{{ $patient->blood_group }}</strong>
        </div>
      </div>
    </div>
  </section>

  <section class="patient-blood-status-card {{ $isEligible ? 'patient-blood-status-eligible' : 'patient-blood-status-waiting' }}">
    <div class="patient-blood-status-main">
      <span class="patient-blood-status-icon">
        <i class="bi {{ $isEligible ? 'bi-check2-circle' : 'bi-clock-history' }}" aria-hidden="true"></i>
      </span>

      <div>
        <small>{{ __('patient.blood.eligibility_status') }}</small>
        @if ($isEligible)
          <h2>{{ __('patient.blood.eligible_title') }}</h2>
          <p>{{ __('patient.blood.eligible_desc') }}</p>
        @else
          <h2>{{ __('patient.blood.waiting_title') }}</h2>
          <p>{{ __('patient.blood.waiting_desc', ['date' => $nextEligibleDate->format('M j, Y')]) }}</p>
        @endif
      </div>
    </div>

    <div class="patient-blood-status-action">
      @if ($isEligible)
        <a href="{{ route('patient.blood-donations.create') }}" class="patient-blood-primary-action">
          <i class="bi bi-plus-circle" aria-hidden="true"></i>
          {{ __('patient.blood.log_donation') }}
        </a>
      @else
        <button type="button" class="patient-blood-primary-action" disabled aria-disabled="true">
          <i class="bi bi-lock" aria-hidden="true"></i>
          {{ __('patient.blood.not_eligible_button') }}
        </button>
      @endif
    </div>
  </section>

  <section class="patient-blood-overview" aria-label="{{ __('patient.blood.overview_label') }}">
    <article>
      <span class="patient-blood-overview-icon"><i class="bi bi-calendar2-check" aria-hidden="true"></i></span>
      <div>
        <small>{{ __('patient.blood.last_donation') }}</small>
        <strong>
          {{ $lastDonation ? $lastDonation->donated_at->format('M j, Y') : __('patient.blood.never_donated') }}
        </strong>
      </div>
    </article>

    <article>
      <span class="patient-blood-overview-icon"><i class="bi bi-arrow-repeat" aria-hidden="true"></i></span>
      <div>
        <small>{{ __('patient.blood.donation_interval') }}</small>
        <strong>{{ __('patient.blood.every_three_months') }}</strong>
      </div>
    </article>

    <article>
      <span class="patient-blood-overview-icon"><i class="bi bi-clipboard2-heart" aria-hidden="true"></i></span>
      <div>
        <small>{{ __('patient.blood.total_logged') }}</small>
        <strong>{{ $donationCount }}</strong>
      </div>
    </article>
  </section>

  <section class="patient-blood-history-card" aria-labelledby="patient-blood-history-title">
    <header class="patient-blood-history-heading">
      <div>
        <span class="patient-blood-history-icon"><i class="bi bi-clock-history" aria-hidden="true"></i></span>
        <div>
          <h2 id="patient-blood-history-title">{{ __('patient.blood.history') }}</h2>
          <p>{{ __('patient.blood.history_desc') }}</p>
        </div>
      </div>
      <span class="patient-blood-history-count">{{ trans_choice('patient.blood.donation_count', $donationCount, ['count' => $donationCount]) }}</span>
    </header>

    @if ($history->isEmpty())
      <div class="patient-blood-empty">
        <span><i class="bi bi-droplet" aria-hidden="true"></i></span>
        <div>
          <strong>{{ __('patient.blood.no_history_title') }}</strong>
          <p>{{ __('patient.blood.no_history') }}</p>
        </div>
      </div>
    @else
      <div class="patient-blood-history-list">
        @foreach ($history as $donation)
          <article class="patient-blood-history-row">
            <div class="patient-blood-date-badge">
              <small>{{ strtoupper($donation->donated_at->format('M')) }}</small>
              <strong>{{ $donation->donated_at->format('d') }}</strong>
              <span>{{ $donation->donated_at->format('Y') }}</span>
            </div>

            <div class="patient-blood-history-copy">
              <small>{{ __('patient.blood.donation_record') }}</small>
              <strong>{{ $donation->donated_at->format('D, M j Y') }}</strong>
            </div>

            <div class="patient-blood-history-hospital">
              <span><i class="bi bi-hospital" aria-hidden="true"></i>{{ __('patient.blood.hospital') }}</span>
              <strong>{{ $donation->hospital->hospital_name ?? __('patient.blood.not_specified') }}</strong>
            </div>

            <span class="patient-blood-history-status">
              <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
              {{ __('patient.blood.logged') }}
            </span>
          </article>
        @endforeach
      </div>
    @endif
  </section>

  <section class="patient-blood-info-strip">
    <div>
      <span><i class="bi bi-info-circle" aria-hidden="true"></i></span>
      <div>
        <strong>{{ __('patient.blood.how_it_works_title') }}</strong>
        <p>{{ __('patient.blood.how_it_works_desc') }}</p>
      </div>
    </div>

    <a href="{{ route('help.index') }}">
      {{ __('patient.blood.help_action') }}
      <i class="bi bi-arrow-right" aria-hidden="true"></i>
    </a>
  </section>
</div>

<footer class="patient-dashboard-footer patient-blood-footer">
  <div class="patient-dashboard-footer-brand">
    <span class="patient-dashboard-footer-dot"></span>
    <div><strong>{{ __('dashboard.patient.platform_name') }}</strong><small>{{ __('home.brand_tagline') }}</small></div>
  </div>
  <p>{{ __('dashboard.patient.footer_tagline') }}</p>
</footer>
@endsection
