{{-- Used by my-appointments.blade.php (target=doctor / target=hospital) and
     my-orders.blade.php (target=pharmacy). Expects: $target, $label,
     $existingReview (a Review or null), and exactly one of
     $appointmentId / $orderId. --}}
@if ($existingReview)
  <div class="muted" style="font-size:0.85rem;">
    <span style="color:#f5b301;">{{ str_repeat('★', $existingReview->rating) }}{{ str_repeat('☆', 5 - $existingReview->rating) }}</span>
    your review of {{ $label }}
    @if ($existingReview->comment)
      &mdash; &ldquo;{{ $existingReview->comment }}&rdquo;
    @endif
  </div>
@else
  <details style="margin-top:0.4rem;">
    <summary style="cursor:pointer;color:var(--color-primary);font-size:0.85rem;">Review {{ $label }}</summary>
    <form method="POST" action="{{ route('patient.reviews.store') }}" style="margin-top:0.5rem;max-width:360px;">
      @csrf
      <input type="hidden" name="target" value="{{ $target }}">
      @if ($appointmentId ?? null)
        <input type="hidden" name="appointment_id" value="{{ $appointmentId }}">
      @endif
      @if ($orderId ?? null)
        <input type="hidden" name="order_id" value="{{ $orderId }}">
      @endif
      <div class="star-rating">
        @for ($i = 5; $i >= 1; $i--)
          @php $starId = 'star-' . $target . '-' . ($appointmentId ?? $orderId) . '-' . $i; @endphp
          <input type="radio" id="{{ $starId }}" name="rating" value="{{ $i }}" required>
          <label for="{{ $starId }}" title="{{ $i }} star">★</label>
        @endfor
      </div>
      <textarea name="comment" rows="2" maxlength="500" placeholder="Optional comment" style="margin-top:0.4rem;"></textarea>
      <button type="submit" class="btn" style="padding:0.3rem 0.7rem;margin-top:0.4rem;">Submit review</button>
    </form>
  </details>
@endif
