@extends('layouts.app')
@section('title', __('patient.reviews.page_title'))
@section('content')
@php
  $eligibleTotal = $doctors->count() + $hospitals->count() + $pharmacies->count() + $deliveryAgents->count();

  $reviewedDoctors = $doctors->filter(fn ($doctor) => $existingByType['doctor']->has($doctor->doctor_id))->count();
  $reviewedHospitals = $hospitals->filter(fn ($hospital) => $existingByType['hospital']->has($hospital->hospital_id))->count();
  $reviewedPharmacies = $pharmacies->filter(fn ($pharmacy) => $existingByType['pharmacy']->has($pharmacy->pharmacy_id))->count();
  $reviewedDelivery = $deliveryAgents->filter(fn ($agent) => $existingByType['delivery']->has($agent->delivery_agent_id))->count();

  $reviewedTotal = $reviewedDoctors + $reviewedHospitals + $reviewedPharmacies + $reviewedDelivery;
  $pendingTotal = max(0, $eligibleTotal - $reviewedTotal);

  $categories = [
    [
      'key' => 'doctor',
      'title' => __('patient.reviews.doctors_title'),
      'desc' => __('patient.reviews.doctors_desc'),
      'icon' => 'bi-person-badge',
      'items' => $doctors,
      'reviewed' => $reviewedDoctors,
    ],
    [
      'key' => 'hospital',
      'title' => __('patient.reviews.hospitals_title'),
      'desc' => __('patient.reviews.hospitals_desc'),
      'icon' => 'bi-hospital',
      'items' => $hospitals,
      'reviewed' => $reviewedHospitals,
    ],
    [
      'key' => 'pharmacy',
      'title' => __('patient.reviews.pharmacies_title'),
      'desc' => __('patient.reviews.pharmacies_desc'),
      'icon' => 'bi-shop',
      'items' => $pharmacies,
      'reviewed' => $reviewedPharmacies,
    ],
    [
      'key' => 'delivery',
      'title' => __('patient.reviews.delivery_title'),
      'desc' => __('patient.reviews.delivery_desc'),
      'icon' => 'bi-truck',
      'items' => $deliveryAgents,
      'reviewed' => $reviewedDelivery,
    ],
  ];
@endphp

<div class="patient-reviews-page">
  <nav class="patient-appointments-workspace-nav" aria-label="{{ __('patient.reviews.patient_navigation') }}">
    <a href="{{ route('patient.doctors') }}"><i class="bi bi-calendar2-check" aria-hidden="true"></i><span>{{ __('dashboard.patient.appointments_title') }}</span></a>
    <a href="{{ route('patient.prescriptions') }}"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span>{{ __('dashboard.patient.stat_prescriptions') }}</span></a>
    <a href="{{ route('patient.lab-tests') }}"><i class="bi bi-eyedropper" aria-hidden="true"></i><span>{{ __('dashboard.patient.lab_tests') }}</span></a>
    <a href="{{ route('patient.facilities') }}"><i class="bi bi-hospital" aria-hidden="true"></i><span>{{ __('dashboard.patient.hospital_services') }}</span></a>
    <a href="{{ route('patient.records') }}"><i class="bi bi-clipboard2" aria-hidden="true"></i><span>{{ __('dashboard.patient.records_short') }}</span></a>
  </nav>

  <section class="patient-reviews-hero" aria-labelledby="patient-reviews-title">
    <div class="patient-reviews-breadcrumb">
      <a href="{{ route('patient.dashboard') }}">{{ __('patient.reviews.home') }}</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <span>{{ __('patient.reviews.title') }}</span>
    </div>

    <div class="patient-reviews-hero-grid">
      <div>
        <h1 id="patient-reviews-title">{{ __('patient.reviews.hero_title') }}</h1>
        <p>{{ __('patient.reviews.hero_desc') }}</p>
      </div>

      <div class="patient-reviews-hero-note">
        <span><i class="bi bi-incognito" aria-hidden="true"></i></span>
        <div>
          <strong>{{ __('patient.reviews.private_title') }}</strong>
          <small>{{ __('patient.reviews.private_desc') }}</small>
        </div>
      </div>
    </div>
  </section>

  <div class="patient-reviews-layout">
    <main class="patient-reviews-main">
      <section class="patient-reviews-summary" aria-label="{{ __('patient.reviews.summary_label') }}">
        <div>
          <span class="patient-reviews-summary-icon"><i class="bi bi-list-check" aria-hidden="true"></i></span>
          <div>
            <small>{{ __('patient.reviews.eligible_total') }}</small>
            <strong>{{ $eligibleTotal }}</strong>
          </div>
        </div>
        <div>
          <span class="patient-reviews-summary-icon"><i class="bi bi-star" aria-hidden="true"></i></span>
          <div>
            <small>{{ __('patient.reviews.awaiting_review') }}</small>
            <strong>{{ $pendingTotal }}</strong>
          </div>
        </div>
        <div>
          <span class="patient-reviews-summary-icon"><i class="bi bi-check2-circle" aria-hidden="true"></i></span>
          <div>
            <small>{{ __('patient.reviews.submitted') }}</small>
            <strong>{{ $reviewedTotal }}</strong>
          </div>
        </div>
        <div>
          <span class="patient-reviews-summary-icon"><i class="bi bi-gift" aria-hidden="true"></i></span>
          <div>
            <small>{{ __('patient.reviews.reward_balance') }}</small>
            <strong>{{ number_format($pointsBalance) }}</strong>
          </div>
        </div>
      </section>

      <nav class="patient-reviews-category-nav" aria-label="{{ __('patient.reviews.category_navigation') }}">
        @foreach ($categories as $category)
          <a href="#reviews-{{ $category['key'] }}">
            <i class="bi {{ $category['icon'] }}" aria-hidden="true"></i>
            <span>{{ $category['title'] }}</span>
            <em>{{ $category['items']->count() }}</em>
          </a>
        @endforeach
      </nav>

      @foreach ($categories as $category)
        @php
          $emptyKey = match ($category['key']) {
            'doctor' => 'empty_doctors',
            'hospital' => 'empty_hospitals',
            'pharmacy' => 'empty_pharmacies',
            default => 'empty_delivery',
          };
        @endphp

        <section class="patient-reviews-section" id="reviews-{{ $category['key'] }}" aria-labelledby="reviews-{{ $category['key'] }}-title">
          <header class="patient-reviews-section-heading">
            <div>
              <span class="patient-reviews-section-icon"><i class="bi {{ $category['icon'] }}" aria-hidden="true"></i></span>
              <div>
                <h2 id="reviews-{{ $category['key'] }}-title">{{ $category['title'] }}</h2>
                <p>{{ $category['desc'] }}</p>
              </div>
            </div>

            <div class="patient-reviews-section-counts">
              <span>{{ __('patient.reviews.reviewed_count', ['reviewed' => $category['reviewed'], 'total' => $category['items']->count()]) }}</span>
            </div>
          </header>

          @if ($category['items']->isEmpty())
            <div class="patient-reviews-empty">
              <span><i class="bi {{ $category['icon'] }}" aria-hidden="true"></i></span>
              <div>
                <strong>{{ __('patient.reviews.nothing_eligible') }}</strong>
                <p>{{ __('patient.reviews.' . $emptyKey) }}</p>
              </div>
            </div>
          @else
            <div class="patient-reviews-list">
              @foreach ($category['items'] as $item)
                @php
                  $itemId = match ($category['key']) {
                    'doctor' => $item->doctor_id,
                    'hospital' => $item->hospital_id,
                    'pharmacy' => $item->pharmacy_id,
                    default => $item->delivery_agent_id,
                  };

                  $itemName = match ($category['key']) {
                    'doctor' => 'Dr. ' . $item->full_name,
                    'hospital' => $item->hospital_name,
                    'pharmacy' => $item->pharmacy_name,
                    default => $item->full_name,
                  };

                  $review = $existingByType[$category['key']]->get($itemId);
                @endphp

                <article class="patient-review-row {{ $review ? 'patient-review-row-reviewed' : '' }}">
                  <span class="patient-review-provider-icon">
                    <i class="bi {{ $category['icon'] }}" aria-hidden="true"></i>
                  </span>

                  <div class="patient-review-provider">
                    <span>{{ __('patient.reviews.type_' . $category['key']) }}</span>
                    <strong>{{ $itemName }}</strong>
                    @if ($review && $review->comment)
                      <p>“{{ $review->comment }}”</p>
                    @elseif (!$review)
                      <p>{{ __('patient.reviews.eligible_to_review') }}</p>
                    @endif
                  </div>

                  <div class="patient-review-rating">
                    <span>{{ __('patient.reviews.your_rating') }}</span>
                    @if ($review)
                      <div class="patient-review-stars" aria-label="{{ __('patient.reviews.rating_out_of_five', ['rating' => $review->rating]) }}">
                        @for ($star = 1; $star <= 5; $star++)
                          <i class="bi {{ $star <= $review->rating ? 'bi-star-fill' : 'bi-star' }}" aria-hidden="true"></i>
                        @endfor
                        <strong>{{ $review->rating }}/5</strong>
                      </div>
                    @else
                      <em>{{ __('patient.reviews.not_rated') }}</em>
                    @endif
                  </div>

                  <div class="patient-review-status">
                    @if ($review)
                      <span class="patient-review-status-done"><i class="bi bi-check-circle-fill" aria-hidden="true"></i>{{ __('patient.reviews.reviewed') }}</span>
                    @else
                      <span class="patient-review-status-pending"><i class="bi bi-clock-fill" aria-hidden="true"></i>{{ __('patient.reviews.pending') }}</span>
                    @endif
                  </div>

                  <a
                    class="patient-review-action"
                    href="{{ route('patient.reviews.rate', ['type' => $category['key'], 'id' => $itemId]) }}"
                  >
                    {{ $review ? __('patient.reviews.edit_rating') : __('patient.reviews.rate_now') }}
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                  </a>
                </article>
              @endforeach
            </div>
          @endif
        </section>
      @endforeach
    </main>

    <aside class="patient-reviews-sidebar">
      <section class="patient-reviews-reward-card">
        <div class="patient-reviews-side-heading">
          <span><i class="bi bi-gift" aria-hidden="true"></i></span>
          <div>
            <h2>{{ __('patient.reviews.reward_card_title') }}</h2>
            <p>{{ __('patient.reviews.reward_card_desc') }}</p>
          </div>
        </div>

        <div class="patient-reviews-balance">
          <span>{{ __('patient.reviews.current_balance') }}</span>
          <strong>{{ number_format($pointsBalance) }} <small>{{ __('patient.reviews.points') }}</small></strong>
        </div>

        <a href="{{ route('patient.rewards') }}">
          {{ __('patient.reviews.view_rewards') }}
          <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
      </section>

      <section class="patient-reviews-side-card">
        <span class="patient-reviews-side-icon"><i class="bi bi-shield-check" aria-hidden="true"></i></span>
        <div>
          <h2>{{ __('patient.reviews.privacy_title') }}</h2>
          <p>{{ __('patient.reviews.privacy_desc') }}</p>
        </div>
      </section>

      <section class="patient-reviews-side-card">
        <span class="patient-reviews-side-icon"><i class="bi bi-info-circle" aria-hidden="true"></i></span>
        <div>
          <h2>{{ __('patient.reviews.eligibility_title') }}</h2>
          <p>{{ __('patient.reviews.eligibility_desc') }}</p>
        </div>
      </section>

      <section class="patient-reviews-side-card">
        <span class="patient-reviews-side-icon"><i class="bi bi-question-circle" aria-hidden="true"></i></span>
        <div>
          <h2>{{ __('patient.reviews.help_title') }}</h2>
          <p>{{ __('patient.reviews.help_desc') }}</p>
        </div>
        <a href="{{ route('help.index') }}">
          {{ __('patient.reviews.help_action') }}
          <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
      </section>
    </aside>
  </div>
</div>

<footer class="patient-dashboard-footer patient-reviews-footer">
  <div class="patient-dashboard-footer-brand">
    <span class="patient-dashboard-footer-dot"></span>
    <div><strong>{{ __('dashboard.patient.platform_name') }}</strong><small>{{ __('home.brand_tagline') }}</small></div>
  </div>
  <p>{{ __('dashboard.patient.footer_tagline') }}</p>
</footer>
@endsection
