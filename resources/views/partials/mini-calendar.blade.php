{{--
  Read-only "this month" calendar — distinct from the interactive
  booking-calendar.js widget (that one lets a PATIENT click a date to
  book; this one just shows the current month with today highlighted and
  a dot under any day that has at least one real appointment). No JS at
  all — plain server-rendered PHP date math, since there's no date
  picking to do here.

  Expects: $busyDates — a collection/array of 'Y-m-d' strings.
--}}
@php
  $monthStart = now()->startOfMonth();
  $monthEnd = now()->endOfMonth();
  $todayStr = now()->toDateString();
  $busyDates = collect($busyDates ?? []);

  // Leading blanks so day 1 lands in the correct weekday column
  // (day_of_week uses Sunday=0, matching PHP's date('w')).
  $leadingBlanks = (int) $monthStart->format('w');
@endphp
<div class="cal-widget mini-cal">
  <div class="cal-header">
    <strong>{{ $monthStart->format('F Y') }}</strong>
  </div>
  <div class="cal-grid">
    @foreach (['Su','Mo','Tu','We','Th','Fr','Sa'] as $day)
      <div class="cal-weekday">{{ $day }}</div>
    @endforeach

    @for ($i = 0; $i < $leadingBlanks; $i++)
      <div class="cal-cell cal-day cal-outside"></div>
    @endfor

    @for ($day = 1; $day <= $monthEnd->day; $day++)
      @php
        $dateStr = $monthStart->copy()->day($day)->toDateString();
        $isToday = $dateStr === $todayStr;
        $isBusy = $busyDates->contains($dateStr);
      @endphp
      <div class="cal-cell cal-day {{ $isToday ? 'cal-selected' : ($isBusy ? 'cal-available' : '') }}">
        <span>{{ $day }}</span>
        @if ($isBusy && !$isToday)
          <i class="cal-busy-dot"></i>
        @endif
      </div>
    @endfor
  </div>
</div>
