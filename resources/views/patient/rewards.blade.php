@extends('layouts.app')
@section('title', __('patient.rewards.page_title'))
@section('content')
<div class="patient-rewards-page">
  <nav class="patient-appointments-workspace-nav" aria-label="{{ __('patient.rewards.patient_navigation') }}">
    <a href="{{ route('patient.doctors') }}"><i class="bi bi-calendar2-check" aria-hidden="true"></i><span>{{ __('dashboard.patient.appointments_title') }}</span></a>
    <a href="{{ route('patient.prescriptions') }}"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span>{{ __('dashboard.patient.stat_prescriptions') }}</span></a>
    <a href="{{ route('patient.lab-tests') }}"><i class="bi bi-eyedropper" aria-hidden="true"></i><span>{{ __('dashboard.patient.lab_tests') }}</span></a>
    <a href="{{ route('patient.facilities') }}"><i class="bi bi-hospital" aria-hidden="true"></i><span>{{ __('dashboard.patient.hospital_services') }}</span></a>
    <a href="{{ route('patient.records') }}"><i class="bi bi-clipboard2" aria-hidden="true"></i><span>{{ __('dashboard.patient.records_short') }}</span></a>
  </nav>

  <section class="patient-rewards-hero" aria-labelledby="patient-rewards-title">
    <div class="patient-rewards-breadcrumb">
      <a href="{{ route('patient.dashboard') }}">{{ __('patient.rewards.home') }}</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <span>{{ __('patient.rewards.title') }}</span>
    </div>

    <div class="patient-rewards-hero-grid">
      <div>
        <h1 id="patient-rewards-title">{{ __('patient.rewards.hero_title') }}</h1>
        <p>{{ __('patient.rewards.hero_desc') }}</p>
      </div>

      <div class="patient-rewards-hero-note">
        <span><i class="bi bi-stars" aria-hidden="true"></i></span>
        <div>
          <strong>{{ __('patient.rewards.real_actions_title') }}</strong>
          <small>{{ __('patient.rewards.real_actions_desc') }}</small>
        </div>
      </div>
    </div>
  </section>

  <div class="patient-rewards-layout">
    <main class="patient-rewards-main">
      <section class="patient-rewards-balance-card">
        <div class="patient-rewards-balance-icon"><i class="bi bi-gift" aria-hidden="true"></i></div>
        <div class="patient-rewards-balance-copy">
          <span>{{ __('patient.rewards.balance_label') }}</span>
          <strong>{{ number_format($patient->reward_points_balance) }} <small>{{ __('patient.rewards.points') }}</small></strong>
          <p>{{ __('patient.rewards.balance_desc') }}</p>
        </div>

        <div class="patient-rewards-balance-stats">
          <div>
            <span>{{ __('patient.rewards.earned_total') }}</span>
            <strong>+{{ number_format($earnedTotal) }}</strong>
          </div>
          <div>
            <span>{{ __('patient.rewards.spent_total') }}</span>
            <strong>{{ number_format($spentTotal) }}</strong>
          </div>
        </div>
      </section>

      <section class="patient-rewards-history-card" aria-labelledby="patient-rewards-history-title">
        <header class="patient-rewards-section-heading">
          <div>
            <span class="patient-rewards-section-icon"><i class="bi bi-clock-history" aria-hidden="true"></i></span>
            <div>
              <h2 id="patient-rewards-history-title">{{ __('patient.rewards.activity_title') }}</h2>
              <p>{{ __('patient.rewards.activity_desc') }}</p>
            </div>
          </div>
          <strong>{{ $ledger->count() }}</strong>
        </header>

        @if ($ledger->isEmpty())
          <div class="patient-rewards-empty">
            <span><i class="bi bi-stars" aria-hidden="true"></i></span>
            <div>
              <strong>{{ __('patient.rewards.empty_title') }}</strong>
              <p>{{ __('patient.rewards.empty_desc') }}</p>
            </div>
          </div>
        @else
          <div class="patient-rewards-ledger">
            @foreach ($ledger as $entry)
              @php
                $sourceClass = match ($entry->source_type) {
                  'review' => 'review',
                  'medicine_purchase' => 'medicine',
                  'lab_test_purchase' => 'lab',
                  'redemption' => 'redemption',
                  default => 'adjustment',
                };

                $sourceIcon = match ($entry->source_type) {
                  'review' => 'bi-star',
                  'medicine_purchase' => 'bi-capsule-pill',
                  'lab_test_purchase' => 'bi-eyedropper',
                  'redemption' => 'bi-tag',
                  default => 'bi-sliders',
                };

                $sourceTitle = match ($entry->source_type) {
                  'review' => __('patient.rewards.source_review'),
                  'medicine_purchase' => __('patient.rewards.source_medicine'),
                  'lab_test_purchase' => __('patient.rewards.source_lab'),
                  'redemption' => __('patient.rewards.source_redemption'),
                  default => __('patient.rewards.source_adjustment'),
                };

                $sourceDetail = null;
                if ($entry->source_type === 'medicine_purchase' && $entry->source_id) {
                  $order = $medicineOrders->get($entry->source_id);
                  $sourceDetail = $order
                    ? __('patient.rewards.order_detail', ['id' => $order->order_id, 'pharmacy' => $order->pharmacy?->pharmacy_name ?? '—'])
                    : __('patient.rewards.order_number', ['id' => $entry->source_id]);
                } elseif ($entry->source_type === 'lab_test_purchase' && $entry->source_id) {
                  $booking = $labBookings->get($entry->source_id);
                  $sourceDetail = $booking
                    ? __('patient.rewards.lab_detail', ['test' => $booking->facilityType?->name ?? '—', 'hospital' => $booking->hospital?->hospital_name ?? '—'])
                    : __('patient.rewards.lab_booking_number', ['id' => $entry->source_id]);
                } elseif ($entry->source_type === 'redemption' && $entry->source_id) {
                  $sourceDetail = __('patient.rewards.order_number', ['id' => $entry->source_id]);
                }
              @endphp

              <article class="patient-rewards-ledger-row">
                <span class="patient-rewards-ledger-icon patient-rewards-ledger-icon-{{ $sourceClass }}"><i class="bi {{ $sourceIcon }}" aria-hidden="true"></i></span>
                <div class="patient-rewards-ledger-copy">
                  <strong>{{ $sourceTitle }}</strong>
                  @if ($sourceDetail)
                    <span>{{ $sourceDetail }}</span>
                  @endif
                  <small>{{ optional($entry->created_at)->format('D, M j Y · g:i A') }}</small>
                </div>
                <div class="patient-rewards-ledger-points {{ $entry->points >= 0 ? 'earned' : 'spent' }}">
                  {{ $entry->points > 0 ? '+' : '' }}{{ $entry->points }}
                  <small>{{ __('patient.rewards.points') }}</small>
                </div>
              </article>
            @endforeach
          </div>
        @endif
      </section>
    </main>

    <aside class="patient-rewards-side">
      <section class="patient-rewards-earn-card">
        <header>
          <span><i class="bi bi-trophy" aria-hidden="true"></i></span>
          <div>
            <h2>{{ __('patient.rewards.earn_title') }}</h2>
            <p>{{ __('patient.rewards.earn_desc') }}</p>
          </div>
        </header>

        <a href="{{ route('patient.reviews') }}" class="patient-rewards-earn-row">
          <span class="patient-rewards-earn-icon patient-rewards-earn-icon-review"><i class="bi bi-star" aria-hidden="true"></i></span>
          <span class="patient-rewards-earn-copy">
            <strong>{{ __('patient.rewards.earn_review_title') }}</strong>
            <small>{{ __('patient.rewards.earn_review_desc') }}</small>
          </span>
          <em>+{{ $earningPoints['review'] }}</em>
        </a>

        <a href="{{ route('patient.medicine') }}" class="patient-rewards-earn-row">
          <span class="patient-rewards-earn-icon patient-rewards-earn-icon-medicine"><i class="bi bi-capsule-pill" aria-hidden="true"></i></span>
          <span class="patient-rewards-earn-copy">
            <strong>{{ __('patient.rewards.earn_medicine_title') }}</strong>
            <small>{{ __('patient.rewards.earn_medicine_desc') }}</small>
          </span>
          <em>+{{ $earningPoints['medicine_purchase'] }}</em>
        </a>

        <a href="{{ route('patient.lab-tests') }}" class="patient-rewards-earn-row">
          <span class="patient-rewards-earn-icon patient-rewards-earn-icon-lab"><i class="bi bi-eyedropper" aria-hidden="true"></i></span>
          <span class="patient-rewards-earn-copy">
            <strong>{{ __('patient.rewards.earn_lab_title') }}</strong>
            <small>{{ __('patient.rewards.earn_lab_desc') }}</small>
          </span>
          <em>+{{ $earningPoints['lab_test_purchase'] }}</em>
        </a>
      </section>

      <section class="patient-rewards-redeem-card">
        <div class="patient-rewards-side-heading">
          <span><i class="bi bi-tag" aria-hidden="true"></i></span>
          <div>
            <h2>{{ __('patient.rewards.redeem_title') }}</h2>
            <p>{{ __('patient.rewards.redeem_desc') }}</p>
          </div>
        </div>

        <div class="patient-rewards-redeem-value">
          <span>{{ __('patient.rewards.usable_now') }}</span>
          <strong>{{ $maxRedeemable }} {{ __('patient.rewards.points') }}</strong>
          <small>{{ __('patient.rewards.discount_value', ['percent' => rtrim(rtrim(number_format($redeemPercent, 1), '0'), '.')]) }}</small>
        </div>

        <p class="patient-rewards-rule"><i class="bi bi-info-circle" aria-hidden="true"></i>{{ __('patient.rewards.redeem_rule') }}</p>

        <a href="{{ route('patient.medicine') }}">
          {{ __('patient.rewards.order_medicine') }}
          <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
      </section>

      <section class="patient-rewards-quick-card">
        <a href="{{ route('patient.reviews') }}"><i class="bi bi-star" aria-hidden="true"></i><span>{{ __('patient.rewards.my_reviews') }}</span><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
        <a href="{{ route('patient.orders') }}"><i class="bi bi-bag-check" aria-hidden="true"></i><span>{{ __('patient.rewards.my_orders') }}</span><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
        <a href="{{ route('patient.lab-tests') }}"><i class="bi bi-eyedropper" aria-hidden="true"></i><span>{{ __('patient.rewards.lab_tests') }}</span><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
      </section>
    </aside>
  </div>
</div>

<footer class="patient-dashboard-footer patient-rewards-footer">
  <div class="patient-dashboard-footer-brand">
    <span class="patient-dashboard-footer-dot"></span>
    <div><strong>{{ __('dashboard.patient.platform_name') }}</strong><small>{{ __('home.brand_tagline') }}</small></div>
  </div>
  <p>{{ __('dashboard.patient.footer_tagline') }}</p>
</footer>
@endsection
