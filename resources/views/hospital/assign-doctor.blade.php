@extends('layouts.app')
@section('title', 'Assigned Doctors')
@section('content')
<div class="card">
  <h1>Assigned Doctors</h1>
  <p><a href="{{ route('hospital.doctors.create') }}" class="btn">+ Assign a doctor</a></p>

  @if ($assignedDoctors->isEmpty())
    <p class="muted">No doctors assigned yet.</p>
  @else
    <table>
      <thead><tr><th>Name</th><th>Specialties</th><th>Fee</th><th></th></tr></thead>
      <tbody>
        @foreach ($assignedDoctors as $doctor)
          <tr>
            <td>Dr. {{ $doctor->full_name }}</td>
            <td class="muted">{{ $doctor->specialties->pluck('specialty_name')->implode(', ') }}</td>
            <td>BDT {{ number_format($doctor->consultation_fee, 2) }}</td>
            <td>
              <form method="POST" action="{{ route('hospital.doctors.revoke', $doctor) }}" data-confirm="Remove Dr. {{ $doctor->full_name }} from your hospital?">
                @csrf
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
