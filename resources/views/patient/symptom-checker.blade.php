@extends('layouts.app')
@section('title', __('patient.symptom_checker.page_title'))
@section('content')
@php
  $resultCount = $results?->count() ?? 0;
  $doctorCount = $doctors?->count() ?? 0;
@endphp

<div class="patient-symptom-page">
  <nav class="patient-appointments-workspace-nav" aria-label="{{ __('patient.symptom_checker.patient_navigation') }}">
    <a href="{{ route('patient.doctors') }}"><i class="bi bi-calendar2-check" aria-hidden="true"></i><span>{{ __('dashboard.patient.appointments_title') }}</span></a>
    <a href="{{ route('patient.prescriptions') }}"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span>{{ __('dashboard.patient.stat_prescriptions') }}</span></a>
    <a href="{{ route('patient.lab-tests') }}"><i class="bi bi-flask" aria-hidden="true"></i><span>{{ __('dashboard.patient.lab_tests') }}</span></a>
    <a href="{{ route('patient.facilities') }}"><i class="bi bi-hospital" aria-hidden="true"></i><span>{{ __('dashboard.patient.hospital_services') }}</span></a>
    <a href="{{ route('patient.records') }}"><i class="bi bi-clipboard2" aria-hidden="true"></i><span>{{ __('dashboard.patient.records_short') }}</span></a>
  </nav>

  <section class="patient-symptom-hero" aria-labelledby="patient-symptom-title">
    <div class="patient-symptom-breadcrumb">
      <a href="{{ route('patient.dashboard') }}">{{ __('patient.symptom_checker.home') }}</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <span>{{ __('patient.symptom_checker.title') }}</span>
    </div>

    <div class="patient-symptom-kicker">{{ __('patient.symptom_checker.kicker') }}</div>
    <h1 id="patient-symptom-title">{{ __('patient.symptom_checker.hero_title') }}</h1>
    <p class="patient-symptom-hero-desc">{{ __('patient.symptom_checker.hero_desc') }}</p>

    <form method="POST" action="{{ route('patient.symptom-checker.search') }}" class="patient-symptom-form">
      @csrf
      <div class="patient-symptom-input-wrap">
        <label for="symptoms">{{ __('patient.symptom_checker.label') }}</label>
        <textarea id="symptoms" name="symptoms" rows="4" maxlength="500" placeholder="{{ __('patient.symptom_checker.placeholder') }}" required>{{ old('symptoms', $symptoms) }}</textarea>
        <span>{{ __('patient.symptom_checker.character_limit') }}</span>
      </div>
      <button type="submit" class="patient-symptom-submit"><i class="bi bi-search" aria-hidden="true"></i>{{ __('patient.symptom_checker.check_button') }}</button>
    </form>

    <div class="patient-symptom-disclaimer" role="note">
      <i class="bi bi-shield-exclamation" aria-hidden="true"></i>
      <p><strong>{{ __('patient.symptom_checker.disclaimer_title') }}</strong> {{ __('patient.symptom_checker.disclaimer') }}</p>
    </div>
  </section>

  @if ($results !== null)
    <div class="patient-symptom-content-grid">
      <section class="patient-symptom-main-card">
        <div class="patient-symptom-section-heading">
          <div>
            <span>{{ __('patient.symptom_checker.results_kicker') }}</span>
            <h2>{{ __('patient.symptom_checker.suggested_specialties') }}</h2>
            <p>{{ __('patient.symptom_checker.results_desc') }}</p>
          </div>
          @if ($resultCount > 0)
            <span class="patient-symptom-count-badge">{{ trans_choice('patient.symptom_checker.specialty_count', $resultCount, ['count' => $resultCount]) }}</span>
          @endif
        </div>

        @if ($results->isEmpty())
          <div class="patient-symptom-empty-state">
            <span><i class="bi bi-search" aria-hidden="true"></i></span>
            <h3>{{ __('patient.symptom_checker.no_match_title') }}</h3>
            <p>{{ __('patient.symptom_checker.no_match_desc') }}</p>
            <a href="{{ route('patient.doctors') }}">{{ __('patient.symptom_checker.browse_all') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
          </div>
        @else
          <div class="patient-symptom-specialty-list">
            @foreach ($results as $index => $r)
              <article class="patient-symptom-specialty-card {{ $index === 0 ? 'primary-match' : '' }}">
                <span class="patient-symptom-specialty-icon"><i class="bi bi-person-heart" aria-hidden="true"></i></span>
                <div class="patient-symptom-specialty-copy">
                  <small>{{ $index === 0 ? __('patient.symptom_checker.best_match') : __('patient.symptom_checker.also_relevant') }}</small>
                  <h3>{{ $r['specialty']->specialty_name }}</h3>
                  <p>{{ __('patient.symptom_checker.matched_on') }}</p>
                  <div class="patient-symptom-tags">
                    @foreach ($r['matchedKeywords'] as $keyword)
                      <span>{{ $keyword }}</span>
                    @endforeach
                  </div>
                </div>
                <div class="patient-symptom-score">
                  <strong>{{ $r['score'] }}</strong>
                  <span>{{ __('patient.symptom_checker.match_score') }}</span>
                </div>
              </article>
            @endforeach
          </div>
        @endif

        <div class="patient-symptom-conditions-section">
          <div class="patient-symptom-section-heading compact">
            <div>
              <span>{{ __('patient.symptom_checker.awareness_kicker') }}</span>
              <h2>{{ __('patient.symptom_checker.conditions_title') }}</h2>
              <p>{{ __('patient.symptom_checker.conditions_desc') }}</p>
            </div>
          </div>

          @if ($conditions->isEmpty())
            <div class="patient-symptom-neutral-note"><i class="bi bi-info-circle" aria-hidden="true"></i><span>{{ __('patient.symptom_checker.no_conditions') }}</span></div>
          @else
            <div class="patient-symptom-condition-list">
              @foreach ($conditions as $c)
                <article class="patient-symptom-condition-card">
                  <div>
                    <span>{{ __('patient.symptom_checker.possible_match') }}</span>
                    <h3>{{ $c['disease']->name }}</h3>
                  </div>
                  <p>{{ $c['disease']->advice }}</p>
                  <small>{{ __('patient.symptom_checker.matched_on') }}: {{ $c['matchedKeywords']->implode(', ') }}</small>
                </article>
              @endforeach
            </div>
          @endif
        </div>

        <div class="patient-symptom-doctors-section">
          <div class="patient-symptom-section-heading compact">
            <div>
              <span>{{ __('patient.symptom_checker.next_step_kicker') }}</span>
              <h2>{{ __('patient.symptom_checker.doctors_in_specialties') }}</h2>
              <p>{{ __('patient.symptom_checker.doctors_desc') }}</p>
            </div>
            @if ($doctorCount > 0)
              <a class="patient-symptom-text-link" href="{{ route('patient.doctors') }}">{{ __('patient.symptom_checker.browse_all') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            @endif
          </div>

          @if ($doctors->isEmpty())
            <div class="patient-symptom-neutral-note"><i class="bi bi-person-check" aria-hidden="true"></i><span>{{ __('patient.symptom_checker.no_doctors') }}</span></div>
          @else
            <div class="patient-symptom-doctor-grid">
              @foreach ($doctors as $doctor)
                @php
                  $doctorPhoto = $doctor->account?->photoUrl();
                  $initials = collect(preg_split('/\s+/', trim($doctor->full_name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
                @endphp
                <article class="patient-symptom-doctor-card">
                  <div class="patient-symptom-doctor-avatar">
                    @if ($doctorPhoto)
                      <img src="{{ $doctorPhoto }}" alt="">
                    @else
                      <span>{{ $initials ?: 'DR' }}</span>
                    @endif
                  </div>
                  <div class="patient-symptom-doctor-copy">
                    <h3>Dr. {{ $doctor->full_name }}</h3>
                    <p>{{ $doctor->specialties->pluck('specialty_name')->implode(' · ') }}</p>
                    <div class="patient-symptom-doctor-meta">
                      <span><i class="bi bi-cash-stack" aria-hidden="true"></i>{{ __('patient.symptom_checker.consultation_fee', ['amount' => number_format($doctor->consultation_fee, 2)]) }}</span>
                      <span><i class="bi bi-star-fill" aria-hidden="true"></i>
                        @if ($doctor->reviews_count > 0)
                          {{ number_format($doctor->reviews_avg_rating, 1) }} · {{ trans_choice('patient.symptom_checker.review_count', $doctor->reviews_count, ['count' => $doctor->reviews_count]) }}
                        @else
                          {{ __('patient.symptom_checker.no_ratings') }}
                        @endif
                      </span>
                    </div>
                  </div>
                  <div class="patient-symptom-doctor-actions">
                    <a class="patient-symptom-primary-action" href="{{ route('patient.doctors.show', $doctor) }}">{{ __('patient.symptom_checker.view_doctor') }}</a>
                  </div>
                </article>
              @endforeach
            </div>
          @endif
        </div>
      </section>

      <aside class="patient-symptom-sidebar">
        <section class="patient-symptom-side-card">
          <span class="patient-symptom-side-icon"><i class="bi bi-diagram-3" aria-hidden="true"></i></span>
          <h2>{{ __('patient.symptom_checker.how_it_works') }}</h2>
          <p>{{ __('patient.symptom_checker.how_it_works_desc') }}</p>
          <div class="patient-symptom-steps">
            <div><span>1</span><p><strong>{{ __('patient.symptom_checker.step_one_title') }}</strong>{{ __('patient.symptom_checker.step_one_desc') }}</p></div>
            <div><span>2</span><p><strong>{{ __('patient.symptom_checker.step_two_title') }}</strong>{{ __('patient.symptom_checker.step_two_desc') }}</p></div>
            <div><span>3</span><p><strong>{{ __('patient.symptom_checker.step_three_title') }}</strong>{{ __('patient.symptom_checker.step_three_desc') }}</p></div>
          </div>
        </section>

        <section class="patient-symptom-side-card patient-symptom-urgent-card">
          <span class="patient-symptom-side-icon"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i></span>
          <h2>{{ __('patient.symptom_checker.urgent_title') }}</h2>
          <p>{{ __('patient.symptom_checker.urgent_desc') }}</p>
        </section>

        <section class="patient-symptom-side-card">
          <span class="patient-symptom-side-icon"><i class="bi bi-question-circle" aria-hidden="true"></i></span>
          <h2>{{ __('patient.symptom_checker.help_title') }}</h2>
          <p>{{ __('patient.symptom_checker.help_desc') }}</p>
          <a class="patient-symptom-secondary-action" href="{{ route('help.index') }}">{{ __('patient.symptom_checker.open_help') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </section>
      </aside>
    </div>
  @else
    <section class="patient-symptom-start-card">
      <div><span><i class="bi bi-chat-square-text" aria-hidden="true"></i></span><h2>{{ __('patient.symptom_checker.start_title') }}</h2><p>{{ __('patient.symptom_checker.start_desc') }}</p></div>
      <a href="{{ route('patient.doctors') }}">{{ __('patient.symptom_checker.browse_all') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </section>
  @endif

  <footer class="patient-symptom-footer">
    <div><span aria-hidden="true"></span><strong>{{ __('dashboard.patient.platform_name') }}</strong><small>{{ __('home.brand_tagline') }}</small></div>
    <p>{{ __('dashboard.patient.footer_tagline') }}</p>
  </footer>
</div>
@endsection
