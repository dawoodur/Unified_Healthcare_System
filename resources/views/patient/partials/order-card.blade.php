@php
  $status = $order->status;
  $progressMap = [
    'placed' => 1,
    'accepted' => 2,
    'out_for_delivery' => 3,
    'delivered' => 4,
  ];
  $progressStep = $progressMap[$status] ?? 0;
@endphp

<article class="patient-order-card patient-order-card-{{ $status }}">
  <header class="patient-order-card-header">
    <div class="patient-order-card-id">
      <span class="patient-order-card-icon"><i class="bi bi-bag-check" aria-hidden="true"></i></span>
      <div>
        <strong>{{ __('patient.orders.order_number', ['id' => $order->order_id]) }}</strong>
        <small>{{ $order->created_at->format('D, M j Y · g:i A') }}</small>
      </div>
    </div>

    <span class="patient-order-status patient-order-status-{{ $status }}">
      @if ($status === 'delivered')
        <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
      @elseif ($status === 'cancelled')
        <i class="bi bi-x-circle-fill" aria-hidden="true"></i>
      @elseif ($status === 'out_for_delivery')
        <i class="bi bi-truck" aria-hidden="true"></i>
      @else
        <i class="bi bi-clock-fill" aria-hidden="true"></i>
      @endif
      {{ $order->statusLabel() }}
    </span>
  </header>

  <div class="patient-order-card-body">
    <div class="patient-order-pharmacy">
      <span><i class="bi bi-shop" aria-hidden="true"></i></span>
      <div>
        <small>{{ __('patient.orders.pharmacy_label') }}</small>
        <strong>{{ $order->pharmacy->pharmacy_name }}</strong>
        <p><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ $order->pharmacy->address ?? __('patient.cart.address_not_listed') }}</p>
      </div>
    </div>

    <div class="patient-order-meta">
      <div>
        <span><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ __('patient.orders.delivery_address_label') }}</span>
        <strong>{{ $order->delivery_address }}</strong>
      </div>

      <div>
        <span><i class="bi bi-person-badge" aria-hidden="true"></i>{{ __('patient.orders.delivery_agent_label') }}</span>
        <strong>{{ $order->deliveryAgent?->full_name ?? __('patient.orders.not_assigned') }}</strong>
      </div>

      <div>
        <span><i class="bi bi-wallet2" aria-hidden="true"></i>{{ __('patient.orders.payment_label') }}</span>
        <strong>
          @if ($order->payment)
            {{ $order->payment->paymentMethod?->method_name ?? '—' }}
            · {{ __('statuses.payment.' . $order->payment->status) }}
          @else
            —
          @endif
        </strong>
      </div>
    </div>
  </div>

  @if ($status !== 'cancelled')
    <div class="patient-order-progress" aria-label="{{ __('patient.orders.delivery_progress') }}">
      @foreach ([
        ['placed', __('patient.orders.step_placed'), 'bi-receipt'],
        ['accepted', __('patient.orders.step_accepted'), 'bi-shop-window'],
        ['out_for_delivery', __('patient.orders.step_delivery'), 'bi-truck'],
        ['delivered', __('patient.orders.step_delivered'), 'bi-check2-circle'],
      ] as $index => $step)
        @php $stepNumber = $index + 1; @endphp
        <div class="patient-order-progress-step {{ $progressStep >= $stepNumber ? 'done' : '' }} {{ $progressStep === $stepNumber ? 'current' : '' }}">
          <span><i class="bi {{ $step[2] }}" aria-hidden="true"></i></span>
          <small>{{ $step[1] }}</small>
        </div>
      @endforeach
    </div>
  @endif

  <div class="patient-order-items">
    <div class="patient-order-items-heading">
      <strong>{{ __('patient.orders.medicines_title') }}</strong>
      <span>{{ trans_choice('patient.orders.item_count', $order->items->sum('quantity'), ['count' => $order->items->sum('quantity')]) }}</span>
    </div>

    @foreach ($order->items as $item)
      <div class="patient-order-item-row">
        <span class="patient-order-medicine-icon"><i class="bi bi-capsule-pill" aria-hidden="true"></i></span>
        <div>
          <strong>{{ $item->medicine->generic_name }}</strong>
          @if ($item->medicine->brand_name)
            <small>{{ $item->medicine->brand_name }}</small>
          @endif
        </div>
        <span>{{ __('patient.orders.quantity_short', ['count' => $item->quantity]) }}</span>
        <b>BDT {{ number_format($item->unit_price_snapshot, 2) }}</b>
        <em>BDT {{ number_format($item->lineTotal(), 2) }}</em>
      </div>
    @endforeach
  </div>

  <footer class="patient-order-card-footer">
    <div class="patient-order-totals">
      @if ((float) $order->discount_amount > 0)
        <span>
          {{ __('patient.orders.subtotal_label') }}
          <strong>BDT {{ number_format($order->subtotal, 2) }}</strong>
        </span>
        <span class="patient-order-discount">
          {{ __('patient.orders.discount_label') }}
          <strong>− BDT {{ number_format($order->discount_amount, 2) }}</strong>
        </span>
      @endif

      @if ((int) $order->reward_points_used > 0)
        <span>
          {{ __('patient.orders.points_used_label') }}
          <strong>{{ $order->reward_points_used }}</strong>
        </span>
      @endif
    </div>

    <div class="patient-order-grand-total">
      <span>{{ __('patient.orders.total_label') }}</span>
      <strong>BDT {{ number_format($order->total_amount, 2) }}</strong>
    </div>
  </footer>
</article>
