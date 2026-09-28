@extends('layouts.app')
@section('title', 'Platform Analytics')
@section('content')
<div class="card">
  <h1>Platform Analytics</h1>
  <p class="muted">Revenue and registrations over the last six months, with activity across the platform.</p>
</div>
<div class="grid grid-2">
  @foreach (['Revenue (completed payments)' => $revenueByMonth, 'Registrations per Month' => $registrationsByMonth, 'Busiest Doctors' => $busiestDoctors, 'Most Ordered Medicines' => $topMedicines, 'Orders by Status' => $orderStatusCounts] as $heading => $series)
    <div class="card">
      <h2>{{ $heading }}</h2>
      @include('partials.bar-chart', ['data' => $series])
    </div>
  @endforeach
</div>
@endsection
