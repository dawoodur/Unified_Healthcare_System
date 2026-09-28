@extends('layouts.app')
@section('title', 'Facility Bookings')
@section('content')
<div class="card">
  <h1>Facility Bookings</h1>
  <p class="muted">Every patient booking made against your listed facilities. For beds/cabins, "Discharge" is what frees the bed up for the next patient — it stays occupied until you click it. For a test/procedure, you can attach the report/result when marking it completed — it's added straight to the patient's medical records.</p>
</div>

<div class="card">
  <h2>Pending</h2>
  @if ($pending->isEmpty())
    <p class="muted">No pending bookings.</p>
  @else
    @include('hospital.partials.facility-booking-table', ['bookings' => $pending])
  @endif
</div>

<div class="card">
  <h2>Completed</h2>
  @if ($completed->isEmpty())
    <p class="muted">No completed bookings yet.</p>
  @else
    @include('hospital.partials.facility-booking-table', ['bookings' => $completed])
  @endif
</div>
@endsection
