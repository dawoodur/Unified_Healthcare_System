@extends('layouts.app')
@section('title', __('patient.orders.page_title'))
@section('content')
@php
  $delivered = $completed->where('status', 'delivered')->values();
  $cancelled = $completed->where('status', 'cancelled')->values();

  $activeCount = $pending->count();
  $deliveredCount = $delivered->count();
  $cancelledCount = $cancelled->count();
@endphp

<div class="patient-orders-page">
  <nav class="patient-appointments-workspace-nav" aria-label="{{ __('patient.orders.patient_navigation') }}">
    <a href="{{ route('patient.doctors') }}"><i class="bi bi-calendar2-check" aria-hidden="true"></i><span>{{ __('dashboard.patient.appointments_title') }}</span></a>
    <a class="active" href="{{ route('patient.prescriptions') }}"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span>{{ __('dashboard.patient.stat_prescriptions') }}</span></a>
    <a href="{{ route('patient.lab-tests') }}"><i class="bi bi-eyedropper" aria-hidden="true"></i><span>{{ __('dashboard.patient.lab_tests') }}</span></a>
    <a href="{{ route('patient.facilities') }}"><i class="bi bi-hospital" aria-hidden="true"></i><span>{{ __('dashboard.patient.hospital_services') }}</span></a>
    <a href="{{ route('patient.records') }}"><i class="bi bi-clipboard2" aria-hidden="true"></i><span>{{ __('dashboard.patient.records_short') }}</span></a>
  </nav>

  <section class="patient-orders-hero" aria-labelledby="patient-orders-title">
    <div class="patient-orders-breadcrumb">
      <a href="{{ route('patient.dashboard') }}">{{ __('patient.orders.home') }}</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <a href="{{ route('patient.prescriptions') }}">{{ __('patient.orders.prescriptions') }}</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <span>{{ __('patient.orders.title') }}</span>
    </div>

    <div class="patient-orders-hero-grid">
      <div>
        <h1 id="patient-orders-title">{{ __('patient.orders.hero_title') }}</h1>
        <p>{{ __('patient.orders.hero_desc') }}</p>
      </div>

      <div class="patient-orders-hero-note">
        <span><i class="bi bi-truck" aria-hidden="true"></i></span>
        <div>
          <strong>{{ __('patient.orders.lifecycle_title') }}</strong>
          <small>{{ __('patient.orders.lifecycle_desc') }}</small>
        </div>
      </div>
    </div>
  </section>

  <div class="patient-orders-layout">
    <main class="patient-orders-main">
      <nav class="patient-orders-status-nav" aria-label="{{ __('patient.orders.status_navigation') }}">
        <a class="active" href="#orders-active">
          <i class="bi bi-clock-history" aria-hidden="true"></i>
          <span>{{ __('patient.orders.active') }}</span>
          <em>{{ $activeCount }}</em>
        </a>
        <a href="#orders-delivered">
          <i class="bi bi-check-circle" aria-hidden="true"></i>
          <span>{{ __('patient.orders.delivered') }}</span>
          <em>{{ $deliveredCount }}</em>
        </a>
        <a href="#orders-cancelled">
          <i class="bi bi-x-circle" aria-hidden="true"></i>
          <span>{{ __('patient.orders.cancelled') }}</span>
          <em>{{ $cancelledCount }}</em>
        </a>
      </nav>

      <section class="patient-orders-section" id="orders-active" aria-labelledby="orders-active-title">
        <header class="patient-orders-section-header">
          <span class="patient-orders-section-icon"><i class="bi bi-bag-check" aria-hidden="true"></i></span>
          <div>
            <h2 id="orders-active-title">{{ __('patient.orders.active_orders') }}</h2>
            <p>{{ __('patient.orders.active_orders_desc') }}</p>
          </div>
          <strong>{{ $activeCount }}</strong>
        </header>

        @if ($pending->isEmpty())
          <div class="patient-orders-empty">
            <span><i class="bi bi-bag" aria-hidden="true"></i></span>
            <div>
              <strong>{{ __('patient.orders.no_active_title') }}</strong>
              <p>{{ __('patient.orders.no_active_desc') }}</p>
            </div>
            <a href="{{ route('patient.prescriptions') }}">
              {{ __('patient.orders.see_prescriptions') }}
              <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </a>
          </div>
        @else
          <div class="patient-orders-list">
            @foreach ($pending as $order)
              @include('patient.partials.order-card', ['order' => $order])
            @endforeach
          </div>
        @endif
      </section>

      <section class="patient-orders-section patient-orders-section-delivered" id="orders-delivered" aria-labelledby="orders-delivered-title">
        <header class="patient-orders-section-header">
          <span class="patient-orders-section-icon"><i class="bi bi-check-lg" aria-hidden="true"></i></span>
          <div>
            <h2 id="orders-delivered-title">{{ __('patient.orders.delivered_orders') }}</h2>
            <p>{{ __('patient.orders.delivered_orders_desc') }}</p>
          </div>
          <strong>{{ $deliveredCount }}</strong>
        </header>

        @if ($delivered->isEmpty())
          <div class="patient-orders-empty">
            <span><i class="bi bi-check2-circle" aria-hidden="true"></i></span>
            <div>
              <strong>{{ __('patient.orders.no_delivered_title') }}</strong>
              <p>{{ __('patient.orders.no_completed') }}</p>
            </div>
          </div>
        @else
          <div class="patient-orders-list">
            @foreach ($delivered as $order)
              @include('patient.partials.order-card', ['order' => $order])
            @endforeach
          </div>
        @endif
      </section>

      <section class="patient-orders-section patient-orders-section-cancelled" id="orders-cancelled" aria-labelledby="orders-cancelled-title">
        <header class="patient-orders-section-header">
          <span class="patient-orders-section-icon"><i class="bi bi-x-lg" aria-hidden="true"></i></span>
          <div>
            <h2 id="orders-cancelled-title">{{ __('patient.orders.cancelled_orders') }}</h2>
            <p>{{ __('patient.orders.cancelled_orders_desc') }}</p>
          </div>
          <strong>{{ $cancelledCount }}</strong>
        </header>

        @if ($cancelled->isEmpty())
          <div class="patient-orders-empty">
            <span><i class="bi bi-bag-x" aria-hidden="true"></i></span>
            <div>
              <strong>{{ __('patient.orders.no_cancelled_title') }}</strong>
              <p>{{ __('patient.orders.no_cancelled_desc') }}</p>
            </div>
          </div>
        @else
          <div class="patient-orders-list">
            @foreach ($cancelled as $order)
              @include('patient.partials.order-card', ['order' => $order])
            @endforeach
          </div>
        @endif
      </section>
    </main>

    <aside class="patient-orders-sidebar">
      <section class="patient-orders-side-card patient-orders-side-card-primary">
        <span class="patient-orders-side-icon"><i class="bi bi-capsule-pill" aria-hidden="true"></i></span>
        <div>
          <h2>{{ __('patient.orders.order_more_title') }}</h2>
          <p>{{ __('patient.orders.order_more_desc') }}</p>
        </div>
        <a href="{{ route('patient.prescriptions') }}">
          {{ __('patient.orders.order_more_action') }}
          <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
      </section>

      <section class="patient-orders-side-card">
        <span class="patient-orders-side-icon"><i class="bi bi-cart3" aria-hidden="true"></i></span>
        <div>
          <h2>{{ __('patient.orders.cart_title') }}</h2>
          <p>{{ __('patient.orders.cart_desc') }}</p>
        </div>
        <a href="{{ route('patient.cart') }}">
          {{ __('patient.orders.view_cart') }}
          <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
      </section>

      <section class="patient-orders-side-card">
        <span class="patient-orders-side-icon"><i class="bi bi-chat-square-text" aria-hidden="true"></i></span>
        <div>
          <h2>{{ __('patient.orders.help_title') }}</h2>
          <p>{{ __('patient.orders.help_desc') }}</p>
        </div>
        <a href="{{ route('help.index') }}">
          {{ __('patient.orders.help_action') }}
          <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
      </section>

      <section class="patient-orders-info-card">
        <div class="patient-orders-info-heading">
          <span><i class="bi bi-info-circle-fill" aria-hidden="true"></i></span>
          <h2>{{ __('patient.orders.status_guide_title') }}</h2>
        </div>
        <ul>
          <li>{{ __('patient.orders.status_guide_placed') }}</li>
          <li>{{ __('patient.orders.status_guide_accepted') }}</li>
          <li>{{ __('patient.orders.status_guide_delivery') }}</li>
          <li>{{ __('patient.orders.status_guide_delivered') }}</li>
        </ul>
      </section>
    </aside>
  </div>
</div>

<footer class="patient-dashboard-footer patient-orders-footer">
  <div class="patient-dashboard-footer-brand">
    <span class="patient-dashboard-footer-dot"></span>
    <div><strong>{{ __('dashboard.patient.platform_name') }}</strong><small>{{ __('home.brand_tagline') }}</small></div>
  </div>
  <p>{{ __('dashboard.patient.footer_tagline') }}</p>
</footer>
@endsection
