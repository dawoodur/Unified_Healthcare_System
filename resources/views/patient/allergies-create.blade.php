@extends('layouts.app')
@section('title', __('patient.allergy_create.page_title'))
@section('content')
<div class="patient-allergy-page">
  <nav class="patient-appointments-workspace-nav" aria-label="{{ __('patient.allergy_create.patient_navigation') }}">
    <a href="{{ route('patient.doctors') }}"><i class="bi bi-calendar2-check" aria-hidden="true"></i><span>{{ __('dashboard.patient.appointments_title') }}</span></a>
    <a href="{{ route('patient.prescriptions') }}"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span>{{ __('dashboard.patient.stat_prescriptions') }}</span></a>
    <a href="{{ route('patient.lab-tests') }}"><i class="bi bi-eyedropper" aria-hidden="true"></i><span>{{ __('dashboard.patient.lab_tests') }}</span></a>
    <a href="{{ route('patient.facilities') }}"><i class="bi bi-hospital" aria-hidden="true"></i><span>{{ __('dashboard.patient.hospital_services') }}</span></a>
    <a class="active" href="{{ route('patient.records') }}"><i class="bi bi-clipboard2" aria-hidden="true"></i><span>{{ __('dashboard.patient.records_short') }}</span></a>
  </nav>

  <section class="patient-allergy-hero" aria-labelledby="patient-allergy-title">
    <div class="patient-allergy-breadcrumb">
      <a href="{{ route('patient.dashboard') }}">{{ __('patient.allergy_create.home') }}</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <a href="{{ route('patient.records') }}">{{ __('patient.allergy_create.medical_records') }}</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <span>{{ __('patient.allergy_create.title') }}</span>
    </div>
    <div class="patient-allergy-hero-row">
      <div class="patient-allergy-hero-copy">
        <span class="patient-allergy-kicker">{{ __('patient.allergy_create.kicker') }}</span>
        <h1 id="patient-allergy-title">{{ __('patient.allergy_create.title') }}</h1>
        <p>{{ __('patient.allergy_create.hero_desc') }}</p>
      </div>
      <div class="patient-allergy-hero-mark" aria-hidden="true">
        <span><i class="bi bi-shield-plus"></i></span>
      </div>
    </div>
  </section>

  <div class="patient-allergy-layout">
    <main class="patient-allergy-main-column">
      <section class="patient-allergy-card patient-allergy-current" aria-labelledby="patient-allergy-current-title">
        <div class="patient-allergy-section-heading">
          <div>
            <h2 id="patient-allergy-current-title">{{ __('patient.allergy_create.current_title') }}</h2>
            <p>{{ __('patient.allergy_create.current_desc') }}</p>
          </div>
          <span>{{ trans_choice('patient.allergy_create.allergy_count', $allergies->count(), ['count' => $allergies->count()]) }}</span>
        </div>

        @if ($allergies->isEmpty())
          <div class="patient-allergy-empty">
            <span><i class="bi bi-shield-check" aria-hidden="true"></i></span>
            <div>
              <strong>{{ __('patient.allergy_create.no_allergies_title') }}</strong>
              <p>{{ __('patient.allergy_create.no_allergies_desc') }}</p>
            </div>
          </div>
        @else
          <div class="patient-allergy-current-grid">
            @foreach ($allergies as $allergy)
              <article class="patient-allergy-item">
                <span class="patient-allergy-item-icon"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i></span>
                <div class="patient-allergy-item-copy">
                  <strong>{{ $allergy->allergen }}</strong>
                  <p><span>{{ __('patient.allergy_create.reaction_prefix') }}</span> {{ $allergy->reaction ?: __('patient.allergy_create.no_reaction') }}</p>
                  <small>
                    @if ($allergy->created_at)
                      {{ __('patient.allergy_create.added_on', ['date' => $allergy->created_at->format('M d, Y')]) }}
                    @endif
                    @if ($allergy->recordedBy)
                      @if ($allergy->created_at) · @endif{{ __('patient.allergy_create.recorded_by', ['name' => $allergy->recorded_by_account_id === auth()->id() ? __('patient.allergy_create.self_reported') : $allergy->recordedBy->displayName()]) }}
                    @endif
                  </small>
                </div>
              </article>
            @endforeach
          </div>
        @endif
      </section>

      <section class="patient-allergy-card patient-allergy-form-card" aria-labelledby="patient-allergy-form-title">
        <div class="patient-allergy-section-heading">
          <div>
            <h2 id="patient-allergy-form-title">{{ __('patient.allergy_create.form_title') }}</h2>
            <p>{{ __('patient.allergy_create.form_desc') }}</p>
          </div>
        </div>

        @if ($errors->any())
          <div class="patient-allergy-errors" role="alert">
            <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
            <div>
              @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
              @endforeach
            </div>
          </div>
        @endif

        <form method="POST" action="{{ route('patient.allergies.store') }}" class="patient-allergy-form" data-confirm="Add this allergy?">
          @csrf
          <div class="patient-allergy-fields">
            <div class="patient-allergy-field">
              <label for="allergen">{{ __('patient.allergy_create.allergen_label') }} <span aria-hidden="true">*</span></label>
              <div class="patient-allergy-control">
                <i class="bi bi-shield-exclamation" aria-hidden="true"></i>
                <input type="text" id="allergen" name="allergen" value="{{ old('allergen') }}" maxlength="150" placeholder="{{ __('patient.allergy_create.allergen_placeholder') }}" required>
              </div>
            </div>
            <div class="patient-allergy-field">
              <label for="reaction">{{ __('patient.allergy_create.reaction_label') }}</label>
              <div class="patient-allergy-control">
                <i class="bi bi-chat-left-text" aria-hidden="true"></i>
                <input type="text" id="reaction" name="reaction" value="{{ old('reaction') }}" maxlength="255" placeholder="{{ __('patient.allergy_create.reaction_placeholder') }}">
              </div>
            </div>
          </div>

          <div class="patient-allergy-form-note">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            <p>{{ __('patient.allergy_create.intro') }}</p>
          </div>

          <div class="patient-allergy-actions">
            <a href="{{ route('patient.records') }}" class="patient-allergy-cancel"><i class="bi bi-arrow-left" aria-hidden="true"></i>{{ __('patient.allergy_create.cancel') }}</a>
            <button type="submit" class="patient-allergy-submit"><i class="bi bi-plus-lg" aria-hidden="true"></i>{{ __('patient.allergy_create.submit') }}</button>
          </div>
        </form>
      </section>
    </main>

    <aside class="patient-allergy-side-column" aria-label="{{ __('patient.allergy_create.important_title') }}">
      <section class="patient-allergy-side-card patient-allergy-important">
        <div class="patient-allergy-side-heading">
          <span><i class="bi bi-info-circle" aria-hidden="true"></i></span>
          <h2>{{ __('patient.allergy_create.important_title') }}</h2>
        </div>
        <p>{{ __('patient.allergy_create.important_desc') }}</p>
      </section>

      <section class="patient-allergy-side-card">
        <div class="patient-allergy-side-heading">
          <span><i class="bi bi-clipboard2-check" aria-hidden="true"></i></span>
          <h2>{{ __('patient.allergy_create.what_happens_title') }}</h2>
        </div>
        <ol class="patient-allergy-steps">
          <li><span>1</span><p>{{ __('patient.allergy_create.what_happens_1') }}</p></li>
          <li><span>2</span><p>{{ __('patient.allergy_create.what_happens_2') }}</p></li>
          <li><span>3</span><p>{{ __('patient.allergy_create.what_happens_3') }}</p></li>
        </ol>
      </section>

      <section class="patient-allergy-side-card">
        <div class="patient-allergy-side-heading">
          <span><i class="bi bi-lightbulb" aria-hidden="true"></i></span>
          <h2>{{ __('patient.allergy_create.tips_title') }}</h2>
        </div>
        <ul class="patient-allergy-tips">
          <li><i class="bi bi-check-circle-fill" aria-hidden="true"></i><span>{{ __('patient.allergy_create.tip_1') }}</span></li>
          <li><i class="bi bi-check-circle-fill" aria-hidden="true"></i><span>{{ __('patient.allergy_create.tip_2') }}</span></li>
          <li><i class="bi bi-check-circle-fill" aria-hidden="true"></i><span>{{ __('patient.allergy_create.tip_3') }}</span></li>
        </ul>
      </section>
    </aside>
  </div>

  <footer class="patient-dashboard-footer patient-allergy-footer">
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
