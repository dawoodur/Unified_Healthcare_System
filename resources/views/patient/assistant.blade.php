@extends('layouts.app')
@section('title', 'Symptom & Help Assistant')
@section('content')
<div class="card">
  <h1>Symptom &amp; Help Assistant</h1>
  <p class="muted">Rule-based keyword matching, not a diagnosis — describe what you're feeling to get a specialty suggestion, or ask a question to find a relevant help article. This never replaces actually seeing a doctor.</p>
  <form method="GET" action="{{ route('patient.assistant') }}" style="display:flex;gap:0.5rem;flex-wrap:wrap;">
    <input type="text" name="q" value="{{ $query }}" placeholder="e.g. I have a fever and sore throat" style="flex:1;min-width:240px;">
    <button type="submit" class="btn">Ask</button>
    @if ($query !== '')
      <a href="{{ route('patient.assistant') }}" class="btn btn-secondary">Clear</a>
    @endif
  </form>
</div>

@if ($query !== '')
  <div class="card">
    <h2>Suggested specialty</h2>
    @if ($specialtySuggestions->isEmpty())
      <p class="muted">Nothing matched that description — try naming a specific symptom (e.g. "chest pain", "rash", "back pain"), or <a href="{{ route('patient.doctors') }}">browse all doctors</a>.</p>
    @else
      @foreach ($specialtySuggestions as $suggestion)
        <div class="card" style="margin-bottom:0.7rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.5rem;">
          <div>
            <strong>{{ $suggestion->specialty->specialty_name }}</strong>
            <div class="muted" style="font-size:0.85rem;">Matched: {{ $suggestion->matchedKeywords->implode(', ') }}</div>
          </div>
          <a href="{{ route('patient.doctors', ['specialty_id' => $suggestion->specialty->specialty_id]) }}" class="btn" style="padding:0.35rem 0.8rem;">Find a {{ $suggestion->specialty->specialty_name }} doctor</a>
        </div>
      @endforeach
    @endif
  </div>

  <div class="card">
    <h2>Related help articles</h2>
    @if ($faqMatches->isEmpty())
      <p class="muted">No help article matched that — browse everything below.</p>
    @else
      @foreach ($faqMatches as $faq)
        <div style="margin-bottom:0.9rem;">
          <strong>{{ $faq->question }}</strong>
          <p class="muted" style="margin-top:0.2rem;">{{ $faq->answer }}</p>
        </div>
      @endforeach
    @endif
  </div>
@endif

<div class="card">
  <h2>Browse all help articles</h2>
  @foreach ($faqsByCategory as $category => $faqs)
    <h3 style="text-transform:capitalize;margin-top:1rem;">{{ $category }}</h3>
    @foreach ($faqs as $faq)
      <div style="margin-bottom:0.8rem;">
        <strong>{{ $faq->question }}</strong>
        <p class="muted" style="margin-top:0.2rem;">{{ $faq->answer }}</p>
      </div>
    @endforeach
  @endforeach
</div>
@endsection
