@extends('layouts.app')
@section('title', 'Doctor Dashboard')
@section('content')
@php
  $statusBadge = ['pending' => 'text-bg-warning', 'approved' => 'text-bg-success', 'rejected' => 'text-bg-danger'][$doctor->verification_status];
@endphp

<div class="card">
  <h1>{{ __('dashboard.doctor.welcome', ['name' => $doctor->full_name]) }}</h1>
  <p class="muted mb-2">{{ __('dashboard.doctor.here_today') }}</p>
  <p class="mb-1">
    {{ __('dashboard.doctor.verification_status') }}:
    <span class="badge {{ $statusBadge }}">{{ ucfirst($doctor->verification_status) }}</span>
  </p>
  <p class="muted mb-1">{{ __('dashboard.doctor.specialties') }}: {{ $doctor->specialties->pluck('specialty_name')->implode(', ') ?: __('dashboard.doctor.none_selected') }}</p>
  <p class="muted mb-0">{{ __('dashboard.doctor.consultation_fee') }}: BDT {{ number_format($doctor->consultation_fee, 2) }}</p>
</div>

<div class="row row-cols-2 row-cols-lg-4 g-3 mb-1">
  <div class="col">
    <div class="card stat-card-sm">
      <div class="stat-card-sm-icon bg-info-subtle text-info"><i class="bi bi-calendar2-check"></i></div>
      <div>
        <div class="muted small">{{ __('dashboard.doctor.stat_today_appointments') }}</div>
        <div class="stat-card-sm-value">{{ $todayAppointments->count() }}</div>
      </div>
    </div>
  </div>
  <div class="col">
    <div class="card stat-card-sm">
      <div class="stat-card-sm-icon icon-tint-brand"><i class="bi bi-hourglass-split"></i></div>
      <div>
        <div class="muted small">{{ __('dashboard.doctor.stat_pending_appointments') }}</div>
        <div class="stat-card-sm-value">{{ $pendingAppointmentsCount }}</div>
      </div>
    </div>
  </div>
  <div class="col">
    <div class="card stat-card-sm">
      <div class="stat-card-sm-icon bg-success-subtle text-success"><i class="bi bi-camera-video"></i></div>
      <div>
        <div class="muted small">{{ __('dashboard.doctor.stat_video_consultations') }}</div>
        <div class="stat-card-sm-value">{{ $todayVideoCount }}</div>
      </div>
    </div>
  </div>
  <div class="col">
    <div class="card stat-card-sm">
      <div class="stat-card-sm-icon bg-warning-subtle text-warning"><i class="bi bi-cash-coin"></i></div>
      <div>
        <div class="muted small">{{ __('dashboard.doctor.stat_today_earnings') }}</div>
        <div class="stat-card-sm-value">৳{{ number_format($todayEarnings, 0) }}</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-1">
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h5 mb-0">{{ __('dashboard.doctor.today_appointments_title') }}</h2>
      </div>
      @if ($todayAppointments->isEmpty())
        <p class="muted mb-0">{{ __('dashboard.doctor.no_today_appointments') }}</p>
      @else
        <ul class="recent-list">
          @foreach ($todayAppointments as $a)
            @php
              if ($a->status === 'completed') {
                  $apptStatusLabel = __('dashboard.doctor.appt_status_completed');
                  $apptStatusClass = 'text-bg-success';
              } elseif ($a->status === 'no_show') {
                  $apptStatusLabel = __('dashboard.doctor.appt_status_no_show');
                  $apptStatusClass = 'text-bg-danger';
              } elseif ($a->isJoinableNow()) {
                  $apptStatusLabel = __('dashboard.doctor.appt_status_in_progress');
                  $apptStatusClass = 'text-bg-warning';
              } else {
                  $apptStatusLabel = __('dashboard.doctor.appt_status_upcoming');
                  $apptStatusClass = 'text-bg-light border';
              }
            @endphp
            <li>
              <span class="text-nowrap muted small" style="min-width:4.5rem;">{{ \Illuminate\Support\Carbon::parse($a->appointment_time)->format('g:i A') }}</span>
              @include('partials.avatar', ['account' => $a->patient->account])
              <span class="flex-grow-1">
                <strong>{{ $a->patient->full_name }}</strong>
                <div class="muted small">{{ $a->appointment_type === 'online' ? __('patient.book.online') : __('patient.book.onsite') }}</div>
              </span>
              <span class="badge {{ $apptStatusClass }}">{{ $apptStatusLabel }}</span>
            </li>
          @endforeach
        </ul>
        <p class="mt-2 mb-0"><a href="{{ route('doctor.appointments') }}">{{ __('dashboard.doctor.view_all_appointments') }}</a></p>
      @endif
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card mb-3">
      <h2 class="h5 mb-2">{{ __('dashboard.doctor.calendar_title') }}</h2>
      @include('partials.mini-calendar', ['busyDates' => $calendarAppointmentDates])
    </div>

    <div class="card">
      <h2 class="h5 mb-2">{{ __('dashboard.doctor.patient_overview_title') }}</h2>
      @if (array_sum($patientOverview) === 0)
        <p class="muted mb-0">{{ __('dashboard.doctor.no_patient_history') }}</p>
      @else
        @include('partials.donut-chart', ['segments' => [
          ['label' => __('dashboard.doctor.overview_new'), 'value' => $patientOverview['new'], 'color' => 'var(--bs-primary)'],
          ['label' => __('dashboard.doctor.overview_follow_up'), 'value' => $patientOverview['follow_up'], 'color' => '#f59e0b'],
          ['label' => __('dashboard.doctor.overview_returning'), 'value' => $patientOverview['returning'], 'color' => '#22c55e'],
        ]])
      @endif
    </div>
  </div>
</div>

<div class="grid grid-2">
  <div class="card dashboard-card">
    <h2><i class="bi bi-calendar-week card-icon"></i>{{ __('dashboard.doctor.availability_title') }}</h2>
    <p class="muted">{{ __('dashboard.doctor.availability_desc') }}</p>
    <p><a href="{{ route('doctor.availability') }}">{{ __('dashboard.doctor.manage_availability') }}</a></p>
  </div>
  <div class="card dashboard-card">
    <h2><i class="bi bi-calendar2-check card-icon"></i>{{ __('dashboard.doctor.appointments_title') }}</h2>
    <p class="muted">{{ __('dashboard.doctor.appointments_desc') }}</p>
    <p><a href="{{ route('doctor.appointments') }}">{{ __('dashboard.doctor.view_appointments') }}</a></p>
  </div>
  <div class="card dashboard-card">
    <h2><i class="bi bi-folder2-open card-icon"></i>{{ __('dashboard.doctor.records_title') }}</h2>
    <p class="muted">{{ __('dashboard.doctor.records_desc') }}</p>
    <p><a href="{{ route('doctor.records') }}">{{ __('dashboard.doctor.patient_records') }}</a></p>
  </div>
  <div class="card dashboard-card">
    <h2><i class="bi bi-chat-dots card-icon"></i>{{ __('dashboard.doctor.inbox_title') }}</h2>
    <p class="muted">{{ __('dashboard.doctor.inbox_desc') }}</p>
    <p><a href="{{ route('inbox.index') }}">{{ __('dashboard.doctor.open_inbox') }}</a></p>
  </div>
  <div class="card dashboard-card">
    <h2><i class="bi bi-star card-icon"></i>{{ __('dashboard.doctor.reviews_title') }}</h2>
    <p class="muted">{{ __('dashboard.doctor.reviews_desc') }}</p>
    <p><a href="{{ route('doctor.reviews') }}">{{ __('dashboard.doctor.my_reviews') }}</a></p>
  </div>
  <div class="card dashboard-card">
    <h2><i class="bi bi-graph-up-arrow card-icon"></i>{{ __('dashboard.doctor.analytics_title') }}</h2>
    <p class="muted">{{ __('dashboard.doctor.analytics_desc') }}</p>
    <p><a href="{{ route('doctor.analytics') }}">{{ __('dashboard.doctor.view_analytics') }}</a></p>
  </div>
</div>
@endsection
