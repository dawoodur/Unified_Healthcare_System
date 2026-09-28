@extends('layouts.app')
@section('title', 'Admin Dashboard')
@section('content')
<div class="card">
  <h1>{{ __('dashboard.admin.welcome', ['name' => $admin->full_name]) }}</h1>
</div>
<div class="row row-cols-2 row-cols-lg-5 g-3">
  <div class="col">
    <div class="card stat-card"><i class="bi bi-people stat-icon"></i><h2>{{ $counts['patients'] }}</h2><p class="muted mb-0">{{ __('dashboard.admin.patients') }}</p></div>
  </div>
  <div class="col">
    <div class="card stat-card"><i class="bi bi-person-badge stat-icon"></i><h2>{{ $counts['doctors'] }}</h2><p class="muted mb-0">{{ __('dashboard.admin.doctors') }} ({{ $counts['pending_doctor_verifications'] }} {{ __('dashboard.admin.pending_verification') }})</p></div>
  </div>
  <div class="col">
    <div class="card stat-card"><i class="bi bi-building stat-icon"></i><h2>{{ $counts['hospitals'] }}</h2><p class="muted mb-0">{{ __('dashboard.admin.hospitals') }}</p></div>
  </div>
  <div class="col">
    <div class="card stat-card"><i class="bi bi-capsule stat-icon"></i><h2>{{ $counts['pharmacies'] }}</h2><p class="muted mb-0">{{ __('dashboard.admin.pharmacies') }}</p></div>
  </div>
  <div class="col">
    <div class="card stat-card"><i class="bi bi-truck stat-icon"></i><h2>{{ $counts['delivery_agents'] }}</h2><p class="muted mb-0">{{ __('dashboard.admin.delivery_agents') }}</p></div>
  </div>
</div>

<div class="row g-3 mt-1 mb-1">
  <div class="col-lg-6">
    <div class="card h-100">
      <h2 class="h5 mb-2">{{ __('dashboard.admin.system_overview_title') }}</h2>
      @include('partials.line-chart', ['series' => [
        ['name' => __('dashboard.admin.overview_users_series'), 'color' => 'var(--bs-primary)', 'data' => $registrationsByMonth],
        ['name' => __('dashboard.admin.overview_appointments_series'), 'color' => '#22c55e', 'data' => $appointmentsByMonth],
      ]])
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card h-100">
      <h2 class="h5 mb-2">{{ __('dashboard.admin.revenue_overview_title') }}</h2>
      @include('partials.bar-chart', ['data' => $revenueByMonth])
    </div>
  </div>
</div>

<div class="row g-3 mb-1">
  <div class="col-lg-7">
    <div class="card h-100">
      <h2 class="h5 mb-2">{{ __('dashboard.admin.recent_activity_title') }}</h2>
      @if ($recentActivity->isEmpty())
        <p class="muted mb-0">{{ __('dashboard.admin.no_recent_activity') }}</p>
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
  <div class="col-lg-5">
    <div class="card h-100">
      <h2 class="h5 mb-2">{{ __('dashboard.admin.system_health_title') }}</h2>
      <ul class="recent-list">
        <li>
          <i class="bi bi-hdd-network bg-success-subtle text-success" style="padding:0.4rem;border-radius:50%;"></i>
          <span class="flex-grow-1">{{ __('dashboard.admin.health_server_label') }}</span>
          <span class="badge text-bg-success">{{ __('dashboard.admin.health_server_value') }}</span>
        </li>
        <li>
          <i class="bi bi-database {{ $systemHealth['db_ok'] ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}" style="padding:0.4rem;border-radius:50%;"></i>
          <span class="flex-grow-1">{{ __('dashboard.admin.health_database_label') }}</span>
          @if ($systemHealth['db_ok'])
            <span class="badge text-bg-success">{{ __('dashboard.admin.health_database_ok', ['size' => number_format($systemHealth['db_size_mb'], 1)]) }}</span>
          @else
            <span class="badge text-bg-danger">{{ __('dashboard.admin.health_database_down') }}</span>
          @endif
        </li>
        <li>
          <i class="bi bi-hdd icon-tint-brand" style="padding:0.4rem;border-radius:50%;"></i>
          <span class="flex-grow-1">
            {{ __('dashboard.admin.health_storage_label') }}
            <div class="muted small">{{ __('dashboard.admin.health_storage_free', ['free' => $systemHealth['storage_free_gb']]) }}</div>
          </span>
          <span class="badge {{ $systemHealth['storage_used_percent'] >= 90 ? 'text-bg-danger' : 'text-bg-secondary' }}">{{ $systemHealth['storage_used_percent'] }}%</span>
        </li>
        <li>
          <i class="bi bi-exclamation-octagon {{ $systemHealth['failed_jobs'] > 0 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }}" style="padding:0.4rem;border-radius:50%;"></i>
          <span class="flex-grow-1">{{ __('dashboard.admin.health_failed_jobs_label') }}</span>
          <span class="badge {{ $systemHealth['failed_jobs'] > 0 ? 'text-bg-danger' : 'text-bg-success' }}">{{ $systemHealth['failed_jobs'] }}</span>
        </li>
      </ul>
    </div>
  </div>
</div>

<div class="grid grid-2">
  <div class="card dashboard-card">
    <h2><i class="bi bi-search card-icon"></i>{{ __('dashboard.admin.search_users_title') }}</h2>
    <p class="muted">{{ __('dashboard.admin.search_users_desc') }}</p>
    <p><a href="{{ route('admin.users') }}">{{ __('dashboard.admin.search_users') }}</a></p>
  </div>
  <div class="card dashboard-card">
    <h2><i class="bi bi-cash-stack card-icon"></i>{{ __('dashboard.admin.transactions_title') }}</h2>
    <p class="muted">{{ __('dashboard.admin.transactions_desc') }}</p>
    <p><a href="{{ route('admin.transactions') }}">{{ __('dashboard.admin.view_transactions') }}</a></p>
  </div>
  <div class="card dashboard-card">
    <h2><i class="bi bi-patch-check card-icon"></i>{{ __('dashboard.admin.verifications_title') }}</h2>
    <p class="muted">{{ __('dashboard.admin.verifications_desc', ['count' => $counts['pending_doctor_verifications']]) }}</p>
    <p><a href="{{ route('admin.doctor-verifications') }}">{{ __('dashboard.admin.review_certificates') }}</a></p>
  </div>
  <div class="card dashboard-card">
    <h2><i class="bi bi-flag card-icon"></i>{{ __('dashboard.admin.reports_title') }}</h2>
    <p class="muted">{{ __('dashboard.admin.reports_desc') }}</p>
    <p><a href="{{ route('admin.reports') }}">{{ __('dashboard.admin.view_reports') }}</a></p>
  </div>
  <div class="card dashboard-card">
    <h2><i class="bi bi-graph-up-arrow card-icon"></i>{{ __('dashboard.admin.analytics_title') }}</h2>
    <p class="muted">{{ __('dashboard.admin.analytics_desc') }}</p>
    <p><a href="{{ route('admin.analytics') }}">{{ __('dashboard.admin.view_analytics') }}</a></p>
  </div>
</div>
@endsection
