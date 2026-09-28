@extends('layouts.app')
@section('title', 'Donation Confirmations')
@section('content')
<div class="card">
  <h1>Blood Donation Confirmations</h1>
  <p class="muted">
    Donors logged these donations at your hospital. Confirming one starts their {{ \App\Models\BloodDonationCooldown::DAYS }}-day
    countdown before they can donate again, and credits them {{ $pointsPerDonation }} reward points. Nothing is credited until you confirm.
  </p>
</div>

<div class="card">
  <h2>Waiting for you ({{ $pending->count() }})</h2>
  @if ($pending->isEmpty())
    <p class="muted">No donations waiting to be confirmed.</p>
  @else
    <div class="table-responsive">
      <table>
        <thead><tr><th>Donor</th><th>Blood group</th><th>Donation date</th><th>Logged</th><th></th></tr></thead>
        <tbody>
          @foreach ($pending as $donation)
            <tr>
              <td>{{ $donation->patient->full_name }}</td>
              <td><span class="badge">{{ $donation->patient->blood_group ?? '—' }}</span></td>
              <td>{{ $donation->donated_at->format('D, M j Y') }}</td>
              <td class="muted">{{ $donation->created_at->diffForHumans() }}</td>
              <td>
                <form method="POST" action="{{ route('hospital.blood-donations.confirm', $donation) }}" style="display:inline;"
                      data-confirm="Confirm {{ $donation->patient->full_name }} donated on {{ $donation->donated_at->format('M j, Y') }}? They will receive {{ $pointsPerDonation }} points.">
                  @csrf
                  <button type="submit" class="btn" style="padding:0.3rem 0.7rem;">Confirm</button>
                </form>
                <form method="POST" action="{{ route('hospital.blood-donations.reject', $donation) }}" style="display:inline;"
                      data-confirm="Mark this donation as not confirmed?">
                  @csrf
                  <button type="submit" class="btn btn-secondary" style="padding:0.3rem 0.7rem;">Not confirmed</button>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>

<div class="card">
  <h2>Already reviewed</h2>
  @if ($reviewed->isEmpty())
    <p class="muted">Nothing reviewed yet.</p>
  @else
    <div class="table-responsive">
      <table>
        <thead><tr><th>Donor</th><th>Donation date</th><th>Status</th><th>Confirmed at</th></tr></thead>
        <tbody>
          @foreach ($reviewed as $donation)
            <tr>
              <td>{{ $donation->patient->full_name }}</td>
              <td>{{ $donation->donated_at->format('D, M j Y') }}</td>
              <td><span class="badge {{ $donation->statusBadgeClass() }}">{{ $donation->statusLabel() }}</span></td>
              <td class="muted">{{ $donation->confirmed_at?->format('M j, Y g:i A') ?? '—' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
@endsection
