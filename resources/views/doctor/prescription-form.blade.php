@extends('layouts.app')
@section('title', 'Issue Prescription')
@section('content')
<div class="card">
  <h1>Prescription for {{ $appointment->patient->full_name }}</h1>
  <p class="muted">Visit on {{ $appointment->appointment_date->format('D, M j Y') }}. Add each medicine one at a time, then issue the prescription once you're done.</p>
</div>

@include('doctor.partials.prescription-form', compact('appointment', 'draft', 'medicines', 'draftMedicines', 'allergies'))
@endsection
