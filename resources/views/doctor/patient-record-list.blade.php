@extends('layouts.app')
@section('title', $patient->full_name . "'s Records")
@section('content')
@include('doctor.partials.quick-nav')
@include('doctor.partials.page-header', [
  'icon' => 'bi-folder2-open',
  'title' => $patient->full_name . "'s Records",
  'subtitle' => 'Age ' . $patient->age . ' · ' . ucfirst($patient->gender) . ' · Blood group ' . $patient->blood_group,
])
<div class="doctor-card">
  <p class="alert alert-info">Your access expires {{ $grant->expires_at->format('D, M j Y g:i A') }}.</p>
  <p><button type="button" id="print-record-btn" class="btn btn-secondary">Print full health record</button></p>
</div>

@if ($healthProfile)
  <div class="doctor-card">
    <h2>Personal Health Information</h2>
    <p class="muted">Patient-maintained health context stored alongside the medical record.</p>
    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0.65rem 1.25rem;">
      <div><strong>Cholesterol:</strong> {{ $healthProfile->cholesterol_status ?: 'Not on file' }}</div>
      <div><strong>Diabetes risk:</strong> {{ $healthProfile->diabetes_risk ?: 'Not on file' }}</div>
      <div><strong>Diet:</strong> {{ $healthProfile->diet_notes ?: 'Not on file' }}</div>
      <div><strong>Therapy:</strong> {{ $healthProfile->therapy_notes ?: 'Not on file' }}</div>
      <div><strong>Major health risks:</strong> {{ $healthProfile->major_health_risks ?: 'Not on file' }}</div>
      <div><strong>Chronic conditions:</strong> {{ $healthProfile->chronic_conditions ?: 'Not on file' }}</div>
    </div>
    @if ($healthProfile->lifestyle_notes)
      <p style="margin-top:0.75rem;"><strong>Lifestyle notes:</strong> {{ $healthProfile->lifestyle_notes }}</p>
    @endif
  </div>
@endif

@if ($allergies->isNotEmpty())
  <div class="doctor-card" style="border-color:var(--bs-danger);">
    <h2 style="color:var(--bs-danger);">Known Allergies</h2>
    <ul style="margin:0;padding-left:1.2rem;">
      @foreach ($allergies as $allergy)
        <li>{{ $allergy->allergen }}@if($allergy->reaction) <span class="muted">({{ $allergy->reaction }})</span>@endif</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="doctor-card">
  <h2>Vitals history</h2>
  @if ($vitals->isEmpty())
    <p class="muted">No vitals recorded yet.</p>
  @else
    <table class="doctor-table">
      <thead><tr><th>Date</th><th>Blood pressure</th><th>Heart rate</th><th>Temp (°C)</th><th>Height (cm)</th><th>Weight (kg)</th><th>Recorded by</th></tr></thead>
      <tbody>
        @foreach ($vitals as $vital)
          <tr>
            <td>{{ $vital->recorded_at->format('D, M j Y') }}</td>
            <td>{{ $vital->bloodPressureLabel() }}</td>
            <td class="muted">{{ $vital->heart_rate ?? '—' }}</td>
            <td class="muted">{{ $vital->temperature_celsius ?? '—' }}</td>
            <td class="muted">{{ $vital->height_cm ?? '—' }}</td>
            <td class="muted">{{ $vital->weight_kg ?? '—' }}</td>
            <td class="muted">Dr. {{ $vital->recordedByDoctor->full_name ?? '—' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>

<div class="doctor-card">
  @if ($records->isEmpty())
    <p class="muted">This patient has no records yet.</p>
  @else
    <table class="doctor-table">
      <thead><tr><th>Type</th><th>Description</th><th>Date</th><th></th></tr></thead>
      <tbody>
        @foreach ($records as $record)
          <tr>
            <td><span class="badge">{{ $record->typeLabel() }}</span></td>
            <td>{{ $record->description ?? '—' }}</td>
            <td class="muted">{{ $record->created_at->format('D, M j Y') }}</td>
            <td>
              @if ($record->record_type === 'prescription' && $record->reference_id)
                <a href="{{ route('prescriptions.show', $record->reference_id) }}" class="btn" style="padding:0.3rem 0.7rem;">View</a>
              @endif
              @if ($record->file_path)
                <a href="{{ route('records.download', $record) }}" class="btn btn-secondary" style="padding:0.3rem 0.7rem;">Download</a>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>

<p><a href="{{ route('doctor.records') }}">&larr; Back to patient list</a></p>

@push('scripts')
<script>
  document.getElementById('print-record-btn').addEventListener('click', function () {
    const patientName = @json($patient->full_name);
    const printUrl = @json(route('doctor.records.print', $patient));
    showConfirmModal('Print ' + patientName + "'s full health record now?", function () {
      window.location.href = printUrl;
    });
  });
</script>
@endpush
@endsection
