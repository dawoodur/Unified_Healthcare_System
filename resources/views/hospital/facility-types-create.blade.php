@extends('layouts.app')
@section('title', 'Add a New Facility Type')
@section('content')
<div class="hospital-page">
  @include('hospital.partials.quick-nav')
  @include('hospital.partials.page-header', [
    'icon' => 'bi-plus-square',
    'title' => 'Add a new facility type',
    'subtitle' => "Any hospital can add a type to the shared catalogue — for when the one you want to offer isn't listed yet.",
  ])

  <div class="hospital-card" style="max-width:560px;">
    <form method="POST" action="{{ route('hospital.facilities.types.store') }}" data-confirm="Add this facility type to the catalog?">
      @csrf
      <div class="field">
        <label for="category_id">Category</label>
        <select id="category_id" name="category_id" required>
          <option value="">Select a category...</option>
          @foreach ($categories as $category)
            <option value="{{ $category->category_id }}" @selected(old('category_id') == $category->category_id)>{{ $category->category_name }}</option>
          @endforeach
        </select>
      </div>
      <div class="field">
        <label for="name">Facility name</label>
        <input type="text" id="name" name="name" maxlength="150" placeholder="e.g. CT Scan" value="{{ old('name') }}" required>
      </div>
      <div class="field">
        <label for="unit_label">Priced...</label>
        <input type="text" id="unit_label" name="unit_label" maxlength="50" placeholder="e.g. per test, per day, per session" value="{{ old('unit_label') }}">
      </div>
      <div class="field">
        <label style="display:flex;align-items:center;gap:0.5rem;font-weight:normal;">
          <input type="checkbox" name="is_occupancy" value="1" @checked(old('is_occupancy')) style="width:auto;min-height:auto;">
          This is a bed/room-type facility (patient occupies it until discharged — e.g. ICU Bed, Cabin), not a same-day service (e.g. an X-Ray or blood test)
        </label>
      </div>
      <button type="submit" class="hospital-btn is-primary">Add facility type</button>
      <a class="hospital-btn is-ghost" href="{{ route('hospital.facilities') }}">Cancel</a>
    </form>
  </div>
</div>
@endsection
