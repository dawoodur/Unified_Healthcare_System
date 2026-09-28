@extends('layouts.app')
@section('title', 'Send Emergency Blood Request')
@section('content')
<div class="hospital-page">
  @include('hospital.partials.quick-nav')
  @include('hospital.partials.page-header', [
    'icon' => 'bi-droplet-fill',
    'title' => 'Send an emergency blood request',
    'subtitle' => "Emails every patient with the selected blood type who is currently eligible to donate (hasn't donated in the last 3 months).",
  ])

  <div class="hospital-card" style="max-width:560px;">
    <form method="POST" action="{{ route('hospital.blood-requests.store') }}" data-confirm="Send this emergency request by email now? This cannot be undone.">
      @csrf
      <div class="field">
        <label for="blood_group">Blood type needed</label>
        <select id="blood_group" name="blood_group" required>
          <option value="">Select...</option>
          @foreach ($eligibleCounts as $group => $count)
            <option value="{{ $group }}" @selected(old('blood_group') === $group)>{{ $group }} ({{ $count }} eligible donor(s))</option>
          @endforeach
        </select>
      </div>
      <div class="field">
        <label for="message">Message (optional)</label>
        <textarea id="message" name="message" rows="4" maxlength="500" placeholder="Any details about the urgency...">{{ old('message') }}</textarea>
      </div>
      <button type="submit" class="hospital-btn is-primary">Send now</button>
      <a class="hospital-btn is-ghost" href="{{ route('hospital.blood-requests') }}">Cancel</a>
    </form>
  </div>
</div>
@endsection
