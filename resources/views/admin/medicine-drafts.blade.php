@extends('layouts.app')
@section('title', 'Medicine Drafts')
@section('content')
<div class="card">
  <h1>Medicine Information Drafts</h1>
  <p class="muted">
    Information downloaded from openFDA for medicines the health chat cannot explain yet. Patients never see a draft —
    it reaches them only when you approve it here. The source is a <strong>US drug label</strong>, so read it, edit the
    wording to suit patients here, and reject anything that does not fit.
  </p>

  <div class="row row-cols-2 row-cols-lg-4 g-3 mb-1">
    @foreach (['pending' => 'Waiting for review', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $caption)
      <div class="col">
        <div class="card stat-card-sm">
          <div class="stat-card-sm-icon {{ $key === 'pending' ? 'bg-warning-subtle text-warning' : ($key === 'approved' ? 'bg-success-subtle text-success' : 'icon-tint-brand') }}">
            <i class="bi {{ $key === 'pending' ? 'bi-hourglass-split' : ($key === 'approved' ? 'bi-check2-circle' : 'bi-x-circle') }}"></i>
          </div>
          <div>
            <div class="muted small">{{ $caption }}</div>
            <div class="stat-card-sm-value">{{ $counts[$key] ?? 0 }}</div>
          </div>
        </div>
      </div>
    @endforeach
    <div class="col">
      <div class="card stat-card-sm">
        <div class="stat-card-sm-icon icon-tint-brand"><i class="bi bi-capsule"></i></div>
        <div>
          <div class="muted small">Medicines still needing info</div>
          <div class="stat-card-sm-value">{{ $needingInfo }}</div>
        </div>
      </div>
    </div>
  </div>

  <p class="mb-0">
    @foreach (['pending' => 'To review', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label)
      <a href="{{ route('admin.medicine-drafts', ['status' => $key]) }}"
         class="btn {{ $status === $key ? '' : 'btn-secondary' }}" style="padding:0.3rem 0.8rem;">{{ $label }}</a>
    @endforeach
  </p>
</div>

@forelse ($drafts as $draft)
  <div class="card">
    <h2>{{ ucfirst($draft->generic_name) }}</h2>
    <p class="muted">
      Fetched from {{ $draft->source }}@if ($draft->queried_as !== $draft->generic_name) , searched as <strong>{{ $draft->queried_as }}</strong>@endif
      &middot; {{ $draft->created_at->format('M j, Y') }}
      @if ($draft->sourceUrl())
        &middot; <a href="{{ $draft->sourceUrl() }}" target="_blank" rel="noopener">view the original label</a>
      @endif
    </p>

    @if ($draft->status === 'pending')
      <form method="POST" action="{{ route('admin.medicine-drafts.approve', $draft) }}" data-confirm="Publish this to patients?">
        @csrf
        <div class="field">
          <label for="uses-{{ $draft->draft_id }}">What it is used for (shown to patients)</label>
          <textarea id="uses-{{ $draft->draft_id }}" name="uses_en" rows="3" required>{{ old('uses_en', $draft->uses_en) }}</textarea>
        </div>
        <div class="field">
          <label for="cautions-{{ $draft->draft_id }}">Cautions — one per line</label>
          <textarea id="cautions-{{ $draft->draft_id }}" name="cautions_en" rows="4">{{ old('cautions_en', implode("\n", $draft->cautions_en ?? [])) }}</textarea>
        </div>
        <div class="field">
          <label>
            <input type="checkbox" name="is_prescription_only" value="1" @checked($draft->suggested_prescription_only)>
            Prescription-only (patients are told not to self-medicate with it)
          </label>
        </div>
        <button type="submit" class="btn">Approve &amp; publish</button>
      </form>
      <form method="POST" action="{{ route('admin.medicine-drafts.reject', $draft) }}" data-confirm="Reject this draft?" style="margin-top:0.5rem;">
        @csrf
        <button type="submit" class="btn btn-secondary">Reject</button>
      </form>
    @else
      <p><strong>Uses:</strong> {{ $draft->uses_en }}</p>
      @if ($draft->cautions_en)
        <ul>@foreach ($draft->cautions_en as $caution)<li>{{ $caution }}</li>@endforeach</ul>
      @endif
      <p class="muted">{{ ucfirst($draft->status) }} {{ $draft->reviewed_at?->format('M j, Y') }}</p>
    @endif
  </div>
@empty
  <div class="card">
    <p class="muted">No {{ $status }} drafts.</p>
  </div>
@endforelse

<div class="card">
  <h2>Fetch more</h2>
  <p class="muted">Run this when the machine has internet. It never runs during a patient's chat, so the assistant keeps working offline:</p>
  <pre style="white-space:pre-wrap;margin:0;">php artisan medicines:fetch-info
php artisan medicines:fetch-info --generic=metformin</pre>
</div>

<div class="mt-2">{{ $drafts->links() }}</div>
@endsection
