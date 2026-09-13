@extends('layouts.app')
@section('title', __('patient.medicine.page_title'))
@section('content')
@php
  $cartItemCount = $cartLines->sum('quantity');
@endphp

<div class="patient-medicine-page">
  <nav class="patient-appointments-workspace-nav" aria-label="{{ __('patient.medicine.patient_navigation') }}">
    <a href="{{ route('patient.doctors') }}"><i class="bi bi-calendar2-check" aria-hidden="true"></i><span>{{ __('dashboard.patient.appointments_title') }}</span></a>
    <a class="active" href="{{ route('patient.prescriptions') }}"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span>{{ __('dashboard.patient.stat_prescriptions') }}</span></a>
    <a href="{{ route('patient.lab-tests') }}"><i class="bi bi-eyedropper" aria-hidden="true"></i><span>{{ __('dashboard.patient.lab_tests') }}</span></a>
    <a href="{{ route('patient.facilities') }}"><i class="bi bi-hospital" aria-hidden="true"></i><span>{{ __('dashboard.patient.hospital_services') }}</span></a>
    <a href="{{ route('patient.records') }}"><i class="bi bi-clipboard2" aria-hidden="true"></i><span>{{ __('dashboard.patient.records_short') }}</span></a>
  </nav>

  <section class="patient-medicine-hero" aria-labelledby="patient-medicine-title">
    <div class="patient-medicine-breadcrumb">
      <a href="{{ route('patient.dashboard') }}">{{ __('patient.medicine.home') }}</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <a href="{{ route('patient.prescriptions') }}">{{ __('patient.medicine.prescriptions') }}</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <span>{{ __('patient.medicine.title') }}</span>
    </div>

    <div class="patient-medicine-hero-grid">
      <div>
        <h1 id="patient-medicine-title">{{ __('patient.medicine.hero_title') }}</h1>
        <p>{{ __('patient.medicine.hero_desc') }}</p>

        <form method="GET" action="{{ route('patient.medicine') }}" class="patient-medicine-search-form">
          <div class="patient-medicine-search-control">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" id="medicineSearch" name="search" value="{{ $search }}" placeholder="{{ __('patient.medicine.search_placeholder') }}" aria-label="{{ __('patient.medicine.search_aria') }}">
          </div>
          <button type="submit">{{ __('patient.medicine.search_button') }}</button>
        </form>
      </div>

      <div class="patient-medicine-hero-visual" aria-hidden="true">
        <span class="patient-medicine-hero-note">{{ __('patient.medicine.hero_note') }}</span>
        <span class="patient-medicine-hero-prescription"><i class="bi bi-file-earmark-medical"></i></span>
        <span class="patient-medicine-hero-pill"><i class="bi bi-capsule-pill"></i></span>
      </div>
    </div>
  </section>

  <div class="patient-medicine-layout">
    <main class="patient-medicine-main">
      @if (!$selectedMedicine && $search === '')
        <section class="patient-medicine-start-card">
          <span class="patient-medicine-start-icon"><i class="bi bi-capsule-pill" aria-hidden="true"></i></span>
          <div>
            <h2>{{ __('patient.medicine.start_title') }}</h2>
            <p>{!! __('patient.medicine.start_desc', ['prescriptions_link' => '<a href="' . route('patient.prescriptions') . '">' . __('patient.medicine.my_prescriptions') . '</a>']) !!}</p>
          </div>
        </section>
      @endif

      @if (!$selectedMedicine && $search !== '')
        <section class="patient-medicine-results-card" aria-labelledby="patient-medicine-results-title">
          <header class="patient-medicine-section-heading">
            <div>
              <h2 id="patient-medicine-results-title">{{ __('patient.medicine.search_results') }}</h2>
              <span>{{ trans_choice('patient.medicine.result_count', $medicines->count(), ['count' => $medicines->count()]) }}</span>
            </div>
          </header>

          @if ($medicines->isEmpty())
            <div class="patient-medicine-empty-state">
              <span><i class="bi bi-search" aria-hidden="true"></i></span>
              <div>
                <strong>{{ __('patient.medicine.no_match_title') }}</strong>
                <p>{{ __('patient.medicine.no_match', ['search' => $search]) }}</p>
              </div>
            </div>
          @else
            <div class="patient-medicine-search-results">
              @foreach ($medicines as $medicine)
                <article class="patient-medicine-result-row">
                  <span class="patient-medicine-result-icon"><i class="bi bi-capsule-pill" aria-hidden="true"></i></span>
                  <div class="patient-medicine-result-copy">
                    <strong>{{ $medicine->generic_name }}</strong>
                    <span>
                      @if ($medicine->brand_name){{ $medicine->brand_name }}@endif
                      @if ($medicine->strength){{ $medicine->brand_name ? ' · ' : '' }}{{ $medicine->strength }}@endif
                      @if ($medicine->form){{ ($medicine->brand_name || $medicine->strength) ? ' · ' : '' }}{{ $medicine->form }}@endif
                    </span>
                    <small>#m{{ $medicine->medicine_master_id }}</small>
                  </div>
                  <a href="{{ route('patient.medicine', ['medicine_master_id' => $medicine->medicine_master_id]) }}">
                    {{ __('patient.medicine.compare_prices') }} <i class="bi bi-arrow-right" aria-hidden="true"></i>
                  </a>
                </article>
              @endforeach
            </div>
          @endif
        </section>
      @endif

      @if ($selectedMedicine)
        <section class="patient-medicine-selected-card">
          <div class="patient-medicine-selected-main">
            <span class="patient-medicine-selected-icon"><i class="bi bi-capsule-pill" aria-hidden="true"></i></span>
            <div>
              <small>{{ __('patient.medicine.comparing') }}</small>
              <h2>{{ $selectedMedicine->generic_name }}</h2>
              <p>
                @if ($selectedMedicine->brand_name){{ $selectedMedicine->brand_name }}@endif
                @if ($selectedMedicine->strength){{ $selectedMedicine->brand_name ? ' · ' : '' }}{{ $selectedMedicine->strength }}@endif
                @if ($selectedMedicine->form){{ ($selectedMedicine->brand_name || $selectedMedicine->strength) ? ' · ' : '' }}{{ $selectedMedicine->form }}@endif
                <span>#m{{ $selectedMedicine->medicine_master_id }}</span>
              </p>
            </div>
          </div>

          @if ($isPrescribed)
            <span class="patient-medicine-prescribed-badge"><i class="bi bi-check-circle-fill" aria-hidden="true"></i>{{ __('patient.medicine.prescribed') }}</span>
          @else
            <span class="patient-medicine-reference-badge"><i class="bi bi-info-circle-fill" aria-hidden="true"></i>{{ __('patient.medicine.reference_only') }}</span>
          @endif
        </section>

        @if (!$isPrescribed)
          <div class="patient-medicine-prescription-note">
            <i class="bi bi-shield-exclamation" aria-hidden="true"></i>
            <p>{!! __('patient.medicine.not_prescribed_note', ['prescriptions_link' => '<a href="' . route('patient.prescriptions') . '">' . __('patient.medicine.my_prescriptions') . '</a>']) !!}</p>
          </div>
        @endif

        <section class="patient-medicine-results-card" aria-labelledby="patient-medicine-offerings-title">
          <header class="patient-medicine-section-heading">
            <div>
              <h2 id="patient-medicine-offerings-title">{{ __('patient.medicine.compare_pharmacies') }}</h2>
              <span>{{ trans_choice('patient.medicine.pharmacy_count', $offerings->count(), ['count' => $offerings->count()]) }}</span>
            </div>
            <a href="{{ route('patient.medicine') }}">{{ __('patient.medicine.compare_another') }}</a>
          </header>

          @if ($offerings->isEmpty())
            <div class="patient-medicine-empty-state">
              <span><i class="bi bi-shop" aria-hidden="true"></i></span>
              <div>
                <strong>{{ __('patient.medicine.no_stock_title') }}</strong>
                <p>{{ __('patient.medicine.no_stock') }}</p>
              </div>
            </div>
          @else
            <div class="patient-medicine-offerings-list">
              @foreach ($offerings as $i => $offering)
                <article class="patient-medicine-offering-row">
                  <div class="patient-medicine-pharmacy">
                    <span class="patient-medicine-pharmacy-icon"><i class="bi bi-shop" aria-hidden="true"></i></span>
                    <div>
                      <strong>{{ $offering->pharmacy->pharmacy_name }}</strong>
                      <span><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ $offering->pharmacy->address ?? __('patient.cart.address_not_listed') }}</span>
                    </div>
                  </div>

                  <div class="patient-medicine-stock-block">
                    <span>{{ __('patient.medicine.in_stock') }}</span>
                    <strong>{{ __('patient.medicine.units', ['count' => $offering->quantity_available]) }}</strong>
                  </div>

                  <div class="patient-medicine-price-block">
                    @if ($i === 0)
                      <span class="patient-medicine-lowest-badge"><i class="bi bi-arrow-down-circle-fill" aria-hidden="true"></i>{{ __('patient.medicine.lowest_price') }}</span>
                    @endif
                    <strong>BDT {{ number_format($offering->price, 2) }}</strong>
                    <small>{{ __('patient.medicine.per_unit') }}</small>
                  </div>

                  <div class="patient-medicine-offering-action">
                    @if ($isPrescribed)
                      <form method="POST" action="{{ route('patient.cart.add') }}">
                        @csrf
                        <input type="hidden" name="pharmacy_id" value="{{ $offering->pharmacy->pharmacy_id }}">
                        <input type="hidden" name="medicine_master_id" value="{{ $selectedMedicine->medicine_master_id }}">
                        <label>
                          <span>{{ __('patient.medicine.quantity') }}</span>
                          <input type="number" name="quantity" min="1" max="{{ min(100, $offering->quantity_available) }}" value="1" required>
                        </label>
                        <button type="submit"><i class="bi bi-cart-plus" aria-hidden="true"></i>{{ __('patient.medicine.add_to_cart') }}</button>
                      </form>
                    @else
                      <span class="patient-medicine-order-locked"><i class="bi bi-lock" aria-hidden="true"></i>{{ __('patient.medicine.not_prescribed') }}</span>
                    @endif
                  </div>
                </article>
              @endforeach
            </div>
          @endif
        </section>
      @endif
    </main>

    <aside class="patient-medicine-sidebar">
      <section class="patient-medicine-cart-card">
        <header>
          <div>
            <span><i class="bi bi-cart3" aria-hidden="true"></i></span>
            <h2>{{ __('patient.medicine.cart_title') }}</h2>
          </div>
          <em>{{ $cartItemCount }}</em>
        </header>

        @if ($cartLines->isEmpty())
          <div class="patient-medicine-cart-empty">
            <i class="bi bi-bag" aria-hidden="true"></i>
            <strong>{{ __('patient.medicine.cart_empty_title') }}</strong>
            <p>{{ __('patient.medicine.cart_empty_desc') }}</p>
          </div>
        @else
          @if ($cartPharmacy)
            <p class="patient-medicine-cart-pharmacy"><i class="bi bi-shop" aria-hidden="true"></i>{{ $cartPharmacy->pharmacy_name }}</p>
          @endif
          <div class="patient-medicine-cart-lines">
            @foreach ($cartLines as $line)
              <div>
                <span>
                  <strong>{{ $line['medicine']->generic_name }}</strong>
                  <small>{{ __('patient.medicine.cart_quantity', ['count' => $line['quantity']]) }}</small>
                </span>
                <strong>BDT {{ number_format($line['line_total'], 2) }}</strong>
              </div>
            @endforeach
          </div>
          <div class="patient-medicine-cart-total">
            <span>{{ __('patient.medicine.subtotal') }}</span>
            <strong>BDT {{ number_format($cartSubtotal, 2) }}</strong>
          </div>
          <a class="patient-medicine-cart-primary" href="{{ route('patient.cart') }}">{{ __('patient.medicine.view_cart') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        @endif

        @if ($cartLines->isEmpty())
          <a class="patient-medicine-cart-secondary" href="{{ route('patient.cart') }}">{{ __('patient.medicine.view_cart') }}</a>
        @endif
      </section>

      <section class="patient-medicine-side-card">
        <span class="patient-medicine-side-icon"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i></span>
        <div>
          <h2>{{ __('patient.medicine.prescription_only_title') }}</h2>
          <p>{{ __('patient.medicine.prescription_only_desc') }}</p>
        </div>
        <a href="{{ route('patient.prescriptions') }}">{{ __('patient.medicine.my_prescriptions') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
      </section>

      <section class="patient-medicine-side-card">
        <span class="patient-medicine-side-icon"><i class="bi bi-bag-check" aria-hidden="true"></i></span>
        <div>
          <h2>{{ __('patient.medicine.orders_title') }}</h2>
          <p>{{ __('patient.medicine.orders_desc') }}</p>
        </div>
        <a href="{{ route('patient.orders') }}">{{ __('patient.medicine.my_orders') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
      </section>

      <section class="patient-medicine-help-card">
        <span><i class="bi bi-chat-square-text" aria-hidden="true"></i></span>
        <div>
          <h2>{{ __('patient.medicine.help_title') }}</h2>
          <p>{{ __('patient.medicine.help_desc') }}</p>
        </div>
        <a href="{{ route('help.index') }}">{{ __('patient.medicine.help_action') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
      </section>
    </aside>
  </div>
</div>

<footer class="patient-dashboard-footer patient-medicine-footer">
  <div class="patient-dashboard-footer-brand">
    <span class="patient-dashboard-footer-dot"></span>
    <div><strong>{{ __('dashboard.patient.platform_name') }}</strong><small>{{ __('home.brand_tagline') }}</small></div>
  </div>
  <p>{{ __('dashboard.patient.footer_tagline') }}</p>
</footer>
@endsection
