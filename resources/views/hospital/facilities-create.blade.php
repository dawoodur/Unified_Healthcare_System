@extends('layouts.app')
@section('title', ($offering ? 'Update' : 'Add') . ' ' . $facilityType->name)
@section('content')
<div class="hospital-page">
  @include('hospital.partials.quick-nav')
  @include('hospital.partials.page-header', [
    'icon' => $facilityType->is_occupancy ? 'bi-hospital' : 'bi-clipboard2-pulse',
    'title' => ($offering ? 'Update ' : 'Add ') . $facilityType->name,
    'subtitle' => $facilityType->category->category_name . ' · priced ' . $facilityType->unit_label,
  ])

  <div class="hospital-card" style="max-width:520px;">
    <form method="POST" action="{{ route('hospital.facilities.store') }}" data-confirm="{{ $offering ? 'Update this facility listing?' : 'Add this facility listing?' }}">
      @csrf
      <input type="hidden" name="facility_type_id" value="{{ $facilityType->facility_type_id }}">
      <div class="field">
        <label for="price">Price (BDT)</label>
        <input type="number" id="price" name="price" min="0" step="0.01" value="{{ old('price', $offering->price ?? '') }}" required>
      </div>
      <div class="field">
        <label for="daily_capacity">{{ $facilityType->is_occupancy ? 'Total beds' : 'Daily quota' }}</label>
        <input type="number" id="daily_capacity" name="daily_capacity" min="1" step="1" value="{{ old('daily_capacity', $offering->daily_capacity ?? 10) }}" required>
      </div>
      <button type="submit" class="hospital-btn is-primary">{{ $offering ? 'Update' : 'Add' }}</button>
      <a class="hospital-btn is-ghost" href="{{ route('hospital.facilities') }}">Cancel</a>
    </form>
  </div>
</div>
@endsection
