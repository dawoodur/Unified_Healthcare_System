@extends('layouts.app')
@section('title', 'Reports')
@section('content')
<div class="card">
  <h1>Reports</h1>
  <p class="muted">Bug/system reports submitted by patients, doctors, hospitals, pharmacies, and delivery agents.</p>

  <form method="GET" action="{{ route('admin.reports') }}" style="display:flex;gap:0.5rem;align-items:flex-end;flex-wrap:wrap;">
    <div>
      <label for="status">Filter by status</label>
      <select id="status" name="status" onchange="this.form.submit()">
        <option value="">All statuses</option>
        @foreach (['open', 'in_review', 'resolved', 'dismissed'] as $option)
          <option value="{{ $option }}" @selected($status === $option)>{{ ucfirst(str_replace('_', ' ', $option)) }}</option>
        @endforeach
      </select>
    </div>
  </form>
</div>

<div class="card">
  @if ($reports->isEmpty())
    <p class="muted">No reports found.</p>
  @else
    @foreach ($reports as $report)
      <div class="card" style="margin-bottom:0.9rem;">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.5rem;">
          <div>
            <strong>{{ $report->subject }}</strong>
            <span class="muted">&middot; from {{ $report->reporter?->uidTag() ?? '#u' . $report->reporter_account_id }} ({{ ucfirst($report->reporter->role ?? '—') }}) &middot; {{ $report->created_at->format('D, M j Y g:i A') }}</span>
          </div>
          <span class="badge {{ ['open' => 'text-bg-warning', 'in_review' => 'text-bg-warning', 'resolved' => 'text-bg-success', 'dismissed' => 'text-bg-danger'][$report->status] ?? 'badge' }}">
            {{ ucfirst(str_replace('_', ' ', $report->status)) }}
          </span>
        </div>

        <p style="margin-top:0.5rem;">{{ $report->description }}</p>

        @if ($report->admin_response)
          <p class="muted" style="margin-top:0.5rem;"><strong>Admin response:</strong> {{ $report->admin_response }}</p>
        @endif

        <form method="POST" action="{{ route('admin.reports.respond', $report) }}" style="margin-top:0.75rem;display:flex;gap:0.5rem;align-items:flex-end;flex-wrap:wrap;">
          @csrf
          <div style="flex:1;min-width:220px;">
            <label for="admin_response_{{ $report->report_id }}">Response</label>
            <textarea id="admin_response_{{ $report->report_id }}" name="admin_response" rows="2">{{ $report->admin_response }}</textarea>
          </div>
          <div>
            <label for="status_{{ $report->report_id }}">Set status</label>
            <select id="status_{{ $report->report_id }}" name="status">
              @foreach (['in_review', 'resolved', 'dismissed'] as $option)
                <option value="{{ $option }}" @selected($report->status === $option)>{{ ucfirst(str_replace('_', ' ', $option)) }}</option>
              @endforeach
            </select>
          </div>
          <button type="submit" class="btn" style="padding:0.4rem 0.9rem;">Save</button>
        </form>
      </div>
    @endforeach
  @endif
</div>
@endsection
