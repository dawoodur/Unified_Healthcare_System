<div class="card" style="margin-bottom:0.9rem;">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.5rem;">
    <div>
      <strong>Order #{{ $order->order_id }}</strong>
      <span class="muted">&middot; {{ $order->patient->full_name }} &middot; {{ $order->created_at->format('D, M j Y') }}</span>
    </div>
    <span class="badge {{ $order->statusBadgeClass() }}">{{ $order->statusLabel() }}</span>
  </div>

  <table style="margin-top:0.75rem;">
    <thead><tr><th>Medicine</th><th>Quantity</th><th>Price</th></tr></thead>
    <tbody>
      @foreach ($order->items as $item)
        <tr>
          <td>{{ $item->medicine->generic_name }} <span class="muted">#m{{ $item->medicine_master_id }}</span></td>
          <td>{{ $item->quantity }}</td>
          <td>BDT {{ number_format($item->unit_price_snapshot, 2) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <p class="muted" style="margin-top:0.5rem;">Deliver to: {{ $order->delivery_address }}</p>
  @if ($order->deliveryAgent)
    <p class="muted">Delivery agent: {{ $order->deliveryAgent->full_name }}</p>
  @endif

  <div style="display:flex;justify-content:space-between;align-items:center;margin-top:0.5rem;">
    <strong>Total: BDT {{ number_format($order->total_amount, 2) }}</strong>
    <div>
      @if ($order->status === 'placed')
        <form method="POST" action="{{ route('pharmacy.orders.accept', $order) }}" style="display:inline;" data-confirm="Accept order #{{ $order->order_id }}?">
          @csrf
          <button type="submit" class="btn" style="padding:0.3rem 0.7rem;">Accept</button>
        </form>
      @endif
      @if (in_array($order->status, ['placed', 'accepted']))
        <form method="POST" action="{{ route('pharmacy.orders.cancel', $order) }}" style="display:inline;" data-confirm="Cancel order #{{ $order->order_id }}? Reserved stock will be restored.">
          @csrf
          <button type="submit" class="btn btn-danger" style="padding:0.3rem 0.7rem;">Cancel</button>
        </form>
      @endif
    </div>
  </div>
</div>
