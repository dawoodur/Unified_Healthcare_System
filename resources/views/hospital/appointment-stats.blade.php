@extends('layouts.app')
@section('title', 'Appointment Stats')
@section('content')
<div class="card">
  <h1>Appointment Stats</h1>
  <p class="muted">Onsite appointment volume at your hospital, per doctor. Online appointments aren't tied to a specific hospital, so they aren't counted here.</p>
</div>

<div class="card">
  <h2>Hospital-wide totals</h2>
  <div class="grid grid-2">
    <p><strong>{{ $totals->total }}</strong> total appointment(s)</p>
    <p><strong>{{ $totals->completed }}</strong> completed</p>
    <p><strong>{{ $totals->booked }}</strong> upcoming / booked</p>
    <p><strong>{{ $totals->cancelled }}</strong> cancelled / no-show</p>
  </div>
</div>

<div class="card">
  <h2>By doctor</h2>
  @if ($byDoctor->isEmpty())
    <p class="muted">No onsite appointments have been booked at your hospital yet.</p>
  @else
    <table>
      <thead><tr><th>Doctor</th><th>Total</th><th>Completed</th><th>Upcoming</th><th>Cancelled/No-show</th></tr></thead>
      <tbody>
        @foreach ($byDoctor as $row)
          <tr>
            <td>Dr. {{ $row->doctor->full_name }}</td>
            <td>{{ $row->total }}</td>
            <td>{{ $row->completed }}</td>
            <td>{{ $row->booked }}</td>
            <td>{{ $row->cancelled }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>
@endsection
