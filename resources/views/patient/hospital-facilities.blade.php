@extends('layouts.app')
@section('title', $hospital->hospital_name)
@section('content')
@php
  $allOfferings = $offeringsByCategory->flatten(1);
  $serviceCount = $allOfferings->count();
  $categoryCount = $offeringsByCategory->count();
  $bedOfferingCount = $allOfferings->filter(fn ($offering) => (bool) $offering->facilityType->is_occupancy)->count();
  $bookableCount = $allOfferings->filter(fn ($offering) => optional($offering->facilityType->category)->category_name !== 'Surgery')->count();
  $surgeryCount = $allOfferings->filter(fn ($offering) => optional($offering->facilityType->category)->category_name === 'Surgery')->count();
@endphp

<div class="patient-hospital-detail-page">
  <nav class="patient-appointments-workspace-nav" aria-label="{{ __('patient.facilities.patient_navigation') }}">
    <a href="{{ route('patient.doctors') }}"><i class="bi bi-calendar2-check" aria-hidden="true"></i><span>{{ __('dashboard.patient.appointments_title') }}</span></a>
    <a href="{{ route('patient.prescriptions') }}"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span>{{ __('dashboard.patient.stat_prescriptions') }}</span></a>
    <a href="{{ route('patient.lab-tests') }}"><i class="bi bi-eyedropper" aria-hidden="true"></i><span>{{ __('dashboard.patient.lab_tests') }}</span></a>
    <a class="active" href="{{ route('patient.facilities') }}"><i class="bi bi-hospital" aria-hidden="true"></i><span>{{ __('dashboard.patient.hospital_services') }}</span></a>
    <a href="{{ route('patient.records') }}"><i class="bi bi-clipboard2" aria-hidden="true"></i><span>{{ __('dashboard.patient.records_short') }}</span></a>
  </nav>

  <section class="patient-hospital-detail-hero" id="hospital-about" aria-labelledby="hospital-detail-title">
    <div class="patient-hospital-detail-breadcrumb">
      <a href="{{ route('patient.dashboard') }}">{{ __('patient.hospital_detail.home') }}</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <a href="{{ route('patient.facilities') }}">{{ __('dashboard.patient.hospital_services') }}</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <span>{{ $hospital->hospital_name }}</span>
    </div>

    <div class="patient-hospital-detail-hero-grid">
      <div class="patient-hospital-detail-copy">
        <span class="patient-hospital-detail-eyebrow">{{ __('patient.hospital_detail.eyebrow') }}</span>
        <h1 id="hospital-detail-title">{{ $hospital->hospital_name }}</h1>
        <p>{{ __('patient.hospital_detail.hero_desc') }}</p>

        <div class="patient-hospital-detail-meta">
          <span><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ $hospital->fullAddress() }}</span>
          <span><i class="bi bi-patch-check" aria-hidden="true"></i>{{ __('patient.facilities.registration_no') }} {{ $hospital->registration_number }}</span>
        </div>

        <div class="patient-hospital-detail-stats">
          <div>
            <i class="bi bi-star-fill" aria-hidden="true"></i>
            @if ($hospital->reviews_count > 0)
              <strong>{{ number_format((float) $hospital->reviews_avg_rating, 1) }}</strong>
              <span>{{ trans_choice('patient.hospital_detail.rating_count', $hospital->reviews_count, ['count' => $hospital->reviews_count]) }}</span>
            @else
              <strong>—</strong>
              <span>{{ __('patient.facilities.no_ratings') }}</span>
            @endif
          </div>
          <div>
            <i class="bi bi-grid" aria-hidden="true"></i>
            <strong>{{ $categoryCount }}</strong>
            <span>{{ trans_choice('patient.hospital_detail.category_count', $categoryCount, ['count' => $categoryCount]) }}</span>
          </div>
          <div>
            <i class="bi bi-clipboard2-pulse" aria-hidden="true"></i>
            <strong>{{ $serviceCount }}</strong>
            <span>{{ trans_choice('patient.hospital_detail.service_count', $serviceCount, ['count' => $serviceCount]) }}</span>
          </div>
        </div>
      </div>

      <div class="patient-hospital-detail-visual" aria-hidden="true">
        <img src="{{ asset('images/patient/HospitalImage.png') }}" alt="">
      </div>
    </div>
  </section>

  <nav class="patient-hospital-detail-subnav" aria-label="{{ __('patient.hospital_detail.section_navigation') }}" role="tablist">
    <button class="active" type="button" role="tab" aria-selected="true" aria-controls="hospital-tab-services" data-hospital-tab="services"><i class="bi bi-stethoscope" aria-hidden="true"></i><span>{{ __('patient.hospital_detail.services') }}</span></button>
    <button type="button" role="tab" aria-selected="false" aria-controls="hospital-tab-about" data-hospital-tab="about"><i class="bi bi-info-circle" aria-hidden="true"></i><span>{{ __('patient.hospital_detail.about') }}</span></button>
    <button type="button" role="tab" aria-selected="false" aria-controls="hospital-tab-facilities" data-hospital-tab="facilities"><i class="bi bi-briefcase" aria-hidden="true"></i><span>{{ __('patient.hospital_detail.facilities') }}</span></button>
    <button type="button" role="tab" aria-selected="false" aria-controls="hospital-tab-doctors" data-hospital-tab="doctors"><i class="bi bi-person" aria-hidden="true"></i><span>{{ __('patient.hospital_detail.doctors') }}</span></button>
    <button type="button" role="tab" aria-selected="false" aria-controls="hospital-tab-reviews" data-hospital-tab="reviews"><i class="bi bi-star" aria-hidden="true"></i><span>{{ __('patient.hospital_detail.reviews') }}</span></button>
    <button type="button" role="tab" aria-selected="false" aria-controls="hospital-tab-location" data-hospital-tab="location"><i class="bi bi-geo-alt" aria-hidden="true"></i><span>{{ __('patient.hospital_detail.location') }}</span></button>
  </nav>

  <div id="hospital-tab-services" class="patient-hospital-detail-tab-panel active" data-hospital-tab-panel="services" role="tabpanel">
  <div class="patient-hospital-detail-layout">
    <main class="patient-hospital-detail-main" id="hospital-services">
      <section class="patient-hospital-detail-card patient-hospital-detail-services-card">
        <header class="patient-hospital-detail-section-heading">
          <div>
            <h2>{{ __('patient.hospital_detail.available_services') }}</h2>
            <p>{{ __('patient.hospital_detail.available_services_desc') }}</p>
          </div>
          <a href="{{ route('patient.facilities') }}">{{ __('patient.hospital_detail.compare_prices') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </header>

        @if ($offeringsByCategory->isEmpty())
          <div class="patient-hospital-detail-empty">
            <span><i class="bi bi-hospital" aria-hidden="true"></i></span>
            <strong>{{ __('patient.hospital_detail.no_services_title') }}</strong>
            <p>{{ __('patient.facilities.not_priced_yet') }}</p>
          </div>
        @else
          <div class="patient-hospital-detail-category-links" aria-label="{{ __('patient.hospital_detail.service_categories') }}">
            <a class="active" href="#hospital-services">{{ __('patient.hospital_detail.all_services') }}</a>
            @foreach ($offeringsByCategory as $categoryName => $offerings)
              <a href="#hospital-category-{{ \Illuminate\Support\Str::slug($categoryName) }}">{{ $categoryName }}</a>
            @endforeach
          </div>

          <div class="patient-hospital-detail-category-list">
            @foreach ($offeringsByCategory as $categoryName => $offerings)
              @php
                $categorySlug = \Illuminate\Support\Str::slug($categoryName);
                $categoryIcon = match (strtolower($categoryName)) {
                  'surgery' => 'bi-scissors',
                  'diagnostic test', 'diagnostics' => 'bi-eyedropper',
                  'imaging' => 'bi-broadcast-pin',
                  'critical care' => 'bi-heart-pulse',
                  'consultation room' => 'bi-stethoscope',
                  default => 'bi-clipboard2-pulse',
                };
              @endphp
              <section class="patient-hospital-detail-category" id="hospital-category-{{ $categorySlug }}">
                <div class="patient-hospital-detail-category-heading">
                  <span><i class="bi {{ $categoryIcon }}" aria-hidden="true"></i></span>
                  <div>
                    <h3>{{ $categoryName }}</h3>
                    <p>{{ trans_choice('patient.hospital_detail.category_service_count', $offerings->count(), ['count' => $offerings->count()]) }}</p>
                  </div>
                </div>

                <div class="patient-hospital-detail-service-list">
                  @foreach ($offerings as $offering)
                    <article class="patient-hospital-detail-service-row">
                      <div class="patient-hospital-detail-service-icon"><i class="bi {{ $categoryIcon }}" aria-hidden="true"></i></div>
                      <div class="patient-hospital-detail-service-copy">
                        <strong>{{ $offering->facilityType->name }}</strong>
                        <span>{{ $offering->facilityType->unit_label }}</span>
                        <small>
                          <i class="bi bi-people" aria-hidden="true"></i>
                          {{ $offering->daily_capacity }}{{ $offering->facilityType->is_occupancy ? __('patient.facilities.beds_suffix') : __('patient.facilities.per_day') }}
                        </small>
                      </div>
                      <div class="patient-hospital-detail-service-price">
                        <span>{{ __('patient.facilities.price') }}</span>
                        <strong>BDT {{ number_format($offering->price, 2) }}</strong>
                      </div>
                      <div class="patient-hospital-detail-service-action">
                        @if ($categoryName === 'Surgery')
                          <a href="{{ route('patient.operations.create', $offering) }}">{{ __('patient.hospital_detail.request_surgery') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                        @else
                          <a href="{{ route('patient.facilities.book', $offering) }}">{{ __('patient.hospital_detail.book_service') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                        @endif
                      </div>
                    </article>
                  @endforeach
                </div>
              </section>
            @endforeach
          </div>
        @endif
      </section>
    </main>

    <aside class="patient-hospital-detail-sidebar">
      <section class="patient-hospital-detail-card" id="hospital-location">
        <div class="patient-hospital-detail-side-heading">
          <span><i class="bi bi-info-circle" aria-hidden="true"></i></span>
          <h2>{{ __('patient.hospital_detail.quick_information') }}</h2>
        </div>
        <dl class="patient-hospital-detail-info-list">
          <div><dt><i class="bi bi-patch-check" aria-hidden="true"></i>{{ __('patient.hospital_detail.registration') }}</dt><dd>{{ $hospital->registration_number }}</dd></div>
          <div><dt><i class="bi bi-geo" aria-hidden="true"></i>{{ __('patient.hospital_detail.city') }}</dt><dd>{{ $hospital->city ?: '—' }}</dd></div>
          <div><dt><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ __('patient.hospital_detail.address') }}</dt><dd>{{ $hospital->fullAddress() }}</dd></div>
          <div><dt><i class="bi bi-clipboard2-pulse" aria-hidden="true"></i>{{ __('patient.hospital_detail.priced_services') }}</dt><dd>{{ $serviceCount }}</dd></div>
        </dl>
      </section>

      <section class="patient-hospital-detail-card" id="hospital-facility-overview">
        <div class="patient-hospital-detail-side-heading">
          <span><i class="bi bi-grid" aria-hidden="true"></i></span>
          <h2>{{ __('patient.hospital_detail.facility_overview') }}</h2>
        </div>
        <div class="patient-hospital-detail-overview-grid">
          <div><strong>{{ $categoryCount }}</strong><span>{{ __('patient.hospital_detail.categories') }}</span></div>
          <div><strong>{{ $bookableCount }}</strong><span>{{ __('patient.hospital_detail.direct_bookings') }}</span></div>
          <div><strong>{{ $surgeryCount }}</strong><span>{{ __('patient.hospital_detail.surgery_requests') }}</span></div>
          <div><strong>{{ $bedOfferingCount }}</strong><span>{{ __('patient.hospital_detail.bed_services') }}</span></div>
        </div>
      </section>

      <section class="patient-hospital-detail-card" id="hospital-reviews">
        <div class="patient-hospital-detail-side-heading">
          <span><i class="bi bi-star" aria-hidden="true"></i></span>
          <h2>{{ __('patient.hospital_detail.patient_ratings') }}</h2>
        </div>
        @if ($hospital->reviews_count > 0)
          <div class="patient-hospital-detail-rating-summary">
            <strong>{{ number_format((float) $hospital->reviews_avg_rating, 1) }}</strong>
            <div>
              @include('partials.star-rating', ['rating' => $hospital->reviews_avg_rating, 'label' => number_format((float) $hospital->reviews_avg_rating, 1)])
              <span>{{ trans_choice('patient.hospital_detail.rating_count', $hospital->reviews_count, ['count' => $hospital->reviews_count]) }}</span>
            </div>
          </div>
        @else
          <p class="patient-hospital-detail-muted">{{ __('patient.facilities.no_ratings') }}</p>
        @endif
      </section>

      <section class="patient-hospital-detail-card patient-hospital-detail-guidance">
        <div class="patient-hospital-detail-side-heading">
          <span><i class="bi bi-lightbulb" aria-hidden="true"></i></span>
          <h2>{{ __('patient.hospital_detail.booking_guidance') }}</h2>
        </div>
        <p>{{ __('patient.hospital_detail.booking_guidance_desc') }}</p>
        <a href="{{ route('help.index') }}">{{ __('patient.hospital_detail.get_help') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
      </section>
    </aside>
  </div>
  </div>

  <section id="hospital-tab-about" class="patient-hospital-detail-tab-panel" data-hospital-tab-panel="about" role="tabpanel" hidden>
    <div class="patient-hospital-detail-tab-card">
      <div class="patient-hospital-detail-side-heading">
        <span><i class="bi bi-info-circle" aria-hidden="true"></i></span>
        <h2>{{ __('patient.hospital_detail.about') }}</h2>
      </div>
      <p class="patient-hospital-detail-tab-lead">{{ __('patient.hospital_detail.hero_desc') }}</p>
      <dl class="patient-hospital-detail-info-list patient-hospital-detail-tab-info">
        <div><dt><i class="bi bi-hospital" aria-hidden="true"></i>{{ __('patient.hospital_detail.about') }}</dt><dd>{{ $hospital->hospital_name }}</dd></div>
        <div><dt><i class="bi bi-patch-check" aria-hidden="true"></i>{{ __('patient.hospital_detail.registration') }}</dt><dd>{{ $hospital->registration_number }}</dd></div>
        <div><dt><i class="bi bi-geo" aria-hidden="true"></i>{{ __('patient.hospital_detail.city') }}</dt><dd>{{ $hospital->city ?: '—' }}</dd></div>
        <div><dt><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ __('patient.hospital_detail.address') }}</dt><dd>{{ $hospital->fullAddress() }}</dd></div>
      </dl>
    </div>
  </section>

  <section id="hospital-tab-facilities" class="patient-hospital-detail-tab-panel" data-hospital-tab-panel="facilities" role="tabpanel" hidden>
    <div class="patient-hospital-detail-tab-card">
      <div class="patient-hospital-detail-side-heading">
        <span><i class="bi bi-grid" aria-hidden="true"></i></span>
        <h2>{{ __('patient.hospital_detail.facility_overview') }}</h2>
      </div>
      <div class="patient-hospital-detail-overview-grid patient-hospital-detail-tab-overview">
        <div><strong>{{ $categoryCount }}</strong><span>{{ __('patient.hospital_detail.categories') }}</span></div>
        <div><strong>{{ $bookableCount }}</strong><span>{{ __('patient.hospital_detail.direct_bookings') }}</span></div>
        <div><strong>{{ $surgeryCount }}</strong><span>{{ __('patient.hospital_detail.surgery_requests') }}</span></div>
        <div><strong>{{ $bedOfferingCount }}</strong><span>{{ __('patient.hospital_detail.bed_services') }}</span></div>
      </div>
      @if ($offeringsByCategory->isNotEmpty())
        <div class="patient-hospital-detail-tab-category-grid">
          @foreach ($offeringsByCategory as $categoryName => $offerings)
            <div>
              <strong>{{ $categoryName }}</strong>
              <span>{{ trans_choice('patient.hospital_detail.category_service_count', $offerings->count(), ['count' => $offerings->count()]) }}</span>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </section>

  <section id="hospital-tab-doctors" class="patient-hospital-detail-tab-panel" data-hospital-tab-panel="doctors" role="tabpanel" hidden>
    <div class="patient-hospital-detail-tab-card">
      <div class="patient-hospital-detail-side-heading">
        <span><i class="bi bi-person" aria-hidden="true"></i></span>
        <h2>{{ __('patient.hospital_detail.doctors') }}</h2>
      </div>
      @if ($activeDoctors->isEmpty())
        <p class="patient-hospital-detail-muted">{{ __('patient.search.no_results') }}</p>
      @else
        <div class="patient-hospital-detail-doctor-grid">
          @foreach ($activeDoctors as $doctor)
            <article class="patient-hospital-detail-doctor-card">
              <div class="patient-hospital-detail-doctor-avatar"><i class="bi bi-person" aria-hidden="true"></i></div>
              <div>
                <strong>Dr. {{ $doctor->full_name }}</strong>
                <p>{{ $doctor->specialties->pluck('specialty_name')->join(', ') ?: __('patient.search.general_care') }}</p>
                <small>{{ __('patient.book.consultation_fee', ['amount' => number_format($doctor->consultation_fee, 2)]) }}</small>
              </div>
              <a href="{{ route('patient.doctors.show', $doctor) }}">{{ __('patient.search.view_profile') }}</a>
            </article>
          @endforeach
        </div>
      @endif
    </div>
  </section>

  <section id="hospital-tab-reviews" class="patient-hospital-detail-tab-panel" data-hospital-tab-panel="reviews" role="tabpanel" hidden>
    <div class="patient-hospital-detail-tab-card">
      <div class="patient-hospital-detail-side-heading">
        <span><i class="bi bi-star" aria-hidden="true"></i></span>
        <h2>{{ __('patient.hospital_detail.patient_ratings') }}</h2>
      </div>
      @if ($hospitalReviews->isEmpty())
        <p class="patient-hospital-detail-muted">{{ __('patient.facilities.no_ratings') }}</p>
      @else
        <div class="patient-hospital-detail-review-list">
          @foreach ($hospitalReviews as $review)
            <article>
              <div>
                @include('partials.star-rating', ['rating' => $review->rating, 'label' => number_format((float) $review->rating, 1)])
                <span>{{ number_format((float) $review->rating, 1) }}</span>
              </div>
              <p>{{ $review->comment ?: '—' }}</p>
              <small>{{ optional($review->created_at)->format('M d, Y') }}</small>
            </article>
          @endforeach
        </div>
      @endif
    </div>
  </section>

  <section id="hospital-tab-location" class="patient-hospital-detail-tab-panel" data-hospital-tab-panel="location" role="tabpanel" hidden>
    <div class="patient-hospital-detail-tab-card">
      <div class="patient-hospital-detail-side-heading">
        <span><i class="bi bi-geo-alt" aria-hidden="true"></i></span>
        <h2>{{ __('patient.hospital_detail.location') }}</h2>
      </div>
      <dl class="patient-hospital-detail-info-list patient-hospital-detail-tab-info">
        <div><dt><i class="bi bi-geo" aria-hidden="true"></i>{{ __('patient.hospital_detail.city') }}</dt><dd>{{ $hospital->city ?: '—' }}</dd></div>
        <div><dt><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ __('patient.hospital_detail.address') }}</dt><dd>{{ $hospital->fullAddress() }}</dd></div>
      </dl>
    </div>
  </section>
</div>

<footer class="patient-dashboard-footer patient-facilities-footer">
  <div class="patient-dashboard-footer-brand">
    <span class="patient-dashboard-footer-dot"></span>
    <div><strong>{{ __('dashboard.patient.platform_name') }}</strong><small>{{ __('home.brand_tagline') }}</small></div>
  </div>
  <p>{{ __('dashboard.patient.footer_tagline') }}</p>
</footer>

<script>
(function () {
  const buttons = Array.from(document.querySelectorAll('[data-hospital-tab]'));
  const panels = Array.from(document.querySelectorAll('[data-hospital-tab-panel]'));
  if (!buttons.length || !panels.length) return;

  function activateTab(name, updateHash) {
    const target = panels.find((panel) => panel.dataset.hospitalTabPanel === name);
    if (!target) return;

    buttons.forEach((button) => {
      const active = button.dataset.hospitalTab === name;
      button.classList.toggle('active', active);
      button.setAttribute('aria-selected', active ? 'true' : 'false');
    });

    panels.forEach((panel) => {
      const active = panel === target;
      panel.classList.toggle('active', active);
      panel.hidden = !active;
    });

    if (updateHash) {
      history.replaceState(null, '', '#hospital-' + name);
    }
  }

  buttons.forEach((button) => {
    button.addEventListener('click', function () {
      activateTab(this.dataset.hospitalTab, true);
    });
  });

  const requested = window.location.hash.replace('#hospital-', '');
  const valid = buttons.some((button) => button.dataset.hospitalTab === requested);
  activateTab(valid ? requested : 'services', false);
})();
</script>
@endsection
