@extends('layouts.app')
@section('title', 'My Analytics')
@section('content')
@include('doctor.partials.quick-nav')
@include('doctor.partials.page-header', [
  'icon' => 'bi-graph-up-arrow',
  'title' => 'My Analytics',
  'subtitle' => 'Your activity over the last 6 months. Total unique patients seen: ' . $totalPatients . '.',
])

<div class="grid grid-2">
  <div class="doctor-card">
    <h2>Earnings (completed payments)</h2>
    @include('partials.bar-chart', ['data' => $earningsByMonth])
  </div>

  <div class="doctor-card">
    <h2>Appointments per Month</h2>
    @include('partials.bar-chart', ['data' => $appointmentsByMonth])
  </div>

  <div class="doctor-card">
    <h2>Appointments by Status</h2>
    @include('partials.bar-chart', ['data' => $statusCounts])
  </div>
</div>
@endsection
