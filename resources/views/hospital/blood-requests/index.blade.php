@extends('layouts.app')
@section('title', 'Emergency Blood Requests')
@section('content')
<div class="card">
  <h1>Emergency Blood Requests</h1>
  <p class="muted">Send an urgent email to every patient with a matching blood type who's currently eligible to donate (hasn't donated in the last 3 months).</p>
  @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  <p><a href="{{ route('hospital.blood-requests.create') }}" class="btn">Send Emergency Request</a></p>
</div>

<div class="card">
  @if ($bloodRequests->isEmpty())
    <p class="muted">You haven't sent an emergency blood request yet.</p>
  @else
    <table>
      <thead><tr><th>Blood Type</th><th>Message</th><th>Recipients</th><th>Sent</th></tr></thead>
      <tbody>
        @foreach ($bloodRequests as $req)
          <tr>
            <td><span class="badge text-bg-danger">{{ $req->blood_group }}</span></td>
            <td class="muted">{{ $req->message ?? '—' }}</td>
            <td>{{ $req->recipient_count }} patient(s)</td>
            <td class="muted">{{ $req->created_at->format('D, M j Y g:i A') }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>
@endsection
