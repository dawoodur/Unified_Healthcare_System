@extends('layouts.app')
@section('title', __('patient.checkout.page_title'))
@section('content')
@php
  $itemCount = $lines->sum('quantity');
@endphp

<div class="patient-checkout-page">
  <nav class="patient-appointments-workspace-nav" aria-label="{{ __('patient.checkout.patient_navigation') }}">
    <a href="{{ route('patient.doctors') }}"><i class="bi bi-calendar2-check" aria-hidden="true"></i><span>{{ __('dashboard.patient.appointments_title') }}</span></a>
    <a class="active" href="{{ route('patient.prescriptions') }}"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span>{{ __('dashboard.patient.stat_prescriptions') }}</span></a>
    <a href="{{ route('patient.lab-tests') }}"><i class="bi bi-eyedropper" aria-hidden="true"></i><span>{{ __('dashboard.patient.lab_tests') }}</span></a>
    <a href="{{ route('patient.facilities') }}"><i class="bi bi-hospital" aria-hidden="true"></i><span>{{ __('dashboard.patient.hospital_services') }}</span></a>
    <a href="{{ route('patient.records') }}"><i class="bi bi-clipboard2" aria-hidden="true"></i><span>{{ __('dashboard.patient.records_short') }}</span></a>
  </nav>

  <section class="patient-checkout-hero" aria-labelledby="patient-checkout-title">
    <div class="patient-checkout-breadcrumb">
      <a href="{{ route('patient.dashboard') }}">{{ __('patient.checkout.home') }}</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <a href="{{ route('patient.prescriptions') }}">{{ __('patient.checkout.prescriptions') }}</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <a href="{{ route('patient.cart') }}">{{ __('patient.checkout.cart') }}</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <span>{{ __('patient.checkout.title') }}</span>
    </div>

    <div class="patient-checkout-hero-grid">
      <div>
        <h1 id="patient-checkout-title">{{ __('patient.checkout.hero_title') }}</h1>
        <p>{{ __('patient.checkout.hero_desc') }}</p>
      </div>

      <div class="patient-checkout-hero-note">
        <span><i class="bi bi-cash-coin" aria-hidden="true"></i></span>
        <div>
          <strong>{{ __('patient.checkout.cash_only_title') }}</strong>
          <small>{{ __('patient.checkout.cash_only_desc') }}</small>
        </div>
      </div>
    </div>
  </section>

  <div class="patient-checkout-layout">
    <main class="patient-checkout-main">
      <section class="patient-checkout-form-card">
        <header class="patient-checkout-section-heading">
          <span><i class="bi bi-geo-alt" aria-hidden="true"></i></span>
          <div>
            <h2>{{ __('patient.checkout.delivery_payment') }}</h2>
            <p>{{ __('patient.checkout.delivery_payment_desc') }}</p>
          </div>
        </header>

        @if ($paymentMethods->isEmpty())
          <div class="patient-checkout-inline-error">
            <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
            <span>{{ __('patient.checkout.no_payment_method') }}</span>
          </div>
        @else
          <form
            id="checkout-form"
            method="POST"
            action="{{ route('patient.checkout.store') }}"
            class="patient-checkout-form"
            data-confirm="{{ __('patient.checkout.confirm_order', ['amount' => number_format($subtotal, 2)]) }}"
          >
            @csrf

            <div class="patient-checkout-field">
              <label for="delivery_address">
                <span><i class="bi bi-house-door" aria-hidden="true"></i>{{ __('patient.checkout.delivery_address') }}</span>
              </label>
              <textarea id="delivery_address" name="delivery_address" rows="4" maxlength="255" required>{{ old('delivery_address', $defaultAddress) }}</textarea>
              <small>{{ __('patient.checkout.delivery_address_hint') }}</small>
            </div>

            <fieldset class="patient-checkout-fieldset">
              <legend>
                <i class="bi bi-wallet2" aria-hidden="true"></i>
                {{ __('patient.checkout.payment_method') }}
              </legend>

              <div class="patient-checkout-payment-options">
                @foreach ($paymentMethods as $method)
                  <label class="patient-checkout-payment-option">
                    <input
                      type="radio"
                      name="payment_method_id"
                      value="{{ $method->payment_method_id }}"
                      @checked($loop->first)
                      required
                    >
                    <span class="patient-checkout-payment-icon"><i class="bi bi-cash-stack" aria-hidden="true"></i></span>
                    <span class="patient-checkout-payment-copy">
                      <strong>{{ __('patient.checkout.on_delivery', ['method' => $method->method_name]) }}</strong>
                      <small>{{ __('patient.checkout.cash_payment_desc') }}</small>
                    </span>
                    <i class="bi bi-check-circle-fill patient-checkout-payment-check" aria-hidden="true"></i>
                  </label>
                @endforeach
              </div>

              <p class="patient-checkout-field-note">
                <i class="bi bi-info-circle" aria-hidden="true"></i>
                {{ __('patient.checkout.bkash_note') }}
              </p>
            </fieldset>

            <div class="patient-checkout-field">
              <label for="points_to_redeem">
                <span><i class="bi bi-stars" aria-hidden="true"></i>{{ __('patient.checkout.use_points') }}</span>
                <em>{{ __('patient.checkout.available_points', ['count' => $pointsBalance]) }}</em>
              </label>

              <select id="points_to_redeem" name="points_to_redeem">
                @foreach ($redemptionOptions as $points)
                  <option value="{{ $points }}">
                    @if ($points === 0)
                      {{ __('patient.checkout.no_points') }}
                    @else
                      {{ __('patient.checkout.points_off', ['points' => $points, 'percent' => $points / 10, 'amount' => number_format($subtotal * $points / 1000, 2)]) }}
                    @endif
                  </option>
                @endforeach
              </select>

              <small>{{ __('patient.checkout.points_balance', ['count' => $pointsBalance]) }}</small>
            </div>

            <div class="patient-checkout-form-actions">
              <a href="{{ route('patient.cart') }}" class="patient-checkout-back-action">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                {{ __('patient.checkout.back_to_cart') }}
              </a>

              <button type="submit" class="patient-checkout-place-order">
                <i class="bi bi-bag-check" aria-hidden="true"></i>
                {{ __('patient.checkout.place_order') }}
                <i class="bi bi-arrow-right" aria-hidden="true"></i>
              </button>
            </div>
          </form>
        @endif
      </section>
    </main>

    <aside class="patient-checkout-sidebar">
      <section class="patient-checkout-summary-card">
        <header class="patient-checkout-summary-heading">
          <span><i class="bi bi-receipt" aria-hidden="true"></i></span>
          <div>
            <h2>{{ __('patient.checkout.order_summary') }}</h2>
            <p>{{ trans_choice('patient.checkout.item_count', $itemCount, ['count' => $itemCount]) }}</p>
          </div>
        </header>

        <div class="patient-checkout-pharmacy">
          <small>{{ __('patient.checkout.ordering_from_label') }}</small>
          <strong>{{ $pharmacy->pharmacy_name }}</strong>
          <span><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ $pharmacy->address ?? __('patient.checkout.address_not_listed') }}</span>
        </div>

        <div class="patient-checkout-order-lines">
          @foreach ($lines as $line)
            <div class="patient-checkout-order-line">
              <span class="patient-checkout-order-line-icon"><i class="bi bi-capsule-pill" aria-hidden="true"></i></span>
              <div>
                <strong>{{ $line['medicine']->generic_name }}</strong>
                @if ($line['medicine']->brand_name)
                  <small>{{ $line['medicine']->brand_name }}</small>
                @endif
                <em>{{ __('patient.checkout.quantity_short', ['count' => $line['quantity']]) }}</em>
              </div>
              <b>BDT {{ number_format($line['line_total'], 2) }}</b>
            </div>
          @endforeach
        </div>

        <div class="patient-checkout-summary-row">
          <span>{{ __('patient.checkout.subtotal_label') }}</span>
          <strong>BDT {{ number_format($subtotal, 2) }}</strong>
        </div>

        <div class="patient-checkout-summary-row patient-checkout-discount-row">
          <span>{{ __('patient.checkout.discount_label') }}</span>
          <strong id="discount-amount">BDT 0.00</strong>
        </div>

        <div class="patient-checkout-total-row">
          <span>{{ __('patient.checkout.total_label') }}</span>
          <strong id="total-amount">BDT {{ number_format($subtotal, 2) }}</strong>
        </div>
      </section>

      <section class="patient-checkout-side-card">
        <span class="patient-checkout-side-icon"><i class="bi bi-stars" aria-hidden="true"></i></span>
        <div>
          <h2>{{ __('patient.checkout.reward_title') }}</h2>
          <p>{{ __('patient.checkout.reward_desc') }}</p>
        </div>
      </section>

      <section class="patient-checkout-side-card">
        <span class="patient-checkout-side-icon"><i class="bi bi-chat-square-text" aria-hidden="true"></i></span>
        <div>
          <h2>{{ __('patient.checkout.help_title') }}</h2>
          <p>{{ __('patient.checkout.help_desc') }}</p>
        </div>
        <a href="{{ route('help.index') }}">
          {{ __('patient.checkout.help_action') }}
          <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
      </section>
    </aside>
  </div>
</div>

<footer class="patient-dashboard-footer patient-checkout-footer">
  <div class="patient-dashboard-footer-brand">
    <span class="patient-dashboard-footer-dot"></span>
    <div><strong>{{ __('dashboard.patient.platform_name') }}</strong><small>{{ __('home.brand_tagline') }}</small></div>
  </div>
  <p>{{ __('dashboard.patient.footer_tagline') }}</p>
</footer>

@push('scripts')
<script>
(function () {
  const subtotal = {{ $subtotal }};
  const select = document.getElementById('points_to_redeem');
  const discountEl = document.getElementById('discount-amount');
  const totalEl = document.getElementById('total-amount');
  const form = document.getElementById('checkout-form');

  if (!select || !discountEl || !totalEl || !form) return;

  function refreshCheckoutTotal() {
    const points = parseInt(select.value, 10) || 0;
    const discount = subtotal * points / 1000;
    const total = subtotal - discount;

    discountEl.textContent = 'BDT ' + discount.toFixed(2);
    totalEl.textContent = 'BDT ' + total.toFixed(2);
    form.dataset.confirm = @json(__('patient.checkout.confirm_order_prefix')) + ' BDT ' + total.toFixed(2) + '?';
  }

  select.addEventListener('change', refreshCheckoutTotal);
  refreshCheckoutTotal();
})();
</script>
@endpush
@endsection
