@extends('layouts.app')
@section('title', 'Rate')
@section('content')
@php
  // The target model is a different class depending on $type (Doctor,
  // Hospital, Pharmacy, or DeliveryAgent) — each one names its "what do I
  // call this" field differently (full_name vs hospital_name vs
  // pharmacy_name), so this just picks whichever one actually exists.
  $targetName = $target->full_name ?? $target->hospital_name ?? $target->pharmacy_name ?? 'this';
  $typeLabels = [
      'doctor' => __('patient.reviews.type_doctor'),
      'hospital' => __('patient.reviews.type_hospital'),
      'pharmacy' => __('patient.reviews.type_pharmacy'),
      'delivery' => __('patient.reviews.type_delivery'),
  ];
  $targetId = $target->getKey();
@endphp
<div class="card" style="max-width:480px;margin:0 auto;">
  <h1>{{ __('patient.reviews.rate_title', ['name' => $type === 'doctor' ? 'Dr. ' . $targetName : $targetName]) }}</h1>
  <p class="muted">{{ __('patient.reviews.rate_desc', ['type' => $typeLabels[$type]]) }}</p>

  <form method="POST" action="{{ route('patient.reviews.rate.store') }}" novalidate>
    @csrf
    <input type="hidden" name="type" value="{{ $type }}">
    <input type="hidden" name="id" value="{{ $targetId }}">

    <div class="field">
      <label for="rating">{{ __('patient.reviews.rating_label') }}</label>
      <select id="rating" name="rating" required>
        <option value="">{{ __('patient.reviews.select_placeholder') }}</option>
        @for ($i = 5; $i >= 1; $i--)
          <option value="{{ $i }}" @selected(old('rating', $existing->rating ?? null) == $i)>{{ $i }} — {{ str_repeat('★', $i) }}{{ str_repeat('☆', 5 - $i) }}</option>
        @endfor
      </select>
    </div>

    <div class="field">
      <label for="comment">{{ __('patient.reviews.comment_label') }}</label>
      <textarea id="comment" name="comment" rows="4" maxlength="500">{{ old('comment', $existing->comment ?? '') }}</textarea>
    </div>

    <button type="submit" class="btn btn-block">{{ $existing ? __('patient.reviews.update_rating') : __('patient.reviews.submit_rating') }}</button>
  </form>
  <p class="muted"><a href="{{ route('patient.reviews') }}">{{ __('patient.reviews.back_to_reviews') }}</a></p>
</div>
@endsection
