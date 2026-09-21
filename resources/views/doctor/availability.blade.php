@extends('layouts.app')
@section('title', 'Manage Availability')
@section('content')
<div class="card">
  <h1>Weekly Visiting Hours</h1>
  <p class="muted">These repeat every week. Patients booking into a window get a queue serial number — first to book is #1, next is #2, and so on — not a personal time slot.</p>
  <p><a href="{{ route('doctor.availability.create') }}" class="btn">+ Add visiting window</a> <a href="{{ route('doctor.leave') }}" class="btn btn-secondary">Leave / unavailable dates</a></p>

  @if ($templates->isEmpty())
    <p class="muted">You haven't set any visiting hours yet.</p>
  @else
    <table>
      <thead>
        <tr>
          <th>Day</th><th>Time</th><th>Mode</th><th>Hospital</th><th>Max patients</th><th></th>
        </tr>
      </thead>
      <tbody>
        @foreach ($templates as $t)
          <tr>
            <td>{{ \App\Models\DoctorAvailabilityTemplate::DAY_NAMES[$t->day_of_week] }}</td>
            <td>{{ \Illuminate\Support\Carbon::parse($t->start_time)->format('g:i A') }} - {{ \Illuminate\Support\Carbon::parse($t->end_time)->format('g:i A') }}</td>
            <td><span class="badge">{{ ucfirst($t->mode) }}</span></td>
            <td class="muted">{{ $t->hospital->hospital_name ?? '—' }}</td>
            <td>{{ $t->max_patients }}</td>
            <td>
              <form method="POST" action="{{ route('doctor.availability.destroy', $t) }}" data-confirm="Remove this visiting window?">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger" style="padding:0.3rem 0.7rem;">Remove</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>
@endsection
