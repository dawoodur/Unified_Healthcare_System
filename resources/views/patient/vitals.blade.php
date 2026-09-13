@extends('layouts.app')
@section('title', __('patient.vitals.page_title'))
@section('content')
@php
  $orderedVitals = $vitals->sortByDesc('recorded_at')->values();
  $latestVital = $orderedVitals->first();
  $bpVitals = $vitals
      ->filter(fn ($vital) => filled($vital->blood_pressure_systolic) && filled($vital->blood_pressure_diastolic))
      ->sortBy('recorded_at')
      ->take(-7)
      ->values();

  $bpChart = null;
  if ($bpVitals->count() >= 2) {
      $chartWidth = 560;
      $chartHeight = 178;
      $left = 38;
      $right = 14;
      $top = 16;
      $bottom = 30;
      $plotWidth = $chartWidth - $left - $right;
      $plotHeight = $chartHeight - $top - $bottom;
      $allBpValues = $bpVitals->flatMap(fn ($v) => [(float) $v->blood_pressure_systolic, (float) $v->blood_pressure_diastolic]);
      $minValue = floor(($allBpValues->min() - 10) / 10) * 10;
      $maxValue = ceil(($allBpValues->max() + 10) / 10) * 10;
      if ($maxValue <= $minValue) $maxValue = $minValue + 20;
      $range = $maxValue - $minValue;
      $xFor = fn ($index) => $left + ($index / max(1, $bpVitals->count() - 1)) * $plotWidth;
      $yFor = fn ($value) => $top + (($maxValue - (float) $value) / $range) * $plotHeight;
      $sysPoints = $bpVitals->map(fn ($v, $i) => round($xFor($i), 1) . ',' . round($yFor($v->blood_pressure_systolic), 1))->implode(' ');
      $diaPoints = $bpVitals->map(fn ($v, $i) => round($xFor($i), 1) . ',' . round($yFor($v->blood_pressure_diastolic), 1))->implode(' ');
      $ticks = collect([$maxValue, ($maxValue + $minValue) / 2, $minValue]);
      $bpChart = compact('chartWidth','chartHeight','left','right','top','bottom','plotWidth','plotHeight','minValue','maxValue','range','xFor','yFor','sysPoints','diaPoints','ticks');
  }
@endphp

<div class="patient-vitals-page">
  <nav class="patient-appointments-workspace-nav" aria-label="{{ __('patient.vitals.patient_navigation') }}">
    <a href="{{ route('patient.doctors') }}"><i class="bi bi-calendar2-check" aria-hidden="true"></i><span>{{ __('dashboard.patient.appointments_title') }}</span></a>
    <a href="{{ route('patient.prescriptions') }}"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span>{{ __('dashboard.patient.stat_prescriptions') }}</span></a>
    <a href="{{ route('patient.lab-tests') }}"><i class="bi bi-eyedropper" aria-hidden="true"></i><span>{{ __('dashboard.patient.lab_tests') }}</span></a>
    <a href="{{ route('patient.facilities') }}"><i class="bi bi-hospital" aria-hidden="true"></i><span>{{ __('dashboard.patient.hospital_services') }}</span></a>
    <a class="active" href="{{ route('patient.records') }}"><i class="bi bi-clipboard2" aria-hidden="true"></i><span>{{ __('dashboard.patient.records_short') }}</span></a>
  </nav>

  <section class="patient-vitals-hero" aria-labelledby="patient-vitals-title">
    <div class="patient-vitals-breadcrumb">
      <a href="{{ route('patient.dashboard') }}">{{ __('patient.vitals.home') }}</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <a href="{{ route('patient.records') }}">{{ __('patient.records.title') }}</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <span>{{ __('patient.vitals.title') }}</span>
    </div>
    <div class="patient-vitals-hero-row">
      <div>
        <span class="patient-vitals-kicker">{{ __('patient.vitals.kicker') }}</span>
        <h1 id="patient-vitals-title">{{ __('patient.vitals.hero_title') }}</h1>
        <p>{{ __('patient.vitals.hero_desc') }}</p>
      </div>
      <a class="patient-vitals-back-link" href="{{ route('patient.records') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i>{{ __('patient.vitals.back_to_records') }}</a>
    </div>
  </section>

  @if ($vitals->isEmpty())
    <section class="patient-vitals-empty" aria-label="{{ __('patient.vitals.title') }}">
      <span><i class="bi bi-activity" aria-hidden="true"></i></span>
      <h2>{{ __('patient.vitals.empty_title') }}</h2>
      <p>{{ __('patient.vitals.empty') }}</p>
      <a href="{{ route('patient.records') }}">{{ __('patient.vitals.back_to_records') }}</a>
    </section>
  @else
    <div class="patient-vitals-overview-grid">
      <section class="patient-vitals-latest-card" aria-labelledby="patient-vitals-latest-title">
        <div class="patient-vitals-section-heading">
          <div>
            <h2 id="patient-vitals-latest-title">{{ __('patient.vitals.latest_readings') }}</h2>
            <p>{{ __('patient.vitals.latest_desc') }}</p>
          </div>
          <span>{{ __('patient.vitals.recorded_on') }} {{ $latestVital->recorded_at->format('M d, Y') }}</span>
        </div>

        <div class="patient-vitals-latest-grid">
          <article>
            <span class="patient-vitals-metric-icon"><i class="bi bi-heart-pulse" aria-hidden="true"></i></span>
            <div><small>{{ __('patient.vitals.blood_pressure') }}</small><strong>{{ $latestVital->bloodPressureLabel() }}@if($latestVital->bloodPressureLabel() !== '—') <em>mmHg</em>@endif</strong><span>{{ __('patient.vitals.latest_reading') }}</span></div>
          </article>
          <article>
            <span class="patient-vitals-metric-icon"><i class="bi bi-heart" aria-hidden="true"></i></span>
            <div><small>{{ __('patient.vitals.heart_rate') }}</small><strong>{{ $latestVital->heart_rate ?? '—' }}@if($latestVital->heart_rate) <em>bpm</em>@endif</strong><span>{{ __('patient.vitals.latest_reading') }}</span></div>
          </article>
          <article>
            <span class="patient-vitals-metric-icon"><i class="bi bi-thermometer-half" aria-hidden="true"></i></span>
            <div><small>{{ __('patient.vitals.temp') }}</small><strong>{{ $latestVital->temperature_celsius ?? '—' }}@if($latestVital->temperature_celsius) <em>°C</em>@endif</strong><span>{{ __('patient.vitals.latest_reading') }}</span></div>
          </article>
          <article>
            <span class="patient-vitals-metric-icon"><i class="bi bi-speedometer2" aria-hidden="true"></i></span>
            <div><small>{{ __('patient.vitals.weight') }}</small><strong>{{ $latestVital->weight_kg ?? '—' }}@if($latestVital->weight_kg) <em>kg</em>@endif</strong><span>{{ __('patient.vitals.latest_reading') }}</span></div>
          </article>
        </div>
        <p class="patient-vitals-latest-meta">
          {{ __('patient.vitals.recorded_by') }}
          <strong>{{ $latestVital->recordedByDoctor ? 'Dr. ' . $latestVital->recordedByDoctor->full_name : '—' }}</strong>
          @if ($latestVital->height_cm) · {{ __('patient.vitals.height') }} {{ $latestVital->height_cm }} cm @endif
        </p>
      </section>

      <section class="patient-vitals-bp-card" aria-labelledby="patient-vitals-bp-title">
        <div class="patient-vitals-section-heading patient-vitals-chart-heading">
          <div>
            <h2 id="patient-vitals-bp-title">{{ __('patient.vitals.bp_trend') }}</h2>
            <p>{{ __('patient.vitals.bp_trend_desc') }}</p>
          </div>
          <span>{{ trans_choice('patient.vitals.reading_count', $bpVitals->count(), ['count' => $bpVitals->count()]) }}</span>
        </div>

        @if ($bpChart)
          <div class="patient-vitals-chart-legend" aria-hidden="true">
            <span><i class="patient-vitals-legend-dot systolic"></i>{{ __('patient.vitals.systolic') }}</span>
            <span><i class="patient-vitals-legend-dot diastolic"></i>{{ __('patient.vitals.diastolic') }}</span>
          </div>
          <div class="patient-vitals-bp-chart" role="img" aria-label="{{ __('patient.vitals.bp_chart_aria') }}">
            <svg viewBox="0 0 {{ $bpChart['chartWidth'] }} {{ $bpChart['chartHeight'] }}" preserveAspectRatio="none" aria-hidden="true">
              @foreach ($bpChart['ticks'] as $tick)
                @php $tickY = $bpChart['yFor']($tick); @endphp
                <line x1="{{ $bpChart['left'] }}" x2="{{ $bpChart['chartWidth'] - $bpChart['right'] }}" y1="{{ $tickY }}" y2="{{ $tickY }}" class="patient-vitals-grid-line" />
                <text x="2" y="{{ $tickY + 3 }}" class="patient-vitals-axis-label">{{ round($tick) }}</text>
              @endforeach
              <polyline points="{{ $bpChart['sysPoints'] }}" class="patient-vitals-line systolic" />
              <polyline points="{{ $bpChart['diaPoints'] }}" class="patient-vitals-line diastolic" />
              @foreach ($bpVitals as $reading)
                @php $index = $loop->index; $x = $bpChart['xFor']($index); @endphp
                <circle cx="{{ $x }}" cy="{{ $bpChart['yFor']($reading->blood_pressure_systolic) }}" r="3" class="patient-vitals-point systolic" />
                <circle cx="{{ $x }}" cy="{{ $bpChart['yFor']($reading->blood_pressure_diastolic) }}" r="3" class="patient-vitals-point diastolic" />
                <text x="{{ $x }}" y="{{ $bpChart['chartHeight'] - 8 }}" text-anchor="middle" class="patient-vitals-date-label">{{ $reading->recorded_at->format('M j') }}</text>
              @endforeach
            </svg>
          </div>
        @else
          <div class="patient-vitals-chart-empty">
            <i class="bi bi-graph-up" aria-hidden="true"></i>
            <p>{{ __('patient.vitals.not_enough_bp') }}</p>
          </div>
        @endif
      </section>
    </div>

    <section class="patient-vitals-secondary-trends" aria-labelledby="patient-vitals-other-trends-title">
      <div class="patient-vitals-section-heading">
        <div>
          <h2 id="patient-vitals-other-trends-title">{{ __('patient.vitals.other_trends') }}</h2>
          <p>{{ __('patient.vitals.other_trends_desc') }}</p>
        </div>
      </div>
      <div class="patient-vitals-trend-grid">
        @foreach ($trends as $key => $trend)
          @if (!in_array($key, ['blood_pressure_systolic', 'blood_pressure_diastolic']) && $trend['readings']->isNotEmpty())
            <article class="patient-vitals-trend-card">
              <h3>{{ $trend['label'] }}</h3>
              @include('partials.bar-chart', ['data' => $trend['readings']])
            </article>
          @endif
        @endforeach
      </div>
    </section>

    <section class="patient-vitals-history-card" aria-labelledby="patient-vitals-history-title">
      <div class="patient-vitals-section-heading">
        <div>
          <h2 id="patient-vitals-history-title">{{ __('patient.vitals.full_history') }}</h2>
          <p>{{ trans_choice('patient.vitals.history_count', $orderedVitals->count(), ['count' => $orderedVitals->count()]) }}</p>
        </div>
      </div>
      <div class="patient-vitals-table-wrap">
        <table class="patient-vitals-table">
          <thead>
            <tr>
              <th>{{ __('patient.vitals.date') }}</th>
              <th>{{ __('patient.vitals.blood_pressure') }}</th>
              <th>{{ __('patient.vitals.heart_rate') }}</th>
              <th>{{ __('patient.vitals.temp') }}</th>
              <th>{{ __('patient.vitals.weight') }}</th>
              <th>{{ __('patient.vitals.height') }}</th>
              <th>{{ __('patient.vitals.recorded_by') }}</th>
              <th>{{ __('patient.vitals.notes') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($orderedVitals as $vital)
              <tr>
                <td><strong>{{ $vital->recorded_at->format('M d, Y') }}</strong><span>{{ $vital->recorded_at->format('h:i A') }}</span></td>
                <td>{{ $vital->bloodPressureLabel() }}@if($vital->bloodPressureLabel() !== '—') <span>mmHg</span>@endif</td>
                <td>{{ $vital->heart_rate ?? '—' }}@if($vital->heart_rate) <span>bpm</span>@endif</td>
                <td>{{ $vital->temperature_celsius ?? '—' }}@if($vital->temperature_celsius) <span>°C</span>@endif</td>
                <td>{{ $vital->weight_kg ?? '—' }}@if($vital->weight_kg) <span>kg</span>@endif</td>
                <td>{{ $vital->height_cm ?? '—' }}@if($vital->height_cm) <span>cm</span>@endif</td>
                <td>{{ $vital->recordedByDoctor ? 'Dr. ' . $vital->recordedByDoctor->full_name : '—' }}</td>
                <td class="patient-vitals-notes-cell">{{ $vital->notes ?? '—' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </section>
  @endif
  <footer class="patient-dashboard-footer patient-appointments-footer">
  	<div class="patient-dashboard-footer-brand">
	    <span class="patient-dashboard-footer-dot" aria-hidden="true"></span>
	    <div>
	      <strong>{{ __('dashboard.patient.platform_name') }}</strong>
	      <small>{{ __('home.brand_tagline') }}</small>
	    </div>
	  </div>

  	<p>{{ __('dashboard.patient.footer_tagline') }}</p>
   </footer>
</div>
@endsection
