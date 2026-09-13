@extends('layouts.app')
@section('title', __('patient.facility_book.page_title'))
@section('content')
@php
  $categoryName = optional($offering->facilityType->category)->category_name ?: '—';
  $categoryIcon = match (strtolower($categoryName)) {
    'surgery' => 'bi-scissors',
    'diagnostic test', 'diagnostics' => 'bi-eyedropper',
    'imaging' => 'bi-broadcast-pin',
    'critical care' => 'bi-heart-pulse',
    'consultation room' => 'bi-stethoscope',
    default => 'bi-clipboard2-pulse',
  };
  $isOccupancy = (bool) $offering->facilityType->is_occupancy;
@endphp

<div class="patient-facility-book-page">
  <nav class="patient-appointments-workspace-nav" aria-label="{{ __('patient.facility_book.patient_navigation') }}">
    <a href="{{ route('patient.doctors') }}"><i class="bi bi-calendar2-check" aria-hidden="true"></i><span>{{ __('dashboard.patient.appointments_title') }}</span></a>
    <a href="{{ route('patient.prescriptions') }}"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span>{{ __('dashboard.patient.stat_prescriptions') }}</span></a>
    <a href="{{ route('patient.lab-tests') }}"><i class="bi bi-eyedropper" aria-hidden="true"></i><span>{{ __('dashboard.patient.lab_tests') }}</span></a>
    <a class="active" href="{{ route('patient.facilities') }}"><i class="bi bi-hospital" aria-hidden="true"></i><span>{{ __('dashboard.patient.hospital_services') }}</span></a>
    <a href="{{ route('patient.records') }}"><i class="bi bi-clipboard2" aria-hidden="true"></i><span>{{ __('dashboard.patient.records_short') }}</span></a>
  </nav>

  <section class="patient-facility-book-hero" aria-labelledby="facility-book-title">
    <div class="patient-facility-book-breadcrumb">
      <a href="{{ route('patient.dashboard') }}">{{ __('patient.facility_book.home') }}</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <a href="{{ route('patient.facilities') }}">{{ __('dashboard.patient.hospital_services') }}</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <a href="{{ route('patient.hospitals.show', $offering->hospital) }}">{{ $offering->hospital->hospital_name }}</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <span>{{ __('patient.facility_book.page_title') }}</span>
    </div>

    <div class="patient-facility-book-hero-grid">
      <div>
        <span class="patient-facility-book-eyebrow">{{ __('patient.facility_book.eyebrow') }}</span>
        <h1 id="facility-book-title">{{ __('patient.facility_book.hero_title', ['facility' => $offering->facilityType->name]) }}</h1>
        <p>{{ $isOccupancy ? __('patient.facility_book.hero_desc_occupancy') : __('patient.facility_book.hero_desc') }}</p>
      </div>
      <div class="patient-facility-book-hero-visual" aria-hidden="true">
        <span><i class="bi bi-calendar2-check"></i></span>
      </div>
    </div>
  </section>

  <div class="patient-facility-book-layout">
    <main class="patient-facility-book-main">
      <section class="patient-facility-book-card patient-facility-book-service-card">
        <div class="patient-facility-book-service-icon"><i class="bi {{ $categoryIcon }}" aria-hidden="true"></i></div>
        <div class="patient-facility-book-service-copy">
          <span>{{ __('patient.facility_book.selected_service') }}</span>
          <h2>{{ $offering->facilityType->name }}</h2>
          <p>{{ $categoryName }} · {{ $offering->facilityType->unit_label }}</p>
          <div>
            <span><i class="bi bi-hospital" aria-hidden="true"></i>{{ $offering->hospital->hospital_name }}</span>
            <span><i class="bi bi-people" aria-hidden="true"></i>{{ $isOccupancy ? __('patient.facility_book.capacity_beds', ['count' => $offering->daily_capacity]) : __('patient.facility_book.capacity_daily', ['count' => $offering->daily_capacity]) }}</span>
          </div>
        </div>
        <div class="patient-facility-book-service-price">
          <span>{{ __('patient.facility_book.price_label') }}</span>
          <strong>BDT {{ number_format($offering->price, 2) }}</strong>
          <small>{{ $offering->facilityType->unit_label }}</small>
        </div>
      </section>

      @if ($isOccupancy)
        <section class="patient-facility-book-card patient-facility-book-selection-card">
          <header class="patient-facility-book-section-heading">
            <span><i class="bi bi-door-open" aria-hidden="true"></i></span>
            <div>
              <h2>{{ __('patient.facility_book.current_availability') }}</h2>
              <p>{{ __('patient.facility_book.bed_note') }}</p>
            </div>
          </header>

          <div class="patient-facility-book-availability-strip">
            <div>
              <span>{{ __('patient.facility_book.available_now') }}</span>
              <strong>{{ __('patient.facility_book.beds_free', ['left' => $availability['spots_left'], 'max' => $availability['capacity']]) }}</strong>
            </div>
            @if ($availability['already_booked'])
              <span class="patient-facility-book-state muted-state">{{ __('patient.facility_book.already_booked') }}</span>
            @elseif ($availability['spots_left'] <= 0)
              <span class="patient-facility-book-state muted-state">{{ __('patient.facility_book.no_beds') }}</span>
            @else
              <span class="patient-facility-book-state available-state"><i class="bi bi-check-circle" aria-hidden="true"></i>{{ __('patient.facility_book.available') }}</span>
            @endif
          </div>

          @if (!$availability['already_booked'] && $availability['spots_left'] > 0)
            <form id="occupancy-booking-form" method="POST" action="{{ route('patient.facilities.book.store', $offering) }}" class="patient-facility-book-days-form">
              @csrf
              <label for="days">{{ __('patient.facility_book.days_question') }}</label>
              <div>
                <input type="number" id="days" name="days" min="1" max="90" value="1" required>
                <span>{{ __('patient.facility_book.days_label') }}</span>
              </div>
              <small>{{ __('patient.facility_book.days_help') }}</small>
            </form>
          @endif
        </section>
      @else
        <section class="patient-facility-book-card patient-facility-book-selection-card">
          <header class="patient-facility-book-section-heading">
            <span><i class="bi bi-calendar3" aria-hidden="true"></i></span>
            <div>
              <h2>{{ __('patient.facility_book.pick_date') }}</h2>
              <p>{{ __('patient.facility_book.pick_date_desc') }}</p>
            </div>
          </header>

          @if (empty($dates))
            <div class="patient-facility-book-empty">
              <i class="bi bi-calendar-x" aria-hidden="true"></i>
              <strong>{{ __('patient.facility_book.fully_booked') }}</strong>
            </div>
          @else
            <div class="patient-facility-book-calendar" id="facility-calendar"></div>
          @endif
        </section>
      @endif
    </main>

    <aside class="patient-facility-book-sidebar">
      <section class="patient-facility-book-card patient-facility-book-hospital-card">
        <div class="patient-facility-book-side-heading">
          <span><i class="bi bi-hospital" aria-hidden="true"></i></span>
          <h2>{{ __('patient.facility_book.hospital_information') }}</h2>
        </div>
        <strong>{{ $offering->hospital->hospital_name }}</strong>
        <p><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ $offering->hospital->fullAddress() }}</p>
        <p><i class="bi bi-patch-check" aria-hidden="true"></i>{{ __('patient.facilities.registration_no') }} {{ $offering->hospital->registration_number }}</p>
        <a href="{{ route('patient.hospitals.show', $offering->hospital) }}">{{ __('patient.facility_book.back_to_hospital') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
      </section>

      <section class="patient-facility-book-card patient-facility-book-summary-card">
        <div class="patient-facility-book-side-heading">
          <span><i class="bi bi-receipt" aria-hidden="true"></i></span>
          <h2>{{ __('patient.facility_book.booking_summary') }}</h2>
        </div>
        <p class="patient-facility-book-summary-intro">{{ __('patient.facility_book.booking_summary_desc') }}</p>

        <dl class="patient-facility-book-summary-list">
          <div><dt>{{ __('patient.facility_book.service_label') }}</dt><dd>{{ $offering->facilityType->name }}</dd></div>
          <div><dt>{{ __('patient.facility_book.hospital_label') }}</dt><dd>{{ $offering->hospital->hospital_name }}</dd></div>
          <div><dt>{{ __('patient.facility_book.unit_label') }}</dt><dd>{{ $offering->facilityType->unit_label }}</dd></div>
          @if ($isOccupancy)
            <div><dt>{{ __('patient.facility_book.availability') }}</dt><dd>{{ $availability['spots_left'] }}/{{ $availability['capacity'] }}</dd></div>
            @if (!$availability['already_booked'] && $availability['spots_left'] > 0)
              <div><dt>{{ __('patient.facility_book.expected_stay') }}</dt><dd id="facility-summary-days">1 {{ __('patient.facility_book.day_singular') }}</dd></div>
            @endif
          @else
            <div><dt>{{ __('patient.facility_book.selected_date') }}</dt><dd id="facility-summary-date">{{ __('patient.facility_book.not_selected') }}</dd></div>
            <div><dt>{{ __('patient.facility_book.remaining_capacity') }}</dt><dd id="facility-summary-capacity">—</dd></div>
          @endif
        </dl>

        <div class="patient-facility-book-total">
          <span>{{ $isOccupancy ? __('patient.facility_book.price_per_day') : __('patient.facility_book.price_label') }}</span>
          <strong>BDT {{ number_format($offering->price, 2) }}</strong>
        </div>

        @if ($isOccupancy)
          @if ($availability['already_booked'])
            <button type="button" class="patient-facility-book-confirm" disabled>{{ __('patient.facility_book.already_booked') }}</button>
          @elseif ($availability['spots_left'] <= 0)
            <button type="button" class="patient-facility-book-confirm" disabled>{{ __('patient.facility_book.no_beds') }}</button>
          @else
            <button type="submit" form="occupancy-booking-form" class="patient-facility-book-confirm">{{ __('patient.facility_book.confirm_booking') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
          @endif
        @else
          @if (empty($dates))
            <button type="button" class="patient-facility-book-confirm" disabled>{{ __('patient.facility_book.fully_booked_short') }}</button>
          @else
            <form id="dated-facility-booking-form" method="POST" action="{{ route('patient.facilities.book.store', $offering) }}">
              @csrf
              <input type="hidden" id="facility-selected-date" name="date" value="">
              <button type="submit" id="facility-confirm-booking" class="patient-facility-book-confirm" disabled>{{ __('patient.facility_book.confirm_booking') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
            </form>
          @endif
        @endif

        <small class="patient-facility-book-secure"><i class="bi bi-shield-check" aria-hidden="true"></i>{{ __('patient.facility_book.secure_note') }}</small>
      </section>
    </aside>
  </div>

  <footer class="patient-dashboard-footer patient-facility-book-footer">
    <div class="patient-dashboard-footer-brand">
      <span class="patient-dashboard-footer-dot" aria-hidden="true"></span>
      <div>
        <strong>{{ __('dashboard.patient.platform_name') }}</strong>
        <small>{{ __('home.brand_tagline') }}</small>
      </div>
    </div>
    <p>{{ __('dashboard.patient.footer_tagline') }}</p>
  </footer>
</div>

@if ($isOccupancy && !$availability['already_booked'] && $availability['spots_left'] > 0)
  @push('scripts')
    <script>
      (function () {
        const form = document.getElementById('occupancy-booking-form');
        const days = document.getElementById('days');
        const summary = document.getElementById('facility-summary-days');
        if (!form || !days || !summary) return;

        const singular = @json(__('patient.facility_book.day_singular'));
        const plural = @json(__('patient.facility_book.day_plural'));
        const confirmTemplate = @json(__('patient.facility_book.confirm_occupancy'));
        const facilityName = @json($offering->facilityType->name);

        function updateDays() {
          const value = Math.max(1, parseInt(days.value || '1', 10));
          summary.textContent = value + ' ' + (value === 1 ? singular : plural);
        }

        days.addEventListener('input', updateDays);
        updateDays();

        form.addEventListener('submit', function (event) {
          if (form.dataset.confirmed === 'true') return;
          event.preventDefault();
          const value = Math.max(1, parseInt(days.value || '1', 10));
          const message = confirmTemplate.replace(':facility', facilityName).replace(':days', value);
          showConfirmModal(message, function () {
            form.dataset.confirmed = 'true';
            form.requestSubmit ? form.requestSubmit() : form.submit();
          });
        });
      })();
    </script>
  @endpush
@endif

@if (!$isOccupancy && !empty($dates))
  @push('scripts')
    <script src="{{ asset('js/booking-calendar.js') }}?v={{ filemtime(public_path('js/booking-calendar.js')) }}"></script>
    <script>
      (function () {
        const facilityAvailableDates = @json($dates);
        const facilityCapacity = {{ $offering->daily_capacity }};
        const facilityName = @json($offering->facilityType->name);
        const summaryDate = document.getElementById('facility-summary-date');
        const summaryCapacity = document.getElementById('facility-summary-capacity');
        const dateInput = document.getElementById('facility-selected-date');
        const form = document.getElementById('dated-facility-booking-form');
        const button = document.getElementById('facility-confirm-booking');
        const alreadyBookedLabel = @json(__('patient.facility_book.already_booked_day'));
        const spotsTemplate = @json(__('patient.facility_book.spots_left_template'));
        const confirmTemplate = @json(__('patient.facility_book.confirm_book'));

        renderBookingCalendar('facility-calendar', facilityAvailableDates, function (dateStr, info) {
          const label = new Date(dateStr + 'T00:00:00').toLocaleDateString('en-US', {
            weekday: 'short', month: 'short', day: 'numeric', year: 'numeric'
          });

          summaryDate.textContent = label;
          dateInput.value = dateStr;

          if (info.already_booked) {
            summaryCapacity.textContent = alreadyBookedLabel;
            button.disabled = true;
            return;
          }

          summaryCapacity.textContent = spotsTemplate.replace(':left', info.spots_left).replace(':max', facilityCapacity);
          button.disabled = false;
        });

        form.addEventListener('submit', function (event) {
          if (form.dataset.confirmed === 'true') return;
          if (!dateInput.value) {
            event.preventDefault();
            return;
          }
          event.preventDefault();
          const label = summaryDate.textContent;
          const message = confirmTemplate.replace(':facility', facilityName).replace(':date', label);
          showConfirmModal(message, function () {
            form.dataset.confirmed = 'true';
            form.requestSubmit ? form.requestSubmit() : form.submit();
          });
        });
      })();
    </script>
  @endpush
@endif
@endsection
