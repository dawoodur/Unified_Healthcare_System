@extends('layouts.app')
@section('title', 'Add a New Medicine')
@section('content')
<div class="pharmacy-page">
  @include('pharmacy.partials.quick-nav')
  @include('pharmacy.partials.page-header', [
    'icon' => 'bi-capsule',
    'title' => 'Add a new medicine',
    'subtitle' => "Any pharmacy can add to the shared catalogue — for when the medicine you want to stock isn't in the dropdown yet.",
  ])

  <div class="pharmacy-card" style="max-width:560px;">
    <form method="POST" action="{{ route('pharmacy.inventory.medicines.store') }}" data-confirm="Add this medicine to the catalog?">
      @csrf
      <div class="field">
        <label for="generic_name">Generic name</label>
        <input type="text" id="generic_name" name="generic_name" maxlength="150" placeholder="e.g. Paracetamol" required>
      </div>
      <div class="grid grid-2">
        <div class="field">
          <label for="brand_name">Brand name</label>
          <input type="text" id="brand_name" name="brand_name" maxlength="150" placeholder="e.g. Napa">
        </div>
        <div class="field">
          <label for="form">Form</label>
          <input type="text" id="form" name="form" maxlength="60" placeholder="e.g. tablet, syrup">
        </div>
      </div>
      <div class="field">
        <label for="strength">Strength</label>
        <input type="text" id="strength" name="strength" maxlength="60" placeholder="e.g. 500mg">
      </div>
      <button type="submit" class="pharmacy-btn is-primary">Add medicine</button>
      <a class="pharmacy-btn is-ghost" href="{{ route('pharmacy.inventory') }}">Cancel</a>
    </form>
  </div>
</div>
@endsection
