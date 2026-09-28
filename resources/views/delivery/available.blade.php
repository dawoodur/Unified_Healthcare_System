@extends('layouts.app')
@section('title', 'Available Orders')
@section('content')
<div class="card">
  <h1>Available Orders</h1>
  <p class="muted">Orders a pharmacy has prepared and is waiting for a delivery agent to pick up.</p>
</div>

<div class="card">
  @if ($orders->isEmpty())
    <p class="muted">No orders are waiting for pickup right now.</p>
  @else
    <table>
      <thead><tr><th>Order</th><th>Pharmacy</th><th>Deliver to</th><th>Total</th><th></th></tr></thead>
      <tbody>
        @foreach ($orders as $order)
          <tr>
            <td>#{{ $order->order_id }}</td>
            <td>{{ $order->pharmacy->pharmacy_name }} <span class="muted">({{ $order->pharmacy->address ?? '—' }})</span></td>
            <td class="muted">{{ $order->delivery_address }}</td>
            <td>BDT {{ number_format($order->total_amount, 2) }}</td>
            <td>
              <form method="POST" action="{{ route('delivery.orders.accept', $order) }}" data-confirm="Accept order #{{ $order->order_id }} for delivery?">
                @csrf
                <button type="submit" class="btn" style="padding:0.3rem 0.7rem;">Accept</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>
@endsection
