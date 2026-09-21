@extends('layouts.app')
@section('title', 'My Appointments')
@section('content')
<div class="card">
  <h1>Appointments</h1>
  <p class="muted">{{ $todayCount }} appointment(s) today &middot; {{ $pending->count() }} pending &middot; {{ $completed->count() }} completed</p>
  @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  <form method="POST" action="{{ route('doctor.queue-status.update') }}" style="display:flex;gap:0.6rem;align-items:flex-end;flex-wrap:wrap;">
    @csrf
    <div class="field" style="margin:0;">
      <label for="current_serial">Now serving # (today)</label>
      <input type="number" id="current_serial" name="current_serial" min="0" value="{{ $todaysQueueStatus->current_serial ?? 0 }}" style="width:120px;">
    </div>
    <button type="submit" class="btn btn-secondary">Update</button>
  </form>
</div>

<div class="card">
  <h2>Pending</h2>
  @if ($pending->isEmpty())
    <p class="muted">No pending appointments.</p>
  @else
    <table>
      <thead>
        <tr><th>Serial</th><th>Patient</th><th>Date</th><th>Time</th><th>Type</th><th>Status</th><th>Payment</th><th></th></tr>
      </thead>
      <tbody>
        @foreach ($pending as $a)
          @include('doctor.partials.appointment-row', ['a' => $a])
        @endforeach
      </tbody>
    </table>
  @endif
</div>

<div class="card">
  <h2>Completed</h2>
  @if ($completed->isEmpty())
    <p class="muted">No completed appointments yet.</p>
  @else
    <table>
      <thead>
        <tr><th>Serial</th><th>Patient</th><th>Date</th><th>Time</th><th>Type</th><th>Status</th><th>Payment</th><th></th></tr>
      </thead>
      <tbody>
        @foreach ($completed as $a)
          @include('doctor.partials.appointment-row', ['a' => $a])
        @endforeach
      </tbody>
    </table>
  @endif
</div>
@endsection
