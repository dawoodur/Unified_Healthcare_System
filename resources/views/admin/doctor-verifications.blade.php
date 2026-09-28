@extends('layouts.app')
@section('title', 'Doctor Verifications')
@section('content')
<div class="card">
  <h1>Doctor Verifications</h1>
  <p class="muted">Review uploaded certificates. Approving one makes that doctor visible in patient search; rejecting keeps them hidden.</p>
</div>

<div class="card">
  <h2>Pending review ({{ $pending->count() }})</h2>
  @if ($pending->isEmpty())
    <p class="muted">Nothing waiting on review.</p>
  @else
    <table>
      <thead>
        <tr>
          <th>Doctor</th>
          <th>Uploaded</th>
          <th>Certificate</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($pending as $certificate)
          <tr>
            <td>Dr. {{ $certificate->doctor->full_name ?? '—' }} <span class="muted">({{ $certificate->doctor->account?->uidTag() ?? '—' }})</span></td>
            <td>{{ $certificate->uploaded_at->format('D, M j Y g:i A') }}</td>
            <td><a href="{{ route('admin.doctor-verifications.download', $certificate) }}" target="_blank">View file</a></td>
            <td style="white-space:nowrap;">
              <form method="POST" action="{{ route('admin.doctor-verifications.approve', $certificate) }}" style="display:inline;" data-confirm="Approve Dr. {{ $certificate->doctor->full_name ?? 'this doctor' }}'s certificate? They'll become visible to patients.">
                @csrf
                <button type="submit" class="btn" style="padding:0.3rem 0.7rem;">Approve</button>
              </form>
              <form method="POST" action="{{ route('admin.doctor-verifications.reject', $certificate) }}" style="display:inline;" data-confirm="Reject Dr. {{ $certificate->doctor->full_name ?? 'this doctor' }}'s certificate?">
                @csrf
                <button type="submit" class="btn btn-danger" style="padding:0.3rem 0.7rem;">Reject</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>

<div class="card">
  <h2>Previously reviewed</h2>
  @if ($reviewed->isEmpty())
    <p class="muted">No certificates reviewed yet.</p>
  @else
    <table>
      <thead>
        <tr>
          <th>Doctor</th>
          <th>Uploaded</th>
          <th>Certificate</th>
          <th>Status</th>
          <th>Reviewed at</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($reviewed as $certificate)
          <tr>
            <td>Dr. {{ $certificate->doctor->full_name ?? '—' }} <span class="muted">({{ $certificate->doctor->account?->uidTag() ?? '—' }})</span></td>
            <td>{{ $certificate->uploaded_at->format('D, M j Y g:i A') }}</td>
            <td><a href="{{ route('admin.doctor-verifications.download', $certificate) }}" target="_blank">View file</a></td>
            <td><span class="badge {{ $certificate->verification_status === 'approved' ? 'text-bg-success' : 'text-bg-danger' }}">{{ ucfirst($certificate->verification_status) }}</span></td>
            <td>{{ $certificate->reviewed_at?->format('D, M j Y g:i A') ?? '—' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>
@endsection
