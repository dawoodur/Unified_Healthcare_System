@extends('layouts.app')
@section('title', 'Request Operation')
@section('content')
<div class="card" style="max-width:560px;margin:0 auto;">
  <h1>{{ __('patient.operations.request_title', ['name' => $offering->facilityType->name]) }}</h1>
  <p class="muted">{{ $offering->hospital->hospital_name }} &middot; BDT {{ number_format($offering->price, 2) }} ({{ $offering->facilityType->unit_label }})</p>
  <p class="muted">{{ __('patient.operations.request_intro') }}</p>

  <form method="POST" action="{{ route('patient.operations.store', $offering) }}" data-confirm="Send this operation request to {{ $offering->hospital->hospital_name }}?">
    @csrf
    <div class="field">
      <label for="patient_notes">{{ __('patient.operations.notes_label') }}</label>
      <textarea id="patient_notes" name="patient_notes" rows="4" maxlength="1000" placeholder="{{ __('patient.operations.notes_placeholder') }}">{{ old('patient_notes') }}</textarea>
    </div>
    <button type="submit" class="btn btn-block">{{ __('patient.operations.send_request') }}</button>
  </form>
  <p class="muted"><a href="{{ route('patient.hospitals.show', $offering->hospital) }}">{{ __('patient.operations.back_to', ['name' => $offering->hospital->hospital_name]) }}</a></p>
</div>
@endsection
