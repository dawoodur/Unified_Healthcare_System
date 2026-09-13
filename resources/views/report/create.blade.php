@extends('layouts.app')
@section('title', 'Submit a Report')
@section('content')
<div class="card">
  <h1>Submit a Report</h1>
  <p><a href="{{ route('report.index') }}">&larr; Back to my reports</a></p>

  <form method="POST" action="{{ route('report.store') }}" class="field" data-confirm="Submit this report?">
    @csrf
    <div>
      <label for="subject">Subject</label>
      <input type="text" id="subject" name="subject" maxlength="190" value="{{ old('subject') }}" required>
    </div>
    <div style="margin-top:0.75rem;">
      <label for="description">Description</label>
      <textarea id="description" name="description" rows="4" maxlength="2000" required>{{ old('description') }}</textarea>
    </div>
    <button type="submit" class="btn" style="margin-top:0.75rem;">Submit report</button>
  </form>
</div>
@endsection
