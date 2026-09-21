@extends('layouts.app')
@section('title', 'Patient Records')
@section('content')
@include('doctor.partials.quick-nav')
@include('doctor.partials.page-header', [
  'icon' => 'bi-folder2-open',
  'title' => 'Patient Records',
  'subtitle' => "Every patient you've seen. Request access to view their records — they'll get an email code to approve it.",
])

<div class="doctor-card">
  @if ($patients->isEmpty())
    <p class="muted">You haven't had any appointments yet.</p>
  @else
    <table class="doctor-table">
      <thead><tr><th>Patient</th><th>Access status</th><th></th></tr></thead>
      <tbody>
        @foreach ($patients as $patient)
          @php $grant = $latestGrantByPatient->get($patient->patient_id); @endphp
          <tr>
            <td>{{ $patient->full_name }}</td>
            <td>
              @if (!$grant)
                <span class="muted">No request yet</span>
              @else
                <span class="badge {{ $grant->statusBadgeClass() }}">{{ $grant->statusLabel() }}</span>
                @if ($grant->isActive())
                  <div class="muted" style="font-size:0.8rem;">Until {{ $grant->expires_at->format('M j, g:i A') }}</div>
                @endif
              @endif
            </td>
            <td>
              @if ($grant && $grant->isActive())
                <a href="{{ route('doctor.records.show', $patient) }}" class="btn" style="padding:0.3rem 0.7rem;">View Records</a>
              @elseif ($grant && in_array($grant->status, ['requested', 'otp_sent']))
                <span class="muted">Waiting on patient</span>
              @else
                <form method="POST" action="{{ route('doctor.records.request', $patient) }}" data-confirm="Request access to {{ $patient->full_name }}'s records?">
                  @csrf
                  <button type="submit" class="btn" style="padding:0.3rem 0.7rem;">Request Access</button>
                </form>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>
@endsection
