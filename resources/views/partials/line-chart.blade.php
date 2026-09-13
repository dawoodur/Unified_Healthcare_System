{{--
  Reusable multi-series SVG line chart — plain inline SVG computed here in
  PHP, same "hand-rolled, no charting library" spirit as bar-chart.blade.php
  and donut-chart.blade.php.

  Expects: $series — an array of ['name' => string, 'color' => '#hex', 'data' => Collection],
  where each series' 'data' is a MonthlySeries::fill() result (same labels/count for every series).
--}}
@php
  $labels = collect($series[0]['data'] ?? [])->pluck('label');
  $pointCount = max(1, $labels->count());
  $maxValue = max(1, collect($series)->flatMap(fn ($s) => collect($s['data'])->pluck('value'))->max());

  $width = 600;
  $height = 220;
  $padLeft = 34;
  $padRight = 34;
  $padTop = 16;
  $padBottom = 34;
  $plotWidth = $width - $padLeft - $padRight;
  $plotHeight = $height - $padTop - $padBottom;

  $xFor = fn ($i) => $pointCount > 1 ? $padLeft + ($i / ($pointCount - 1)) * $plotWidth : $padLeft + $plotWidth / 2;
  $yFor = fn ($v) => $padTop + $plotHeight - ($v / $maxValue) * $plotHeight;
@endphp
<div class="line-chart-wrap">
  <svg viewBox="0 0 {{ $width }} {{ $height }}" class="line-chart-svg" preserveAspectRatio="none">
    <line x1="{{ $padLeft }}" y1="{{ $padTop + $plotHeight }}" x2="{{ $width - $padRight }}" y2="{{ $padTop + $plotHeight }}" class="line-chart-axis" />

    @foreach ($series as $s)
      @php
        $points = collect($s['data'])->values()->map(fn ($row, $i) => $xFor($i) . ',' . round($yFor($row['value']), 1))->implode(' ');
      @endphp
      <polyline points="{{ $points }}" fill="none" stroke="{{ $s['color'] }}" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" />
      @foreach (collect($s['data'])->values() as $i => $row)
        <circle cx="{{ $xFor($i) }}" cy="{{ round($yFor($row['value']), 1) }}" r="3.5" fill="{{ $s['color'] }}" />
      @endforeach
    @endforeach

    @foreach ($labels as $i => $label)
      <text x="{{ $xFor($i) }}" y="{{ $height - 10 }}" class="line-chart-tick" text-anchor="middle">{{ $label }}</text>
    @endforeach
  </svg>

  <ul class="line-chart-legend">
    @foreach ($series as $s)
      <li><span class="donut-legend-dot" style="background: {{ $s['color'] }};"></span>{{ $s['name'] }}</li>
    @endforeach
  </ul>
</div>
