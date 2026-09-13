@extends('layouts.app')
@section('title', __('patient.cart.page_title'))
@section('content')
@php
  $itemCount = $lines->sum('quantity');
@endphp

<div class="patient-cart-page">
  <nav class="patient-appointments-workspace-nav" aria-label="{{ __('patient.cart.patient_navigation') }}">
    <a href="{{ route('patient.doctors') }}"><i class="bi bi-calendar2-check" aria-hidden="true"></i><span>{{ __('dashboard.patient.appointments_title') }}</span></a>
    <a class="active" href="{{ route('patient.prescriptions') }}"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span>{{ __('dashboard.patient.stat_prescriptions') }}</span></a>
    <a href="{{ route('patient.lab-tests') }}"><i class="bi bi-eyedropper" aria-hidden="true"></i><span>{{ __('dashboard.patient.lab_tests') }}</span></a>
    <a href="{{ route('patient.facilities') }}"><i class="bi bi-hospital" aria-hidden="true"></i><span>{{ __('dashboard.patient.hospital_services') }}</span></a>
    <a href="{{ route('patient.records') }}"><i class="bi bi-clipboard2" aria-hidden="true"></i><span>{{ __('dashboard.patient.records_short') }}</span></a>
  </nav>

  <section class="patient-cart-hero" aria-labelledby="patient-cart-title">
    <div class="patient-cart-breadcrumb">
      <a href="{{ route('patient.dashboard') }}">{{ __('patient.cart.home') }}</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <a href="{{ route('patient.prescriptions') }}">{{ __('patient.cart.prescriptions') }}</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <span>{{ __('patient.cart.title') }}</span>
    </div>

    <div class="patient-cart-hero-grid">
      <div>
        <h1 id="patient-cart-title">{{ __('patient.cart.hero_title') }}</h1>
        <p>{{ __('patient.cart.hero_desc') }}</p>
      </div>

      <div class="patient-cart-hero-note" aria-label="{{ __('patient.cart.one_pharmacy_title') }}">
        <span><i class="bi bi-bag-check" aria-hidden="true"></i></span>
        <div>
          <strong>{{ __('patient.cart.one_pharmacy_title') }}</strong>
          <small>{{ __('patient.cart.one_pharmacy_desc') }}</small>
        </div>
      </div>
    </div>
  </section>

  @if ($lines->isEmpty())
    <section class="patient-cart-empty">
      <span class="patient-cart-empty-icon"><i class="bi bi-cart3" aria-hidden="true"></i></span>
      <h2>{{ __('patient.cart.empty_title') }}</h2>
      <p>{{ __('patient.cart.empty_desc') }}</p>
      <div class="patient-cart-empty-actions">
        <a class="patient-cart-primary-action" href="{{ route('patient.medicine') }}">
          {{ __('patient.cart.browse_medicine') }}
          <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
        <a class="patient-cart-secondary-action" href="{{ route('patient.prescriptions') }}">
          {{ __('patient.cart.my_prescriptions') }}
        </a>
      </div>
    </section>
  @else
    <div class="patient-cart-layout">
      <main class="patient-cart-main">
        <section class="patient-cart-list-card">
          <header class="patient-cart-list-heading">
            <div>
              <span class="patient-cart-list-icon"><i class="bi bi-cart3" aria-hidden="true"></i></span>
              <div>
                <h2>{{ __('patient.cart.cart_items_title') }}</h2>
                <p>{{ trans_choice('patient.cart.item_count', $itemCount, ['count' => $itemCount]) }}</p>
              </div>
            </div>

            <form method="POST" action="{{ route('patient.cart.clear') }}" data-confirm="{{ __('patient.cart.clear_confirm') }}">
              @csrf
              <button type="submit" class="patient-cart-clear-btn">
                <i class="bi bi-trash3" aria-hidden="true"></i>
                {{ __('patient.cart.clear_cart') }}
              </button>
            </form>
          </header>

          <div class="patient-cart-pharmacy-strip">
            <span><i class="bi bi-shop" aria-hidden="true"></i></span>
            <div>
              <small>{{ __('patient.cart.ordering_from') }}</small>
              <strong>{{ $pharmacy->pharmacy_name }}</strong>
              <p><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ $pharmacy->address ?? __('patient.cart.address_not_listed') }}</p>
            </div>
          </div>

          <div class="patient-cart-items">
            @foreach ($lines as $line)
              <article class="patient-cart-item">
                <span class="patient-cart-medicine-icon" aria-hidden="true">
                  <i class="bi bi-capsule-pill"></i>
                </span>

                <div class="patient-cart-item-copy">
                  <strong>{{ $line['medicine']->generic_name }}</strong>
                  @if ($line['medicine']->brand_name)
                    <span>{{ $line['medicine']->brand_name }}</span>
                  @endif
                  <small>
                    @if ($line['medicine']->strength)
                      {{ $line['medicine']->strength }}
                    @endif
                    @if ($line['medicine']->form)
                      @if ($line['medicine']->strength) · @endif
                      {{ $line['medicine']->form }}
                    @endif
                    <em>#m{{ $line['medicine']->medicine_master_id }}</em>
                  </small>
                </div>

                <div class="patient-cart-item-quantity">
                  <span>{{ __('patient.cart.quantity') }}</span>
                  <div class="patient-cart-quantity-control">
                    <form method="POST" action="{{ route('patient.cart.remove') }}">
                      @csrf
                      <input type="hidden" name="medicine_master_id" value="{{ $line['medicine']->medicine_master_id }}">
                      <button type="submit" aria-label="{{ __('patient.cart.quantity') }} -">
                        <i class="bi bi-dash-lg" aria-hidden="true"></i>
                      </button>
                    </form>

                    <strong>{{ $line['quantity'] }}</strong>

                    <form method="POST" action="{{ route('patient.cart.add') }}">
                      @csrf
                      <input type="hidden" name="pharmacy_id" value="{{ $pharmacy->pharmacy_id }}">
                      <input type="hidden" name="medicine_master_id" value="{{ $line['medicine']->medicine_master_id }}">
                      <input type="hidden" name="quantity" value="1">
                      <button type="submit" aria-label="{{ __('patient.cart.quantity') }} +">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                      </button>
                    </form>
                  </div>
                </div>

                <div class="patient-cart-item-metric">
                  <span>{{ __('patient.cart.unit_price') }}</span>
                  @if ($line['price'] === null)
                    <strong class="patient-cart-unavailable">{{ __('patient.cart.out_of_stock') }}</strong>
                  @else
                    <strong>BDT {{ number_format($line['price'], 2) }}</strong>
                  @endif
                </div>

                <div class="patient-cart-item-metric patient-cart-item-total">
                  <span>{{ __('patient.cart.line_total') }}</span>
                  <strong>BDT {{ number_format($line['line_total'], 2) }}</strong>
                </div>
              </article>
            @endforeach
          </div>

          <footer class="patient-cart-list-footer">
            <a href="{{ route('patient.medicine') }}">
              <i class="bi bi-arrow-left" aria-hidden="true"></i>
              {{ __('patient.cart.continue_comparing') }}
            </a>
          </footer>
        </section>
      </main>

      <aside class="patient-cart-sidebar">
        <section class="patient-cart-summary-card">
          <div class="patient-cart-summary-heading">
            <span><i class="bi bi-receipt" aria-hidden="true"></i></span>
            <div>
              <h2>{{ __('patient.cart.order_summary') }}</h2>
              <p>{{ trans_choice('patient.cart.item_count', $itemCount, ['count' => $itemCount]) }}</p>
            </div>
          </div>

          <div class="patient-cart-summary-row">
            <span>{{ __('patient.cart.pharmacy') }}</span>
            <strong>{{ $pharmacy->pharmacy_name }}</strong>
          </div>
          <div class="patient-cart-summary-row">
            <span>{{ __('patient.cart.items') }}</span>
            <strong>{{ $itemCount }}</strong>
          </div>
          <div class="patient-cart-summary-total">
            <span>{{ __('patient.cart.subtotal_label') }}</span>
            <strong>BDT {{ number_format($subtotal, 2) }}</strong>
          </div>

          <p class="patient-cart-checkout-note">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            {{ __('patient.cart.checkout_note') }}
          </p>

          <a class="patient-cart-checkout-action" href="{{ route('patient.checkout') }}">
            {{ __('patient.cart.proceed_checkout') }}
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
          </a>
        </section>

        <section class="patient-cart-side-card">
          <span class="patient-cart-side-icon"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i></span>
          <div>
            <h2>{{ __('patient.cart.prescription_only_title') }}</h2>
            <p>{{ __('patient.cart.prescription_only_desc') }}</p>
          </div>
          <a href="{{ route('patient.prescriptions') }}">
            {{ __('patient.cart.my_prescriptions') }}
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
          </a>
        </section>

        <section class="patient-cart-side-card">
          <span class="patient-cart-side-icon"><i class="bi bi-bag-check" aria-hidden="true"></i></span>
          <div>
            <h2>{{ __('patient.cart.orders_title') }}</h2>
            <p>{{ __('patient.cart.orders_desc') }}</p>
          </div>
          <a href="{{ route('patient.orders') }}">
            {{ __('patient.cart.my_orders') }}
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
          </a>
        </section>

        <section class="patient-cart-side-card">
          <span class="patient-cart-side-icon"><i class="bi bi-chat-square-text" aria-hidden="true"></i></span>
          <div>
            <h2>{{ __('patient.cart.help_title') }}</h2>
            <p>{{ __('patient.cart.help_desc') }}</p>
          </div>
          <a href="{{ route('help.index') }}">
            {{ __('patient.cart.help_action') }}
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
          </a>
        </section>
      </aside>
    </div>
  @endif
</div>

<footer class="patient-dashboard-footer patient-cart-footer">
  <div class="patient-dashboard-footer-brand">
    <span class="patient-dashboard-footer-dot"></span>
    <div><strong>{{ __('dashboard.patient.platform_name') }}</strong><small>{{ __('home.brand_tagline') }}</small></div>
  </div>
  <p>{{ __('dashboard.patient.footer_tagline') }}</p>
</footer>
@endsection
