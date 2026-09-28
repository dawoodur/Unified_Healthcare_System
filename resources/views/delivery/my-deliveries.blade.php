@extends('layouts.app')
@section('title', 'My Deliveries')
@section('content')
<div class="card">
  <h1>My Deliveries</h1>
  <p class="muted">Orders assigned to you. To complete a delivery: click "Request Code" (emails a confirmation code to the patient), ask the patient for that code once you've handed over the order, then enter it below to confirm.</p>
</div>

<div class="card">
  <h2>Pending</h2>
  @if ($pending->isEmpty())
    <p class="muted">No pending deliveries. <a href="{{ route('delivery.available') }}">See available orders</a>.</p>
  @else
    @foreach ($pending as $order)
      @include('delivery.partials.delivery-card', ['order' => $order])
    @endforeach
  @endif
</div>

<div class="card">
  <h2>Completed</h2>
  @if ($completed->isEmpty())
    <p class="muted">No completed deliveries yet.</p>
  @else
    @foreach ($completed as $order)
      @include('delivery.partials.delivery-card', ['order' => $order])
    @endforeach
  @endif
</div>

@push('scripts')
  {{-- Drives every [data-delivery-share] panel rendered by delivery-card above. --}}
  <script src="{{ asset('js/delivery-share-location.js') }}?v={{ filemtime(public_path('js/delivery-share-location.js')) }}"></script>
@endpush
@endsection
