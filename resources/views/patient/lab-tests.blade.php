@extends('layouts.app')
@section('title', __('patient.lab_tests.page_title'))
@section('content')
@php
  $selectedId = $selectedType?->facility_type_id;
  $navQuery = fn (?int $typeId = null, bool $all = false) => array_filter([
      'facility_type_id' => $typeId,
      'view' => $all ? 'all' : null,
      'q' => $searchQuery !== '' ? $searchQuery : null,
  ]);
@endphp

<div class="patient-lab-tests-page">
  <nav class="patient-appointments-workspace-nav" aria-label="{{ __('patient.lab_tests.patient_navigation') }}">
    <a href="{{ route('patient.doctors') }}"><i class="bi bi-calendar2-check" aria-hidden="true"></i><span>{{ __('dashboard.patient.appointments_title') }}</span></a>
    <a href="{{ route('patient.prescriptions') }}"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span>{{ __('dashboard.patient.stat_prescriptions') }}</span></a>
    <a class="active" href="{{ route('patient.lab-tests') }}"><i class="bi bi-eyedropper" aria-hidden="true"></i><span>{{ __('dashboard.patient.lab_tests') }}</span></a>
    <a href="{{ route('patient.facilities') }}"><i class="bi bi-hospital" aria-hidden="true"></i><span>{{ __('dashboard.patient.hospital_services') }}</span></a>
    <a href="{{ route('patient.records') }}"><i class="bi bi-clipboard2" aria-hidden="true"></i><span>{{ __('dashboard.patient.records_short') }}</span></a>
  </nav>

  <section class="patient-lab-tests-hero" aria-labelledby="patient-lab-tests-title">
    <div class="patient-lab-tests-breadcrumb">
      <a href="{{ route('patient.dashboard') }}">{{ __('patient.lab_tests.home') }}</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <span>{{ __('patient.lab_tests.title') }}</span>
    </div>

    <div class="patient-lab-tests-hero-grid">
      <div class="patient-lab-tests-hero-copy">
        <h1 id="patient-lab-tests-title">{{ __('patient.lab_tests.hero_title') }}</h1>
        <p>{{ __('patient.lab_tests.hero_desc') }}</p>

        <form method="GET" action="{{ route('patient.lab-tests') }}" class="patient-lab-tests-search-form">
          <div class="patient-lab-tests-search-input">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" name="q" value="{{ $searchQuery }}" placeholder="{{ __('patient.lab_tests.search_placeholder') }}" aria-label="{{ __('patient.lab_tests.search_aria') }}">
          </div>
          @if ($selectedId)<input type="hidden" name="facility_type_id" value="{{ $selectedId }}">@endif
          @if ($showAll)<input type="hidden" name="view" value="all">@endif
          <button type="submit" class="patient-lab-tests-search-button">{{ __('patient.lab_tests.search_button') }}</button>
        </form>

        <div class="patient-lab-tests-filter-row" aria-label="{{ __('patient.lab_tests.test_filters') }}">
          <a class="patient-lab-tests-filter-chip {{ $showAll ? 'active' : '' }}" href="{{ route('patient.lab-tests', ['view' => 'all']) }}">{{ __('patient.lab_tests.all_tests') }}</a>
          @foreach ($testTypes as $type)
            <a class="patient-lab-tests-filter-chip {{ !$showAll && $selectedId === $type->facility_type_id ? 'active' : '' }}"
               href="{{ route('patient.lab-tests', ['facility_type_id' => $type->facility_type_id]) }}">{{ $type->name }}</a>
          @endforeach
        </div>
      </div>

      <div class="patient-lab-tests-hero-visual" aria-hidden="true">
        <img
          src="{{ asset('images/patient/LabTest.png') }}"
          class="patient-lab-tests-hero-illustration"
          alt=""
        >
      </div>
    </div>
  </section>

  <div class="patient-lab-tests-content-grid">
    <section class="patient-lab-tests-main-card" aria-labelledby="patient-lab-tests-results-title">
      <div class="patient-lab-tests-section-heading">
        <div>
          <h2 id="patient-lab-tests-results-title">{{ $showAll ? __('patient.lab_tests.available_tests') : __('patient.lab_tests.compare_prices') }}</h2>
          <span>
            @if ($showAll)
              {{ trans_choice('patient.lab_tests.test_count', $testSummaries->count(), ['count' => $testSummaries->count()]) }}
            @else
              {{ trans_choice('patient.lab_tests.hospital_count', $offerings->count(), ['count' => $offerings->count()]) }}
            @endif
          </span>
        </div>
        @unless ($showAll)
          <form method="GET" action="{{ route('patient.lab-tests') }}" class="patient-lab-tests-sort-form">
            <label for="lab-test-sort">{{ __('patient.lab_tests.sort_by') }}</label>
            <select id="lab-test-sort" name="sort" class="patient-lab-tests-sort-select" onchange="this.form.submit()">
              <option value="lowest" {{ $sortOrder === 'lowest' ? 'selected' : '' }}>{{ __('patient.lab_tests.lowest_price') }}</option>
              <option value="highest" {{ $sortOrder === 'highest' ? 'selected' : '' }}>Highest price</option>
            </select>
            @if ($selectedId)<input type="hidden" name="facility_type_id" value="{{ $selectedId }}">@endif
            @if ($searchQuery !== '')<input type="hidden" name="q" value="{{ $searchQuery }}">@endif
          </form>
        @endunless
      </div>

      @if ($showAll)
        <div class="patient-lab-tests-test-list">
          @forelse ($testSummaries as $summary)
            <article class="patient-lab-tests-test-card">
              <span class="patient-lab-tests-test-icon"><i class="bi bi-flask" aria-hidden="true"></i></span>
              <div class="patient-lab-tests-test-copy">
                <span>{{ __('patient.lab_tests.diagnostic_test') }}</span>
                <h3>{{ $summary['type']->name }}</h3>
                <p>{{ $summary['type']->unit_label }}</p>
              </div>
              <div class="patient-lab-tests-test-price">
                <small>{{ __('patient.lab_tests.from_price') }}</small>
                <strong>{{ $summary['min_price'] !== null ? 'BDT ' . number_format($summary['min_price'], 2) : '—' }}</strong>
                <span>{{ trans_choice('patient.lab_tests.provider_count', $summary['hospital_count'], ['count' => $summary['hospital_count']]) }}</span>
              </div>
              <a class="patient-lab-tests-primary-action" href="{{ route('patient.lab-tests', ['facility_type_id' => $summary['type']->facility_type_id]) }}">{{ __('patient.lab_tests.compare_action') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </article>
          @empty
            <div class="patient-lab-tests-empty-state">
              <span><i class="bi bi-search" aria-hidden="true"></i></span>
              <h3>{{ __('patient.lab_tests.no_tests_found') }}</h3>
              <p>{{ __('patient.lab_tests.no_tests_found_desc') }}</p>
              <a href="{{ route('patient.lab-tests', ['view' => 'all']) }}">{{ __('patient.lab_tests.clear_search') }}</a>
            </div>
          @endforelse
        </div>
      @else
        @if ($selectedType)
          <div class="patient-lab-tests-selected-test">
            <div>
              <span class="patient-lab-tests-selected-icon"><i class="bi bi-flask" aria-hidden="true"></i></span>
              <div>
                <strong>{{ $selectedType->name }}</strong>
                <span>{{ __('patient.lab_tests.diagnostic_test') }} · {{ $selectedType->unit_label }}</span>
              </div>
            </div>
            <a href="{{ route('patient.lab-tests', ['view' => 'all']) }}">{{ __('patient.lab_tests.change_test') }}</a>
          </div>
        @endif

        <div class="patient-lab-tests-hospital-list">
          @forelse ($offerings as $index => $offering)
            @php $hospital = $offering->hospital; @endphp
            <article class="patient-lab-tests-hospital-row {{ $index === 0 ? 'cheapest' : '' }}">
              <span class="patient-lab-tests-hospital-icon"><i class="bi bi-hospital" aria-hidden="true"></i></span>
              <div class="patient-lab-tests-hospital-copy">
                <h3>{{ $hospital->hospital_name }}</h3>
                <div class="patient-lab-tests-hospital-meta">
                  <span><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ $hospital->fullAddress() }}</span>
                  @if ($hospital->reviews_count > 0)
                    <span><i class="bi bi-star-fill patient-lab-tests-star" aria-hidden="true"></i>{{ number_format($hospital->reviews_avg_rating, 1) }} ({{ $hospital->reviews_count }})</span>
                  @else
                    <span>{{ __('patient.lab_tests.no_ratings') }}</span>
                  @endif
                  <span><i class="bi bi-person-vcard" aria-hidden="true"></i>{{ $hospital->account?->uidTag() ?? '—' }}</span>
                </div>
              </div>
              <div class="patient-lab-tests-price-block">
                <small>{{ __('patient.lab_tests.price') }}</small>
                <strong>BDT {{ number_format($offering->price, 2) }}</strong>
                <span>{{ $selectedType?->unit_label }}</span>
                @if ($index === 0)<em>{{ __('patient.lab_tests.cheapest') }}</em>@endif
              </div>
              <div class="patient-lab-tests-row-actions">
                <a class="patient-lab-tests-outline-action" href="{{ route('patient.hospitals.show', $hospital) }}">{{ __('patient.lab_tests.view_hospital') }}</a>
                <a class="patient-lab-tests-primary-action" href="{{ route('patient.facilities.book', $offering) }}">{{ __('patient.lab_tests.book_test') }}</a>
              </div>
            </article>
          @empty
            <div class="patient-lab-tests-empty-state">
              <span><i class="bi bi-hospital" aria-hidden="true"></i></span>
              <h3>{{ __('patient.lab_tests.no_prices') }}</h3>
              <p>{{ __('patient.lab_tests.no_prices_desc') }}</p>
              @if ($searchQuery !== '')<a href="{{ route('patient.lab-tests', ['facility_type_id' => $selectedId]) }}">{{ __('patient.lab_tests.clear_search') }}</a>@endif
            </div>
          @endforelse
        </div>
      @endif
    </section>

    <aside class="patient-lab-tests-sidebar">
      <section class="patient-lab-tests-side-card" aria-labelledby="patient-lab-tests-bookings-title">
        <div class="patient-lab-tests-side-heading">
          <h2 id="patient-lab-tests-bookings-title">{{ __('patient.lab_tests.my_bookings') }}</h2>
          <a href="{{ route('patient.facility-bookings') }}">{{ __('patient.lab_tests.view_all') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>

        @forelse ($recentBookings as $booking)
          <a class="patient-lab-tests-booking-item" href="{{ route('patient.facility-bookings') }}">
            <span class="patient-lab-tests-booking-date">
              <small>{{ $booking->booking_date->format('M') }}</small>
              <strong>{{ $booking->booking_date->format('d') }}</strong>
              <span>{{ $booking->booking_date->format('D') }}</span>
            </span>
            <span class="patient-lab-tests-booking-copy">
              <strong>{{ $booking->facilityType->name }}</strong>
              <small>{{ $booking->hospital->hospital_name }}</small>
              <span>{{ __('patient.lab_tests.serial') }} #{{ $booking->serial_number }}</span>
            </span>
            <span class="patient-lab-tests-booking-status patient-lab-tests-booking-status-{{ $booking->status }}">{{ $booking->statusLabel() }}</span>
          </a>
        @empty
          <div class="patient-lab-tests-booking-empty">
            <span><i class="bi bi-calendar2-check" aria-hidden="true"></i></span>
            <strong>{{ __('patient.lab_tests.no_bookings') }}</strong>
            <p>{{ __('patient.lab_tests.no_bookings_desc') }}</p>
          </div>
        @endforelse
      </section>

      <section class="patient-lab-tests-side-card patient-lab-tests-prescribed-card">
        <span class="patient-lab-tests-side-icon"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i></span>
        <div>
          <strong>{{ __('patient.lab_tests.prescribed_title') }}</strong>
          <p>{{ __('patient.lab_tests.prescribed_desc') }}</p>
          <a href="{{ route('patient.prescriptions') }}">{{ __('patient.lab_tests.open_prescriptions') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
      </section>

      <section class="patient-lab-tests-support-card">
        <span class="patient-lab-tests-side-icon"><i class="bi bi-headset" aria-hidden="true"></i></span>
        <div>
          <strong>{{ __('patient.lab_tests.need_help') }}</strong>
          <p>{{ __('patient.lab_tests.support_desc') }}</p>
          <a href="{{ route('help.index') }}">{{ __('patient.lab_tests.contact_support') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
      </section>
    </aside>
  </div>
</div>

<footer class="patient-dashboard-footer patient-lab-tests-footer">
  <div class="patient-dashboard-footer-brand">
    <span class="patient-dashboard-footer-dot" aria-hidden="true"></span>
    <div><strong>{{ __('dashboard.patient.platform_name') }}</strong><small>{{ __('home.brand_tagline') }}</small></div>
  </div>
  <p>{{ __('dashboard.patient.footer_tagline') }}</p>
</footer>
@endsection
