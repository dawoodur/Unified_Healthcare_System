{{--
  Plain CSS donut chart — no charting library, same "hand-rolled, no
  external dependency" spirit as partials/bar-chart.blade.php. A single
  div's background is a conic-gradient built from the segments below,
  with a plain circle the same color as the card punched out of the
  middle (via ::after in CSS) to turn the pie into a donut.

  Expects: $segments — an array of ['label' => ..., 'value' => int, 'color' => '#hex'].
  If every value is 0, shows an empty-state ring instead of dividing by zero.
  Optional: $totalLabel — the word shown under the center count (defaults to
  the doctor dashboard's "patients", since that was this partial's first caller).
--}}
@php
  $totalLabel = $totalLabel ?? __('dashboard.doctor.donut_total_label');
  $total = collect($segments)->sum('value');
  $gradientStops = [];
  $cursor = 0;
  foreach ($segments as $segment) {
      $slice = $total > 0 ? ($segment['value'] / $total) * 360 : 0;
      $gradientStops[] = "{$segment['color']} {$cursor}deg " . ($cursor + $slice) . 'deg';
      $cursor += $slice;
  }
  $gradientCss = $total > 0 ? implode(', ', $gradientStops) : 'var(--bs-border-color) 0deg 360deg';
@endphp
<div class="donut-chart-wrap">
  <div class="donut-chart" style="background: conic-gradient({{ $gradientCss }});">
    <div class="donut-chart-hole">
      <strong>{{ $total }}</strong>
      <span class="muted">{{ $totalLabel }}</span>
    </div>
  </div>
  <ul class="donut-legend">
    @foreach ($segments as $segment)
      <li>
        <span class="donut-legend-dot" style="background: {{ $segment['color'] }};"></span>
        {{ $segment['label'] }}
        <strong>{{ $total > 0 ? round(($segment['value'] / $total) * 100) : 0 }}%</strong>
      </li>
    @endforeach
  </ul>
</div>
