@extends('layouts.app')
@section('title', 'Add Visiting Window')
@section('content')
<div class="card">
  <h1>Add a Visiting Window</h1>
  <p><a href="{{ route('doctor.availability') }}">&larr; Back to weekly schedule</a></p>

  @if ($hospitals->isEmpty())
    <div class="alert alert-info">
      No hospital has assigned you yet, so you can only add <strong>online</strong> windows for now.
      A hospital has to add you to their staff before you can offer onsite (in-person) visits —
      see the "Assigned Doctors" page on a hospital account.
    </div>
  @endif

  <form method="POST" action="{{ route('doctor.availability.store') }}" novalidate data-confirm="Add this visiting window?" style="max-width:520px;">
    @csrf
    <div class="field">
      <label for="day_of_week">Day of week</label>
      <select id="day_of_week" name="day_of_week" required>
        @foreach (\App\Models\DoctorAvailabilityTemplate::DAY_NAMES as $i => $name)
          <option value="{{ $i }}" @selected(old('day_of_week') == $i)>{{ $name }}</option>
        @endforeach
      </select>
    </div>
    <div class="grid grid-2">
      <div class="field">
        <label for="start_time">Start time</label>
        <input type="time" id="start_time" name="start_time" value="{{ old('start_time', '10:00') }}" required>
      </div>
      <div class="field">
        <label for="end_time">End time</label>
        <input type="time" id="end_time" name="end_time" value="{{ old('end_time', '12:00') }}" required>
      </div>
    </div>
    <div class="field">
      <label for="max_patients">How many patients can you see in this window?</label>
      <input type="number" id="max_patients" name="max_patients" min="1" max="200" value="{{ old('max_patients', 10) }}" required>
      <p class="muted">Patients booking this window get queue serial numbers 1, 2, 3... up to this limit — not individual time slots.</p>
    </div>
    <div class="field">
      <label for="mode">Mode</label>
      <select id="mode" name="mode" required>
        <option value="online" @selected(old('mode') !== 'onsite')>Online</option>
        <option value="onsite" @selected(old('mode') === 'onsite')>Onsite (at a hospital)</option>
      </select>
    </div>
    <div class="field">
      <label for="hospital_id">Hospital (only needed for onsite)</label>
      <select id="hospital_id" name="hospital_id" @if($hospitals->isEmpty()) disabled @endif>
        <option value="">— Online, no hospital —</option>
        @foreach ($hospitals as $hospital)
          <option value="{{ $hospital->hospital_id }}" @selected(old('hospital_id') == $hospital->hospital_id)>{{ $hospital->hospital_name }}</option>
        @endforeach
      </select>
      <p class="muted">If you choose "Onsite" above, you must pick which of your hospitals it's at.</p>
    </div>
    <button type="submit" class="btn">Add visiting window</button>
  </form>
</div>
@endsection
