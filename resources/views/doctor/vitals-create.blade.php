@extends('layouts.app')
@section('title', 'Log Vitals')
@section('content')
<div class="card" style="max-width:520px;">
  <h1>Log Vitals for {{ $appointment->patient->full_name }}</h1>
  <p class="muted">Visit on {{ $appointment->appointment_date->format('D, M j Y') }}. Leave any field blank if you didn't measure it.</p>

  <form method="POST" action="{{ route('doctor.appointments.vitals.store', $appointment) }}" data-confirm="Save these vitals?">
    @csrf
    <div class="grid grid-2">
      <div class="field">
        <label for="blood_pressure_systolic">Blood pressure — systolic</label>
        <input type="number" id="blood_pressure_systolic" name="blood_pressure_systolic" min="0" max="300" placeholder="e.g. 120">
      </div>
      <div class="field">
        <label for="blood_pressure_diastolic">Blood pressure — diastolic</label>
        <input type="number" id="blood_pressure_diastolic" name="blood_pressure_diastolic" min="0" max="200" placeholder="e.g. 80">
      </div>
    </div>
    <div class="grid grid-2">
      <div class="field">
        <label for="heart_rate">Heart rate (bpm)</label>
        <input type="number" id="heart_rate" name="heart_rate" min="0" max="300" placeholder="e.g. 72">
      </div>
      <div class="field">
        <label for="temperature_celsius">Temperature (°C)</label>
        <input type="number" id="temperature_celsius" name="temperature_celsius" min="0" max="99.9" step="0.1" placeholder="e.g. 37.0">
      </div>
    </div>
    <div class="grid grid-2">
      <div class="field">
        <label for="height_cm">Height (cm)</label>
        <input type="number" id="height_cm" name="height_cm" min="0" max="999.9" step="0.1" placeholder="e.g. 170">
      </div>
      <div class="field">
        <label for="weight_kg">Weight (kg)</label>
        <input type="number" id="weight_kg" name="weight_kg" min="0" max="999.9" step="0.1" placeholder="e.g. 65">
      </div>
    </div>
    <div class="field">
      <label for="notes">Notes (optional)</label>
      <textarea id="notes" name="notes" rows="3"></textarea>
    </div>
    <button type="submit" class="btn">Save Vitals</button>
  </form>
</div>
@endsection
