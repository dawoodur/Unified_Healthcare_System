@extends('layouts.app')
@section('title', $editing ? 'Update Batch' : 'Add a Batch')
@section('content')
<div class="pharmacy-page">
  @include('pharmacy.partials.quick-nav')
  @include('pharmacy.partials.page-header', [
    'icon' => 'bi-box-seam',
    'title' => $editing ? 'Update batch' : 'Add or update a batch',
    'subtitle' => 'Submitting the same medicine and batch number updates that batch instead of creating a duplicate — to add a new one, use a different batch number.',
  ])

  <div class="pharmacy-card" style="max-width:560px;">
    <p class="pharmacy-notice is-info">
      <i class="bi bi-info-circle" aria-hidden="true"></i>
      Can't find the medicine you want?
      <a href="{{ route('pharmacy.inventory.medicines.create') }}">Add it to the catalogue first</a>.
    </p>

    <form method="POST" action="{{ route('pharmacy.inventory.store') }}" data-confirm="Save this batch?">
      @csrf
      <div class="field">
        <label for="medicine_master_id">Medicine</label>
        <select id="medicine_master_id" name="medicine_master_id" required>
          <option value="">Select a medicine...</option>
          @foreach ($medicines as $medicine)
            <option value="{{ $medicine->medicine_master_id }}" @selected($editing && $editing->medicine_master_id === $medicine->medicine_master_id)>#m{{ $medicine->medicine_master_id }} &middot; {{ $medicine->generic_name }}@if($medicine->brand_name) ({{ $medicine->brand_name }})@endif</option>
          @endforeach
        </select>
      </div>
      <div class="field">
        <label for="batch_no">Batch number</label>
        <input type="text" id="batch_no" name="batch_no" maxlength="60" value="{{ old('batch_no', $editing->batch_no ?? '') }}" required>
      </div>
      <div class="grid grid-2">
        <div class="field">
          <label for="expiry_date">Expiry date</label>
          <input type="date" id="expiry_date" name="expiry_date" value="{{ old('expiry_date', $editing?->expiry_date?->format('Y-m-d')) }}" required>
        </div>
        <div class="field">
          <label for="unit_price">Price per unit (BDT)</label>
          <input type="number" id="unit_price" name="unit_price" min="0" step="0.01" value="{{ old('unit_price', $editing->unit_price ?? '') }}" required>
        </div>
      </div>
      <div class="field">
        <label for="quantity_available">Quantity available</label>
        <input type="number" id="quantity_available" name="quantity_available" min="0" value="{{ old('quantity_available', $editing->quantity_available ?? '') }}" required>
      </div>
      <button type="submit" class="pharmacy-btn is-primary">Save batch</button>
      <a class="pharmacy-btn is-ghost" href="{{ route('pharmacy.inventory') }}">Cancel</a>
    </form>
  </div>
</div>
@endsection
