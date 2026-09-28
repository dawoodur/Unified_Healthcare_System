@extends('layouts.app')
@section('title', 'Orders')
@section('content')
<div class="card">
  <h1>Orders</h1>
  <p class="muted">Every order patients have placed with you.</p>
</div>

<div class="card">
  <h2>Pending</h2>
  @if ($pending->isEmpty())
    <p class="muted">No pending orders.</p>
  @else
    @foreach ($pending as $order)
      @include('pharmacy.partials.order-card', ['order' => $order])
    @endforeach
  @endif
</div>

<div class="card">
  <h2>Completed</h2>
  @if ($completed->isEmpty())
    <p class="muted">No completed orders yet.</p>
  @else
    @foreach ($completed as $order)
      @include('pharmacy.partials.order-card', ['order' => $order])
    @endforeach
  @endif
</div>
@endsection
