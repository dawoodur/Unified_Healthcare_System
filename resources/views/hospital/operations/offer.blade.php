@extends('layouts.app')
@section('title', 'Make Offer')
@section('content')
<div class="hospital-page">
  @include('hospital.partials.quick-nav')
  @include('hospital.partials.page-header', [
    'icon' => 'bi-heart-pulse',
    'title' => $operationRequest->facilityType->name . ' for ' . $operationRequest->patient->full_name,
    'subtitle' => $operationRequest->patient->account?->uidTag() . ' · BDT ' . number_format($operationRequest->price, 2),
  ])

  <div class="hospital-card" style="max-width:600px;">
    @if ($operationRequest->patient_notes)
      <p class="hospital-op-notes">“{{ $operationRequest->patient_notes }}”</p>
    @endif

    @if ($doctors->isEmpty())
      <p class="hospital-notice is-error">
        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
        You have no assigned doctors yet — assign one under Doctors before making an offer.
      </p>
      <a class="hospital-btn is-primary" href="{{ route('hospital.doctors') }}">Go to Doctors</a>
    @else
      <form method="POST" action="{{ route('hospital.operations.offer', $operationRequest) }}" data-confirm="Send this offer to {{ $operationRequest->patient->full_name }}?">
        @csrf
        <div class="field">
          <label for="assigned_doctor_id">Doctor</label>
          <select id="assigned_doctor_id" name="assigned_doctor_id" required>
            <option value="">Select...</option>
            @foreach ($doctors as $doctor)
              <option value="{{ $doctor->doctor_id }}" @selected(old('assigned_doctor_id') == $doctor->doctor_id)>Dr. {{ $doctor->full_name }}</option>
            @endforeach
          </select>
        </div>
        <div class="grid grid-2">
          <div class="field">
            <label for="scheduled_date">Date</label>
            <input type="date" id="scheduled_date" name="scheduled_date" value="{{ old('scheduled_date', $suggestedDate) }}" min="{{ now()->toDateString() }}" required>
          </div>
          <div class="field">
            <label for="scheduled_time">Time</label>
            <input type="time" id="scheduled_time" name="scheduled_time" value="{{ old('scheduled_time', '09:00') }}" required>
          </div>
        </div>
        <div class="field">
          <label for="serial_number">Serial number</label>
          <input type="number" id="serial_number" name="serial_number" min="1" value="{{ old('serial_number', $suggestedSerial) }}" required>
          <p class="muted">Suggested next number for that date — you can set any number, and can still change it later if a more urgent case comes in (as long as the patient hasn't accepted yet).</p>
        </div>
        <button type="submit" class="hospital-btn is-primary">Send offer</button>
        <a class="hospital-btn is-ghost" href="{{ route('hospital.operations') }}">Cancel</a>
      </form>
    @endif
  </div>
</div>
@endsection
