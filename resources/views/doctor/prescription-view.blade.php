@extends('layouts.app')
@section('title', 'Prescription for ' . $appointment->patient->full_name)
@section('content')
<div class="card">
  <h1>Prescription for {{ $appointment->patient->full_name }}</h1>
  <p class="muted">Visit on {{ $appointment->appointment_date->format('D, M j Y') }} &middot; Issued {{ $appointment->prescription->issued_at->format('D, M j Y g:i A') }}</p>
  <p class="alert alert-info">This prescription has already been issued and can't be edited — this is a read-only view.</p>
  @if ($appointment->prescription->diagnosis_notes)
    <p><strong>Diagnosis notes:</strong> {{ $appointment->prescription->diagnosis_notes }}</p>
  @endif
</div>

<div class="card">
  <h2>Medicines</h2>
  <table>
    <thead><tr><th>Medicine</th><th>For Illness</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th>Notes</th></tr></thead>
    <tbody>
      @foreach ($appointment->prescription->items as $item)
        <tr>
          <td>{{ $item->medicine->generic_name ?? '—' }}@if($item->medicine?->brand_name) ({{ $item->medicine->brand_name }})@endif <span class="muted">#m{{ $item->medicine_master_id }}</span></td>
          <td class="muted">{{ $item->for_illness ?? '—' }}</td>
          <td class="muted">{{ $item->dosage ?? '—' }}</td>
          <td class="muted">{{ $item->frequency ?? '—' }}</td>
          <td class="muted">{{ $item->duration_days ? $item->duration_days . ' day(s)' : '—' }}</td>
          <td class="muted">{{ $item->notes ?? '—' }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>

@if ($appointment->prescription->facilityItems->isNotEmpty())
  <div class="card">
    <h2>Tests &amp; Operations</h2>
    <table>
      <thead><tr><th>Test/Operation</th><th>Category</th><th>Notes</th></tr></thead>
      <tbody>
        @foreach ($appointment->prescription->facilityItems as $item)
          <tr>
            <td>{{ $item->facilityType->name ?? '—' }}</td>
            <td class="muted">{{ $item->facilityType->category->category_name ?? '—' }}</td>
            <td class="muted">{{ $item->notes ?? '—' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif

<p><a href="{{ route('doctor.appointments') }}">&larr; Back to appointments</a></p>
@endsection
