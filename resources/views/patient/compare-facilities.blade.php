@extends('layouts.app')
@section('title', __('patient.facilities.page_title'))
@section('content')
@php
  $selectedOfferingHospitalIds = $selectedType ? $offerings->pluck('hospital_id') : collect();
  $topComparisonOfferings = $selectedType ? $offerings->take(3) : collect();
@endphp

<div class="patient-facilities-page">
  <nav class="patient-appointments-workspace-nav" aria-label="{{ __('patient.facilities.patient_navigation') }}">
    <a href="{{ route('patient.doctors') }}"><i class="bi bi-calendar2-check" aria-hidden="true"></i><span>{{ __('dashboard.patient.appointments_title') }}</span></a>
    <a href="{{ route('patient.prescriptions') }}"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span>{{ __('dashboard.patient.stat_prescriptions') }}</span></a>
    <a href="{{ route('patient.lab-tests') }}"><i class="bi bi-eyedropper" aria-hidden="true"></i><span>{{ __('dashboard.patient.lab_tests') }}</span></a>
    <a class="active" href="{{ route('patient.facilities') }}"><i class="bi bi-hospital" aria-hidden="true"></i><span>{{ __('dashboard.patient.hospital_services') }}</span></a>
    <a href="{{ route('patient.records') }}"><i class="bi bi-clipboard2" aria-hidden="true"></i><span>{{ __('dashboard.patient.records_short') }}</span></a>
  </nav>

  <section class="patient-facilities-hero" aria-labelledby="patient-facilities-title">
    <div class="patient-facilities-breadcrumb">
      <a href="{{ route('patient.dashboard') }}">{{ __('patient.facilities.home') }}</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <span>{{ __('dashboard.patient.hospital_services') }}</span>
    </div>

    <div class="patient-facilities-hero-grid">
      <div class="patient-facilities-hero-copy">
        <h1 id="patient-facilities-title">{{ __('patient.facilities.hero_title') }}</h1>
        <p>{{ __('patient.facilities.hero_desc') }}</p>

        <form method="GET" action="{{ route('patient.facilities') }}" class="patient-facilities-search-form">
          <div class="patient-facilities-search-input">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" name="q" value="{{ $searchQuery }}" placeholder="{{ __('patient.facilities.search_placeholder') }}" aria-label="{{ __('patient.facilities.search_aria') }}">
          </div>
          @if ($selectedType)
            <input type="hidden" name="facility_type_id" value="{{ $selectedType->facility_type_id }}">
          @endif
          <button type="submit" class="patient-facilities-search-button">{{ __('patient.facilities.search_button') }}</button>
        </form>

        <div class="patient-facilities-filter-row" aria-label="{{ __('patient.facilities.facility_categories') }}">
          <a class="patient-facilities-filter-chip {{ !$selectedType ? 'active' : '' }}" href="{{ route('patient.facilities', array_filter(['q' => $searchQuery])) }}">
            {{ __('patient.facilities.all_facilities') }}
          </a>
          @foreach ($categories as $category)
            @php $categoryActive = $selectedType && (int) $selectedType->category_id === (int) $category->category_id; @endphp
            <details class="patient-facilities-category-menu {{ $categoryActive ? 'active' : '' }}">
              <summary>{{ $category->category_name }} <i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
              <div class="patient-facilities-category-options">
                @foreach ($category->facilityTypes as $type)
                  <a class="{{ $selectedType && $selectedType->facility_type_id === $type->facility_type_id ? 'active' : '' }}"
                     href="{{ route('patient.facilities', array_filter(['q' => $searchQuery, 'facility_type_id' => $type->facility_type_id])) }}">
                    <span>{{ $type->name }}</span>
                    <small>{{ $type->unit_label }}</small>
                  </a>
                @endforeach
              </div>
            </details>
          @endforeach
        </div>
      </div>

      <div class="patient-facilities-hero-visual" aria-hidden="true">
        <img
          src="{{ asset('images/patient/HospitalImage.png') }}"
          class="patient-facilities-hero-illustration"
          alt=""
        >
      </div>
    </div>
  </section>

  <div class="patient-facilities-content-grid">
    <section class="patient-facilities-list-section" aria-labelledby="patient-facilities-list-title">
      <div class="patient-facilities-section-heading">
        <div>
          <h2 id="patient-facilities-list-title">
            {{ $selectedType ? __('patient.facilities.price_results_title', ['facility' => $selectedType->name]) : __('patient.facilities.hospitals_and_facilities') }}
          </h2>
          <span>
            @if ($selectedType)
              {{ trans_choice('patient.facilities.offering_count', $offerings->count(), ['count' => $offerings->count()]) }}
            @else
              {{ trans_choice('patient.facilities.hospital_count', $hospitals->count(), ['count' => $hospitals->count()]) }}
            @endif
          </span>
        </div>
        @if ($selectedType)
          <a class="patient-facilities-clear-selection" href="{{ route('patient.facilities', array_filter(['q' => $searchQuery])) }}">{{ __('patient.facilities.show_all_hospitals') }}</a>
        @endif
      </div>

      <div class="patient-facilities-list">
        @if ($selectedType)
          @forelse ($offerings as $index => $offering)
            @php $hospital = $offering->hospital; @endphp
            <article class="patient-facilities-hospital-card {{ $index === 0 ? 'recommended' : '' }}">
              <div class="patient-facilities-hospital-icon" aria-hidden="true"><i class="bi bi-hospital"></i></div>
              <div class="patient-facilities-hospital-copy">
                <div class="patient-facilities-hospital-name-row">
                  <h3>{{ $hospital->hospital_name }}</h3>
                  @if ($index === 0)
                    <span class="patient-facilities-cheapest-badge"><i class="bi bi-star-fill" aria-hidden="true"></i>{{ __('patient.facilities.cheapest') }}</span>
                  @endif
                </div>
                <div class="patient-facilities-hospital-meta">
                  <span><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ $hospital->fullAddress() }}</span>
                  <span><i class="bi bi-person-vcard" aria-hidden="true"></i>{{ $hospital->account?->uidTag() ?? '—' }}</span>
                </div>
                <div class="patient-facilities-hospital-submeta">
                  <span>{{ __('patient.facilities.registration_no') }} {{ $hospital->registration_number }}</span>
                  @if ($hospital->reviews_count > 0)
                    <span><i class="bi bi-star-fill" aria-hidden="true"></i>{{ number_format($hospital->reviews_avg_rating, 1) }} ({{ $hospital->reviews_count }})</span>
                  @else
                    <span>{{ __('patient.facilities.no_ratings') }}</span>
                  @endif
                </div>
              </div>
              <div class="patient-facilities-price-block">
                <small>{{ $selectedType->name }}</small>
                <strong>BDT {{ number_format($offering->price, 2) }}</strong>
                <span>{{ $selectedType->unit_label }}</span>
              </div>
              <div class="patient-facilities-card-actions">
                <a class="patient-facilities-outline-action" href="{{ route('patient.hospitals.show', $hospital) }}">{{ __('patient.facilities.view_hospital') }}</a>
                @if ($selectedType->category->category_name === 'Surgery')
                  <a class="patient-facilities-primary-action" href="{{ route('patient.operations.create', $offering) }}">{{ __('patient.facilities.request') }}</a>
                @else
                  <a class="patient-facilities-primary-action" href="{{ route('patient.facilities.book', $offering) }}">{{ __('patient.facilities.book') }}</a>
                @endif
              </div>
            </article>
          @empty
            <div class="patient-facilities-empty-state">
              <span><i class="bi bi-hospital" aria-hidden="true"></i></span>
              <h3>{{ __('patient.facilities.no_price_yet') }}</h3>
              <a href="{{ route('patient.facilities') }}">{{ __('patient.facilities.show_all_hospitals') }}</a>
            </div>
          @endforelse
        @else
          @forelse ($hospitals as $hospital)
            <article class="patient-facilities-hospital-card">
              <div class="patient-facilities-hospital-icon" aria-hidden="true"><i class="bi bi-hospital"></i></div>
              <div class="patient-facilities-hospital-copy">
                <div class="patient-facilities-hospital-name-row"><h3>{{ $hospital->hospital_name }}</h3></div>
                <div class="patient-facilities-hospital-meta">
                  <span><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ $hospital->fullAddress() }}</span>
                  <span><i class="bi bi-person-vcard" aria-hidden="true"></i>{{ $hospital->account?->uidTag() ?? '—' }}</span>
                </div>
                <div class="patient-facilities-hospital-submeta">
                  <span>{{ __('patient.facilities.registration_no') }} {{ $hospital->registration_number }}</span>
                  <span>{{ trans_choice('patient.facilities.priced_service_count', $hospital->facilities_count, ['count' => $hospital->facilities_count]) }}</span>
                  @if ($hospital->reviews_count > 0)
                    <span><i class="bi bi-star-fill" aria-hidden="true"></i>{{ number_format($hospital->reviews_avg_rating, 1) }} ({{ $hospital->reviews_count }})</span>
                  @else
                    <span>{{ __('patient.facilities.no_ratings') }}</span>
                  @endif
                </div>
              </div>
              <div class="patient-facilities-card-actions patient-facilities-card-actions-single">
                <a class="patient-facilities-primary-action" href="{{ route('patient.hospitals.show', $hospital) }}">{{ __('patient.facilities.view_services') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
              </div>
            </article>
          @empty
            <div class="patient-facilities-empty-state">
              <span><i class="bi bi-search" aria-hidden="true"></i></span>
              <h3>{{ __('patient.facilities.no_match') }}</h3>
              <p>{{ __('patient.facilities.no_match_desc') }}</p>
              <a href="{{ route('patient.facilities') }}">{{ __('patient.facilities.clear_button') }}</a>
            </div>
          @endforelse
        @endif
      </div>
    </section>

    <aside class="patient-facilities-sidebar">
      <section class="patient-facilities-side-card" aria-labelledby="patient-facilities-compare-title">
        <div class="patient-facilities-side-heading">
          <h2 id="patient-facilities-compare-title">{{ __('patient.facilities.compare_hospitals') }}</h2>
          @if ($selectedType)
            <a href="{{ route('patient.facilities', array_filter(['q' => $searchQuery])) }}">{{ __('patient.facilities.clear_all') }}</a>
          @endif
        </div>

        @if (!$selectedType)
          <div class="patient-facilities-compare-empty">
            <span><i class="bi bi-bar-chart" aria-hidden="true"></i></span>
            <strong>{{ __('patient.facilities.no_service_selected') }}</strong>
            <p>{{ __('patient.facilities.choose_service_compare') }}</p>
          </div>
        @elseif ($topComparisonOfferings->isEmpty())
          <div class="patient-facilities-compare-empty">
            <span><i class="bi bi-bar-chart" aria-hidden="true"></i></span>
            <strong>{{ __('patient.facilities.no_price_yet') }}</strong>
          </div>
        @else
          <div class="patient-facilities-compare-service">
            <span>{{ __('patient.facilities.comparing') }}</span>
            <strong>{{ $selectedType->name }}</strong>
            <small>{{ $selectedType->unit_label }}</small>
          </div>
          <div class="patient-facilities-compare-list">
            @foreach ($topComparisonOfferings as $index => $offering)
              <a href="{{ route('patient.hospitals.show', $offering->hospital) }}">
                <span class="patient-facilities-compare-rank">{{ $index + 1 }}</span>
                <span class="patient-facilities-compare-copy">
                  <strong>{{ $offering->hospital->hospital_name }}</strong>
                  <small>{{ $offering->hospital->fullAddress() }}</small>
                </span>
                <span class="patient-facilities-compare-price">
                  @if ($index === 0)<small>{{ __('patient.facilities.cheapest') }}</small>@endif
                  <strong>BDT {{ number_format($offering->price, 2) }}</strong>
                </span>
              </a>
            @endforeach
          </div>
        @endif
      </section>

      <section class="patient-facilities-side-card" aria-labelledby="patient-facilities-bookings-title">
        <div class="patient-facilities-side-heading">
          <h2 id="patient-facilities-bookings-title">{{ __('patient.facilities.recent_bookings') }}</h2>
          <a href="{{ route('patient.facility-bookings') }}">{{ __('patient.facilities.view_all') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>

        @forelse ($recentBookings as $booking)
          <a class="patient-facilities-booking-item" href="{{ route('patient.facility-bookings') }}">
            <span class="patient-facilities-booking-date">
              <small>{{ $booking->booking_date->format('M') }}</small>
              <strong>{{ $booking->booking_date->format('d') }}</strong>
              <span>{{ $booking->booking_date->format('D') }}</span>
            </span>
            <span class="patient-facilities-booking-copy">
              <strong>{{ $booking->hospital->hospital_name }}</strong>
              <small>{{ $booking->facilityType->name }}</small>
              <span>#{{ $booking->serial_number }}</span>
            </span>
            <span class="patient-facilities-booking-status patient-facilities-booking-status-{{ $booking->status }}">{{ $booking->statusLabel() }}</span>
          </a>
        @empty
          <div class="patient-facilities-booking-empty">
            <span><i class="bi bi-calendar2-check" aria-hidden="true"></i></span>
            <strong>{{ __('patient.facilities.no_recent_bookings') }}</strong>
            <p>{{ __('patient.facilities.no_recent_bookings_desc') }}</p>
          </div>
        @endforelse
      </section>

      <section class="patient-facilities-support-card">
        <span class="patient-facilities-support-icon"><i class="bi bi-headset" aria-hidden="true"></i></span>
        <div>
          <strong>{{ __('patient.facilities.need_help') }}</strong>
          <p>{{ __('patient.facilities.support_desc') }}</p>
        </div>
        <a href="{{ route('help.index') }}">{{ __('patient.facilities.contact_support') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
      </section>
    </aside>
  </div>
</div>

<footer class="patient-dashboard-footer patient-facilities-footer">
  <div class="patient-dashboard-footer-brand">
    <span class="patient-dashboard-footer-dot"></span>
    <div><strong>{{ __('dashboard.patient.platform_name') }}</strong><small>{{ __('home.brand_tagline') }}</small></div>
  </div>
  <p>{{ __('dashboard.patient.footer_tagline') }}</p>
</footer>
@endsection
