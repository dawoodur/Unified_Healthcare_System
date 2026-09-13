@extends('layouts.app')
@section('title', 'Log a Donation')
@section('content')
<div class="card" style="max-width:480px;margin:0 auto;">
  <h1>{{ __('patient.blood.log_title') }}</h1>
  <p class="muted">{{ __('patient.blood.log_intro') }}</p>

  <form method="POST" action="{{ route('patient.blood-donations.store') }}" novalidate>
    @csrf
    <div class="field">
      <label for="donated_at">{{ __('patient.blood.date_donated') }}</label>
      <input type="date" id="donated_at" name="donated_at" value="{{ old('donated_at', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
    </div>
    <div class="field">
      <label for="hospital_id">{{ __('patient.blood.hospital_optional') }}</label>
      <select id="hospital_id" name="hospital_id">
        <option value="">{{ __('patient.blood.not_specified') }}</option>
        @foreach ($hospitals as $hospital)
          <option value="{{ $hospital->hospital_id }}" @selected(old('hospital_id') == $hospital->hospital_id)>{{ $hospital->hospital_name }}</option>
        @endforeach
      </select>
    </div>
    <button type="submit" class="btn btn-block">{{ __('patient.blood.submit') }}</button>
  </form>
  <p class="muted"><a href="{{ route('patient.blood-donations') }}">{{ __('patient.blood.back') }}</a></p>
</div>
@endsection
