<div class="card" style="margin-bottom:0.9rem;">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.5rem;">
    <div>
      <strong>Order #{{ $order->order_id }}</strong>
      <span class="muted">&middot; {{ $order->pharmacy->pharmacy_name }} ({{ $order->pharmacy->address ?? 'address not listed' }}) &middot; {{ $order->patient->full_name }}</span>
    </div>
    <span class="badge {{ $order->statusBadgeClass() }}">{{ $order->statusLabel() }}</span>
  </div>
  <p class="muted" style="margin-top:0.5rem;">Deliver to: {{ $order->delivery_address }}</p>
  <p><strong>Total: BDT {{ number_format($order->total_amount, 2) }}</strong></p>

  @if ($order->status === 'out_for_delivery')
    <div style="display:flex;gap:0.6rem;align-items:center;flex-wrap:wrap;margin-top:0.5rem;">
      <form method="POST" action="{{ route('delivery.orders.request-otp', $order) }}" data-confirm="Email a confirmation code to {{ $order->patient->full_name }}?">
        @csrf
        <button type="submit" class="btn btn-secondary" style="padding:0.35rem 0.7rem;">Request Code</button>
      </form>
      <form method="POST" action="{{ route('delivery.orders.confirm', $order) }}" style="display:flex;gap:0.4rem;align-items:center;" data-confirm="Confirm this delivery as complete?">
        @csrf
        <input type="text" name="otp_code" maxlength="6" placeholder="6-digit code" style="width:130px;" required>
        <button type="submit" class="btn" style="padding:0.35rem 0.7rem;">Confirm Delivery</button>
      </form>
    </div>

    {{-- Location sharing. Driven by public/js/delivery-share-location.js; the
         CSRF token rides on a data attribute because this layout has no
         csrf-token <meta> tag for scripts to read. --}}
    <div class="delivery-share" data-delivery-share
         data-post-url="{{ route('delivery.orders.location', $order) }}"
         data-csrf="{{ csrf_token() }}"
         data-origin-lat="{{ $order->pharmacy->latitude }}"
         data-origin-lng="{{ $order->pharmacy->longitude }}"
         data-dest-lat="{{ $order->destination_latitude }}"
         data-dest-lng="{{ $order->destination_longitude }}"
         @if (session('simulate_order') == $order->order_id) data-auto-simulate="1" @endif>
      <label class="delivery-share-toggle">
        <input type="checkbox" data-share-toggle>
        <span>Share my location with {{ $order->patient->full_name }}</span>
      </label>
      <button type="button" class="btn btn-secondary delivery-share-simulate" data-simulate>Simulate route</button>
      <span class="muted delivery-share-status" data-share-status>Not sharing.</span>
      <small class="muted delivery-share-hint">
        The patient only sees your position while this order is out for delivery.
        A run you have just claimed starts a simulated route by itself, labelled as
        simulated; switch on sharing above to send your real position instead.
      </small>
    </div>
  @endif
</div>
