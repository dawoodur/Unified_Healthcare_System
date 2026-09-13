{{--
  Reusable star-rating display. Pass $rating (0-5, can be a decimal like
  3.5 for an average) and optionally $label (text shown after the stars,
  e.g. "(4/5)" or "(3.5/5 · 12 ratings)"). See the .star-rating CSS rules
  in public/css/style.css for how the partial-star fill effect works.
--}}
@php
  $pct = max(0, min(100, ($rating / 5) * 100));
@endphp
<span class="star-rating" aria-label="{{ $rating }} out of 5 stars">
  <span class="star-rating-track">★★★★★</span>
  <span class="star-rating-fill" style="width: {{ $pct }}%;">★★★★★</span>
</span>
@isset($label)
  <span class="muted">{{ $label }}</span>
@endisset
