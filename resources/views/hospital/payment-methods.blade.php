@extends('layouts.app')
@section('title', 'Payment Methods')
@section('content')
<div class="card">
  <h1>Payment Methods</h1>
  <p class="muted">Choose which payment methods your hospital accepts, and add your own details for each (e.g. a bKash merchant number) so patients know what to expect.</p>
</div>

<div class="card" style="max-width:560px;">
  <form method="POST" action="{{ route('hospital.payment-methods.update') }}" data-confirm="Save your accepted payment methods?">
    @csrf
    @foreach ($methods as $method)
      @php $current = $accepted->get($method->payment_method_id); @endphp
      <div class="field" style="border-bottom:1px solid var(--bs-border-color);padding-bottom:1rem;margin-bottom:1rem;">
        <label style="display:flex;align-items:center;gap:0.5rem;font-weight:600;">
          <input type="checkbox" name="accepted[]" value="{{ $method->payment_method_id }}" @checked($current) style="width:auto;min-height:auto;">
          {{ $method->method_name }}
        </label>
        <input type="text" name="account_details[{{ $method->payment_method_id }}]" placeholder="Account details (optional) — e.g. merchant number" value="{{ $current->pivot->account_details ?? '' }}" style="margin-top:0.5rem;">
      </div>
    @endforeach
    <button type="submit" class="btn">Save Payment Methods</button>
  </form>
</div>
@endsection
