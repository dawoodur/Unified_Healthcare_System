@extends('layouts.app')
@section('title', 'Leave Dates')
@section('content')
@include('doctor.partials.quick-nav')
@include('doctor.partials.page-header', [
  'icon' => 'bi-calendar-x',
  'title' => 'Leave Dates',
  'subtitle' => 'Block a date to prevent new appointment bookings that day.',
])
<div class="doctor-card">
  <form method="POST" action="{{ route('doctor.leave.store') }}">
    @csrf
    <label for="leave-date">Date</label>
    <input id="leave-date" type="date" name="leave_date" min="{{ now()->toDateString() }}" value="{{ old('leave_date') }}" required>
    <label for="leave-reason">Reason (optional)</label>
    <input id="leave-reason" name="reason" maxlength="255" value="{{ old('reason') }}">
    <button class="btn btn-primary" type="submit">Block date</button>
  </form>
</div>
<div class="doctor-card">
  <h2>Upcoming Leave</h2>
  <div class="table-responsive"><table class="doctor-table">
    <thead><tr><th>Date</th><th>Reason</th><th>Action</th></tr></thead>
    <tbody>
      @forelse ($leaveDates as $leave)
        <tr><td>{{ $leave->leave_date->format('d M Y') }}</td><td>{{ $leave->reason ?: '—' }}</td>
          <td><form method="POST" action="{{ route('doctor.leave.destroy', $leave) }}">@csrf<button class="btn btn-outline-secondary" type="submit">Unblock</button></form></td>
        </tr>
      @empty
        <tr><td colspan="3">No leave dates scheduled.</td></tr>
      @endforelse
    </tbody>
  </table></div>
</div>
@endsection
