@extends('layouts.app')
@section('title', 'Pharmacy Dashboard')
@section('content')
<div class="card">
  <h1>{{ $pharmacy->pharmacy_name }}</h1>
  <p class="muted mb-0">{{ auth()->user()->uidTag() }} &middot; {{ __('dashboard.pharmacy.etin') }} {{ $pharmacy->etin_number }}</p>
</div>

<div class="row row-cols-2 row-cols-lg-4 g-3 mb-1">
  <div class="col">
    <div class="card stat-card-sm">
      <div class="stat-card-sm-icon icon-tint-brand"><i class="bi bi-capsule"></i></div>
      <div>
        <div class="muted small">{{ __('dashboard.pharmacy.stat_total_medicines') }}</div>
        <div class="stat-card-sm-value">{{ $totalMedicines }}</div>
      </div>
    </div>
  </div>
  <div class="col">
    <div class="card stat-card-sm">
      <div class="stat-card-sm-icon bg-warning-subtle text-warning"><i class="bi bi-exclamation-triangle"></i></div>
      <div>
        <div class="muted small">{{ __('dashboard.pharmacy.stat_low_stock') }}</div>
        <div class="stat-card-sm-value">{{ $lowStockCount }}</div>
      </div>
    </div>
  </div>
  <div class="col">
    <div class="card stat-card-sm">
      <div class="stat-card-sm-icon bg-info-subtle text-info"><i class="bi bi-bag-check"></i></div>
      <div>
        <div class="muted small">{{ __('dashboard.pharmacy.stat_today_orders') }}</div>
        <div class="stat-card-sm-value">{{ $todayOrdersCount }}</div>
      </div>
    </div>
  </div>
  <div class="col">
    <div class="card stat-card-sm">
      <div class="stat-card-sm-icon bg-success-subtle text-success"><i class="bi bi-cash-coin"></i></div>
      <div>
        <div class="muted small">{{ __('dashboard.pharmacy.stat_today_sales') }}</div>
        <div class="stat-card-sm-value">৳{{ number_format($todaySales, 0) }}</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-1">
  <div class="col-lg-5">
    <div class="card h-100">
      <h2 class="h5 mb-2">{{ __('dashboard.pharmacy.stock_overview_title') }}</h2>
      @if (($inStockCount + $lowStockCount + $outOfStockCount) === 0)
        <p class="muted mb-0">{{ __('dashboard.pharmacy.no_stock_data') }}</p>
      @else
        @include('partials.donut-chart', ['totalLabel' => __('dashboard.pharmacy.donut_total_label'), 'segments' => [
          ['label' => __('dashboard.pharmacy.stock_in_stock'), 'value' => $inStockCount, 'color' => '#22c55e'],
          ['label' => __('dashboard.pharmacy.stock_low_stock'), 'value' => $lowStockCount, 'color' => '#f59e0b'],
          ['label' => __('dashboard.pharmacy.stock_out_of_stock'), 'value' => $outOfStockCount, 'color' => '#ef4444'],
        ]])
      @endif
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card h-100">
      <h2 class="h5 mb-2">{{ __('dashboard.pharmacy.top_selling_title') }}</h2>
      @if ($topMedicines->isEmpty())
        <p class="muted mb-0">{{ __('dashboard.pharmacy.no_top_selling') }}</p>
      @else
        @include('partials.bar-chart', ['data' => $topMedicines])
      @endif
    </div>
  </div>
</div>

<div class="card mb-1">
  <h2 class="h5 mb-2">{{ __('dashboard.pharmacy.expiring_title') }}</h2>
  @if ($expiringMedicines->isEmpty())
    <p class="muted mb-0">{{ __('dashboard.pharmacy.no_expiring') }}</p>
  @else
    <table>
      <thead>
        <tr>
          <th>{{ __('dashboard.pharmacy.col_medicine') }}</th>
          <th>{{ __('dashboard.pharmacy.col_batch') }}</th>
          <th>{{ __('dashboard.pharmacy.col_expiry') }}</th>
          <th>{{ __('dashboard.pharmacy.col_status') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($expiringMedicines as $batch)
          <tr>
            <td>{{ $batch->medicine->generic_name }}</td>
            <td>{{ $batch->batch_no }}</td>
            <td class="muted">{{ $batch->expiry_date->format('M j, Y') }}</td>
            <td>
              @if ($batch->isExpired())
                <span class="badge text-bg-danger">{{ __('dashboard.pharmacy.expiring_status_expired') }}</span>
              @else
                <span class="badge text-bg-warning">{{ __('dashboard.pharmacy.expiring_status_soon') }}</span>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>

<div class="grid grid-2">
  <div class="card dashboard-card">
    <h2><i class="bi bi-boxes card-icon"></i>{{ __('dashboard.pharmacy.inventory_title') }}</h2>
    <p class="muted">{{ __('dashboard.pharmacy.inventory_desc') }}</p>
    <p><a href="{{ route('pharmacy.inventory') }}">{{ __('dashboard.pharmacy.manage_inventory') }}</a></p>
  </div>
  <div class="card dashboard-card">
    <h2><i class="bi bi-bag-check card-icon"></i>{{ __('dashboard.pharmacy.orders_title') }}</h2>
    <p class="muted">{{ __('dashboard.pharmacy.orders_desc') }}</p>
    <p><a href="{{ route('pharmacy.orders') }}">{{ __('dashboard.pharmacy.view_orders') }}</a></p>
  </div>
  <div class="card dashboard-card">
    <h2><i class="bi bi-chat-dots card-icon"></i>{{ __('dashboard.pharmacy.inbox_title') }}</h2>
    <p class="muted">{{ __('dashboard.pharmacy.inbox_desc') }}</p>
    <p><a href="{{ route('inbox.index') }}">{{ __('dashboard.pharmacy.open_inbox') }}</a></p>
  </div>
  <div class="card dashboard-card">
    <h2><i class="bi bi-star card-icon"></i>{{ __('dashboard.pharmacy.reviews_title') }}</h2>
    <p class="muted">{{ __('dashboard.pharmacy.reviews_desc') }}</p>
    <p><a href="{{ route('pharmacy.reviews') }}">{{ __('dashboard.pharmacy.my_reviews') }}</a></p>
  </div>
</div>
@endsection
