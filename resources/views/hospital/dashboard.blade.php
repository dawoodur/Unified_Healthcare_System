@extends('layouts.app')
@section('title', 'Hospital Dashboard')
@section('content')
<div class="card">
  <h1>{{ $hospital->hospital_name }}</h1>
  <p class="muted mb-0">{{ auth()->user()->uidTag() }} &middot; {{ __('dashboard.hospital.registration_no') }} {{ $hospital->registration_number }}@if($hospital->city) &middot; {{ $hospital->city }}@endif</p>
</div>

<div class="row row-cols-2 row-cols-lg-4 g-3 mb-1">
  <div class="col">
    <div class="card stat-card-sm">
      <div class="stat-card-sm-icon icon-tint-brand"><i class="bi bi-person-badge"></i></div>
      <div>
        <div class="muted small">{{ __('dashboard.hospital.stat_total_doctors') }}</div>
        <div class="stat-card-sm-value">{{ $totalDoctors }}</div>
      </div>
    </div>
  </div>
  <div class="col">
    <div class="card stat-card-sm">
      <div class="stat-card-sm-icon bg-info-subtle text-info"><i class="bi bi-people"></i></div>
      <div>
        <div class="muted small">{{ __('dashboard.hospital.stat_total_patients') }}</div>
        <div class="stat-card-sm-value">{{ $totalPatients }}</div>
      </div>
    </div>
  </div>
  <div class="col">
    <div class="card stat-card-sm">
      <div class="stat-card-sm-icon bg-success-subtle text-success"><i class="bi bi-calendar2-check"></i></div>
      <div>
        <div class="muted small">{{ __('dashboard.hospital.stat_total_appointments') }}</div>
        <div class="stat-card-sm-value">{{ $totalAppointments }}</div>
      </div>
    </div>
  </div>
  <div class="col">
    <div class="card stat-card-sm">
      <div class="stat-card-sm-icon bg-warning-subtle text-warning"><i class="bi bi-cash-coin"></i></div>
      <div>
        <div class="muted small">{{ __('dashboard.hospital.stat_total_revenue') }}</div>
        <div class="stat-card-sm-value">৳{{ number_format($totalRevenue, 0) }}</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-1">
  <div class="col-lg-7">
    <div class="card h-100">
      <h2 class="h5 mb-2">{{ __('dashboard.hospital.overview_chart_title') }}</h2>
      @include('partials.line-chart', ['series' => [
        ['name' => __('dashboard.hospital.overview_appointments_series'), 'color' => 'var(--bs-primary)', 'data' => $appointmentsByMonth],
        ['name' => __('dashboard.hospital.overview_patients_series'), 'color' => '#22c55e', 'data' => $patientsByMonth],
      ]])
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card h-100">
      <h2 class="h5 mb-2">{{ __('dashboard.hospital.bed_availability_title') }}</h2>
      <div class="mini-stat-grid">
        <div>
          <div class="mini-stat-value">{{ $bedAvailability['total'] }}</div>
          <div class="mini-stat-label">{{ __('dashboard.hospital.bed_total') }}</div>
        </div>
        <div>
          <div class="mini-stat-value">{{ $bedAvailability['occupied'] }}</div>
          <div class="mini-stat-label">{{ __('dashboard.hospital.bed_occupied') }}</div>
        </div>
        <div>
          <div class="mini-stat-value">{{ $bedAvailability['available'] }}</div>
          <div class="mini-stat-label">{{ __('dashboard.hospital.bed_available') }}</div>
        </div>
        <div>
          <div class="mini-stat-value">{{ $bedAvailability['icu'] }}</div>
          <div class="mini-stat-label">{{ __('dashboard.hospital.bed_icu') }}</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-1">
  <div class="col-lg-6">
    <div class="card h-100">
      <h2 class="h5 mb-2">{{ __('dashboard.hospital.department_overview_title') }}</h2>
      @if ($departmentOverview->isEmpty())
        <p class="muted mb-0">{{ __('dashboard.hospital.no_department_data') }}</p>
      @else
        @include('partials.bar-chart', ['data' => $departmentOverview])
      @endif
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100">
      <h2 class="h5 mb-2">{{ __('dashboard.hospital.recent_activity_title') }}</h2>
      @if ($recentActivity->isEmpty())
        <p class="muted mb-0">{{ __('dashboard.hospital.no_recent_activity') }}</p>
      @else
        <ul class="activity-list">
          @foreach ($recentActivity as $item)
            <li>
              <i class="bi {{ $item['icon'] }} icon-tint-brand" style="padding:0.4rem;border-radius:50%;"></i>
              <span class="flex-grow-1">{{ $item['message'] }}</span>
              <span class="muted small text-nowrap">{{ $item['time']?->diffForHumans() }}</span>
            </li>
          @endforeach
        </ul>
      @endif
    </div>
  </div>
</div>

<div class="card mb-1">
  <h2 class="h5 mb-2">{{ __('dashboard.hospital.recent_appointments_title') }}</h2>
  @if ($recentAppointments->isEmpty())
    <p class="muted mb-0">{{ __('dashboard.hospital.no_recent_appointments') }}</p>
  @else
    <ul class="recent-list">
      @foreach ($recentAppointments as $a)
        <li>
          @include('partials.avatar', ['account' => $a->patient->account])
          <span class="flex-grow-1">
            <strong>{{ $a->patient->full_name }}</strong>
            <div class="muted small">Dr. {{ $a->doctor->full_name }} &middot; {{ $a->appointment_date->format('M j, Y') }}</div>
          </span>
          <span class="badge {{ $a->statusBadgeClass() }}">{{ $a->statusLabel() }}</span>
        </li>
      @endforeach
    </ul>
  @endif
</div>

<div class="grid grid-2">
  <div class="card dashboard-card">
    <h2><i class="bi bi-person-badge card-icon"></i>{{ __('dashboard.hospital.doctor_assignments_title') }}</h2>
    <p class="muted">{{ __('dashboard.hospital.doctor_assignments_desc') }}</p>
    <p><a href="{{ route('hospital.doctors') }}">{{ __('dashboard.hospital.manage_doctors') }}</a></p>
  </div>
  <div class="card dashboard-card">
    <h2><i class="bi bi-building card-icon"></i>{{ __('dashboard.hospital.facilities_title') }}</h2>
    <p class="muted">{{ __('dashboard.hospital.facilities_desc') }}</p>
    <p><a href="{{ route('hospital.facilities') }}">{{ __('dashboard.hospital.manage_facilities') }}</a> &middot; <a href="{{ route('hospital.facility-bookings') }}">{{ __('dashboard.hospital.view_bookings') }}</a></p>
  </div>
  <div class="card dashboard-card">
    <h2><i class="bi bi-heart-pulse card-icon"></i>{{ __('dashboard.hospital.operations_title') }}</h2>
    <p class="muted">{{ __('dashboard.hospital.operations_desc') }}</p>
    <p><a href="{{ route('hospital.operations') }}">{{ __('dashboard.hospital.view_operation_requests') }}</a></p>
  </div>
  <div class="card dashboard-card">
    <h2><i class="bi bi-credit-card card-icon"></i>{{ __('dashboard.hospital.payment_methods_title') }}</h2>
    <p class="muted">{{ __('dashboard.hospital.payment_methods_desc') }}</p>
    <p><a href="{{ route('hospital.payment-methods') }}">{{ __('dashboard.hospital.manage_payment_methods') }}</a></p>
  </div>
  <div class="card dashboard-card">
    <h2><i class="bi bi-graph-up-arrow card-icon"></i>{{ __('dashboard.hospital.stats_title') }}</h2>
    <p class="muted">{{ __('dashboard.hospital.stats_desc') }}</p>
    <p><a href="{{ route('hospital.appointment-stats') }}">{{ __('dashboard.hospital.view_stats') }}</a></p>
  </div>
  <div class="card dashboard-card">
    <h2><i class="bi bi-chat-dots card-icon"></i>{{ __('dashboard.hospital.inbox_title') }}</h2>
    <p class="muted">{{ __('dashboard.hospital.inbox_desc') }}</p>
    <p><a href="{{ route('inbox.index') }}">{{ __('dashboard.hospital.open_inbox') }}</a></p>
  </div>
  <div class="card dashboard-card">
    <h2><i class="bi bi-star card-icon"></i>{{ __('dashboard.hospital.reviews_title') }}</h2>
    <p class="muted">{{ __('dashboard.hospital.reviews_desc') }}</p>
    <p><a href="{{ route('hospital.reviews') }}">{{ __('dashboard.hospital.my_reviews') }}</a></p>
  </div>
  <div class="card dashboard-card">
    <h2><i class="bi bi-droplet-fill card-icon"></i>{{ __('dashboard.hospital.blood_title') }}</h2>
    <p class="muted">{{ __('dashboard.hospital.blood_desc') }}</p>
    <p><a href="{{ route('hospital.blood-requests') }}">{{ __('dashboard.hospital.blood_action') }}</a></p>
  </div>
</div>
@endsection
