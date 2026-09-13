{{--
  Reusable horizontal bar chart. Pass $data as a Collection/array of
  ['label' => string, 'value' => number, 'formatted' => string] rows —
  bar width is each row's value as a percentage of the largest value in
  the set.
--}}
@php $max = max(1, collect($data)->max('value')); @endphp
<div class="bar-chart">
  @forelse ($data as $row)
    <div class="bar-chart-row">
      <div class="bar-chart-labels">
        <span class="bar-chart-label">{{ $row['label'] }}</span>
        <span class="bar-chart-value">{{ $row['formatted'] }}</span>
      </div>
      <div class="bar-chart-track">
        <div class="bar-chart-fill" style="width: {{ $max > 0 ? ($row['value'] / $max * 100) : 0 }}%;"></div>
      </div>
    </div>
  @empty
    <p class="muted">No data yet.</p>
  @endforelse
</div>
