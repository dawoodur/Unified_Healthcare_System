@extends('layouts.app')
@section('title', 'Assign a Doctor')
@section('content')
<div class="card">
  <h1>Assign a Doctor</h1>
  <p><a href="{{ route('hospital.doctors') }}">&larr; Back to assigned doctors</a></p>
  <p class="muted">Search by name — only verified (approved) doctors not already on your staff will show up.</p>
  <form method="GET" action="{{ route('hospital.doctors.create') }}" class="field" style="max-width:400px;">
    <input type="text" name="search" value="{{ $searchTerm }}" placeholder="Doctor's name...">
    <button type="submit" class="btn" style="margin-top:0.5rem;">Search</button>
  </form>

  @if ($searchTerm !== '')
    @if ($searchResults->isEmpty())
      <p class="muted">No matching, unassigned, approved doctors found.</p>
    @else
      <table>
        <thead><tr><th>Name</th><th>Specialties</th><th></th></tr></thead>
        <tbody>
          @foreach ($searchResults as $doctor)
            <tr>
              <td>Dr. {{ $doctor->full_name }}</td>
              <td class="muted">{{ $doctor->specialties->pluck('specialty_name')->implode(', ') }}</td>
              <td>
                <form method="POST" action="{{ route('hospital.doctors.assign', $doctor) }}" data-confirm="Assign Dr. {{ $doctor->full_name }} to your hospital?">
                  @csrf
                  <button type="submit" class="btn" style="padding:0.3rem 0.7rem;">Assign</button>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  @endif
</div>
@endsection
