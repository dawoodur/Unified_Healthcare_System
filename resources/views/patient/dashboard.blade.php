@extends('layouts.app')
@section('title', __('dashboard.patient.page_title'))
@section('content')
@php
  $account = auth()->user();
  $appointmentTypeLabel = $upcomingAppointment
      ? ($upcomingAppointment->appointment_type === 'online'
          ? __('dashboard.patient.video_consultation')
          : __('dashboard.patient.onsite_visit'))
      : null;
@endphp

<div class="patient-dashboard-page">
  <section class="patient-dashboard-top-grid" aria-label="{{ __('dashboard.patient.workspace_label') }}">
    <div class="patient-dashboard-hero">
      <div class="patient-dashboard-hero-copy">
        <div class="patient-dashboard-eyebrow">
          <span class="patient-dashboard-eyebrow-dot" aria-hidden="true"></span>
          {{ __('dashboard.patient.workspace_label') }}
        </div>
        <h1>{{ __('dashboard.patient.good_to_see', ['name' => $patient->full_name]) }}</h1>
        <p>{{ __('dashboard.patient.workspace_desc') }}</p>

        <div class="patient-dashboard-meta" aria-label="{{ __('dashboard.patient.profile_summary') }}">
          <span><i class="bi bi-person" aria-hidden="true"></i>{{ $account->uidTag() }}</span>
          <span><i class="bi bi-calendar3" aria-hidden="true"></i>{{ __('dashboard.patient.age') }} {{ $patient->age }}</span>
          <span><i class="bi bi-droplet" aria-hidden="true"></i>{{ __('dashboard.patient.blood_group') }} {{ $patient->blood_group }}</span>
        </div>
      </div>

      <a href="{{ route('patient.rewards') }}" class="patient-dashboard-reward" aria-label="{{ __('dashboard.patient.view_rewards') }}">
        <span class="patient-dashboard-reward-icon"><i class="bi bi-star" aria-hidden="true"></i></span>
        <span class="patient-dashboard-reward-copy">
          <small>{{ __('dashboard.patient.reward_balance') }}</small>
          <strong>{{ $patient->reward_points_balance }}</strong>
          <span>{{ __('dashboard.patient.points_available') }}</span>
        </span>
        <i class="bi bi-chevron-right patient-dashboard-reward-arrow" aria-hidden="true"></i>
      </a>
    </div>

    <aside class="patient-dashboard-consultation" aria-labelledby="patient-upcoming-title">
      <div class="patient-dashboard-panel-heading">
        <h2 id="patient-upcoming-title">{{ __('dashboard.patient.upcoming_consultation') }}</h2>
        <a href="{{ route('patient.appointments') }}">{{ __('dashboard.patient.view_all') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
      </div>

      @if ($upcomingAppointment)
        <div class="patient-dashboard-consultation-body">
          <div class="patient-dashboard-date-tile">
            <span>{{ strtoupper($upcomingAppointment->appointment_date->format('M')) }}</span>
            <strong>{{ $upcomingAppointment->appointment_date->format('d') }}</strong>
            <small>{{ $upcomingAppointment->appointment_date->format('D') }}</small>
          </div>

          <div class="patient-dashboard-consultation-details">
            <strong>Dr. {{ $upcomingAppointment->doctor->full_name }}</strong>
            <span>{{ $upcomingAppointment->doctor->specialties->pluck('specialty_name')->implode(', ') ?: '—' }}</span>
            @if ($upcomingAppointment->hospital)
              <span>{{ $upcomingAppointment->hospital->hospital_name }}</span>
            @endif
            <div class="patient-dashboard-consultation-meta">
              <span><i class="bi bi-clock" aria-hidden="true"></i>{{ \Illuminate\Support\Carbon::parse($upcomingAppointment->appointment_time)->format('g:i A') }}</span>
              <span><i class="bi {{ $upcomingAppointment->appointment_type === 'online' ? 'bi-camera-video' : 'bi-geo-alt' }}" aria-hidden="true"></i>{{ $appointmentTypeLabel }}</span>
            </div>
          </div>
        </div>

        <div class="patient-dashboard-consultation-actions">
          @if ($upcomingAppointment->appointment_type === 'online')
            @if ($upcomingAppointment->isJoinableNow())
              <a href="{{ route('consultation.show', $upcomingAppointment) }}" class="patient-dashboard-primary-action"><i class="bi bi-camera-video-fill" aria-hidden="true"></i>{{ __('dashboard.patient.join_now') }}</a>
            @else
              <button type="button" class="patient-dashboard-primary-action" disabled><i class="bi bi-camera-video-fill" aria-hidden="true"></i>{{ __('dashboard.patient.join_now') }}</button>
            @endif
          @else
            <a href="{{ route('patient.appointments') }}" class="patient-dashboard-primary-action"><i class="bi bi-calendar2-check" aria-hidden="true"></i>{{ __('dashboard.patient.view_appointment') }}</a>
          @endif
          <a href="{{ route('patient.appointments') }}" class="patient-dashboard-secondary-action">{{ __('dashboard.patient.reschedule') }}</a>
        </div>
      @else
        <div class="patient-dashboard-empty-consultation">
          <span class="patient-dashboard-empty-icon"><i class="bi bi-calendar2-heart" aria-hidden="true"></i></span>
          <p>{{ __('dashboard.patient.no_upcoming_appointment') }}</p>
          <a href="{{ route('patient.doctors') }}">{{ __('dashboard.patient.find_doctor') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
      @endif
    </aside>
  </section>

  <nav class="patient-dashboard-quick-nav" aria-label="{{ __('dashboard.patient.quick_access') }}">
    <a href="{{ route('patient.doctors') }}"><i class="bi bi-calendar2-check" aria-hidden="true"></i><span>{{ __('dashboard.patient.appointments_title') }}</span></a>
    <a href="{{ route('patient.prescriptions') }}"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span>{{ __('dashboard.patient.stat_prescriptions') }}</span></a>
    <a href="{{ route('patient.lab-tests') }}"><i class="bi bi-eyedropper" aria-hidden="true"></i><span>{{ __('dashboard.patient.lab_tests') }}</span></a>
    <a href="{{ route('patient.facilities') }}"><i class="bi bi-hospital" aria-hidden="true"></i><span>{{ __('dashboard.patient.hospital_services') }}</span></a>
    <a href="{{ route('patient.records') }}"><i class="bi bi-clipboard2" aria-hidden="true"></i><span>{{ __('dashboard.patient.records_short') }}</span></a>
  </nav>

  <section class="patient-dashboard-tools" aria-labelledby="patient-tools-title">
    <div class="patient-dashboard-tools-heading">
      <div>
        <span>{{ __('dashboard.patient.care_tools_label') }}</span>
        <h2 id="patient-tools-title">{{ __('dashboard.patient.what_to_do') }}</h2>
      </div>
      <div class="patient-dashboard-tools-heading-actions">
        <p>{{ __('dashboard.patient.care_tools_desc') }}</p>
        <button type="button" class="patient-dashboard-customize-button" data-dashboard-customize-open>
          <i class="bi bi-sliders" aria-hidden="true"></i>
          {{ __('dashboard.patient.customize_dashboard') }}
        </button>
      </div>
    </div>

    <div class="patient-dashboard-tool-grid">
      <article class="patient-dashboard-tool-card patient-dashboard-tool-card-primary">
        <div class="patient-dashboard-tool-icon"><i class="bi bi-calendar2-plus" aria-hidden="true"></i></div>
        <span class="patient-dashboard-tool-kicker">{{ __('dashboard.patient.consultation_label') }}</span>
        <h3>{{ __('dashboard.patient.appointments_title') }}</h3>
        <p>{{ __('dashboard.patient.appointments_card_desc') }}</p>
        <div class="patient-dashboard-tool-links">
          <a href="{{ route('patient.doctors') }}">{{ __('dashboard.patient.find_doctor') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
          <a href="{{ route('patient.appointments') }}">{{ __('dashboard.patient.my_appointments') }}</a>
        </div>
      </article>

      @if (in_array('facilities', $selectedDashboardTools, true))
      <article class="patient-dashboard-tool-card">
        <div class="patient-dashboard-tool-icon"><i class="bi bi-hospital" aria-hidden="true"></i></div>
        <span class="patient-dashboard-tool-kicker">{{ __('dashboard.patient.hospital_services') }}</span>
        <h3>{{ __('dashboard.patient.facilities_title') }}</h3>
        <p>{{ __('dashboard.patient.facilities_desc') }}</p>
        <div class="patient-dashboard-tool-links">
          <a href="{{ route('patient.facilities') }}">{{ __('dashboard.patient.compare_prices') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
          <a href="{{ route('patient.facility-bookings') }}">{{ __('dashboard.patient.my_bookings_short') }}</a>
        </div>
      </article>
      @endif

      @if (in_array('medicine', $selectedDashboardTools, true))
      <article class="patient-dashboard-tool-card">
        <div class="patient-dashboard-tool-icon"><i class="bi bi-capsule-pill" aria-hidden="true"></i></div>
        <span class="patient-dashboard-tool-kicker">{{ __('dashboard.patient.medicine_order_kicker') }}</span>
        <h3>{{ __('dashboard.patient.medicine_order_card_title') }}</h3>
        <p>{{ __('dashboard.patient.medicine_desc') }}</p>
        <div class="patient-dashboard-tool-links">
          <a href="{{ route('patient.medicine') }}">{{ __('dashboard.patient.order_medicine') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
          <a href="{{ route('patient.orders') }}">{{ __('dashboard.patient.my_orders') }}</a>
        </div>
      </article>
      @endif

      @if (in_array('blood', $selectedDashboardTools, true))
      <article class="patient-dashboard-tool-card">
        <div class="patient-dashboard-tool-icon"><i class="bi bi-droplet-half" aria-hidden="true"></i></div>
        <span class="patient-dashboard-tool-kicker">{{ __('dashboard.patient.blood_donation_kicker') }}</span>
        <h3>{{ __('dashboard.patient.blood_donation_card_title') }}</h3>
        <p>{{ __('dashboard.patient.blood_desc') }}</p>
        <div class="patient-dashboard-tool-links patient-dashboard-tool-links-single">
          <a href="{{ route('patient.blood-donations') }}">{{ __('dashboard.patient.my_donations') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
      </article>
      @endif

      @if (in_array('records', $selectedDashboardTools, true))
      <article class="patient-dashboard-tool-card">
        <div class="patient-dashboard-tool-icon"><i class="bi bi-clipboard2" aria-hidden="true"></i></div>
        <span class="patient-dashboard-tool-kicker">{{ __('dashboard.patient.health_history_label') }}</span>
        <h3>{{ __('dashboard.patient.records_title') }}</h3>
        <p>{{ __('dashboard.patient.records_card_desc') }}</p>
        <div class="patient-dashboard-tool-links patient-dashboard-tool-links-single">
          <a href="{{ route('patient.records') }}">{{ __('dashboard.patient.open_records') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
      </article>
      @endif

      @if (in_array('assistant', $selectedDashboardTools, true))
      <article class="patient-dashboard-tool-card">
        <div class="patient-dashboard-tool-icon"><i class="bi bi-shield-check" aria-hidden="true"></i></div>
        <span class="patient-dashboard-tool-kicker">{{ __('dashboard.patient.guidance_label') }}</span>
        <h3>{{ __('dashboard.patient.symptom_help_title') }}</h3>
        <p>{{ __('dashboard.patient.symptom_help_desc') }}</p>
        <div class="patient-dashboard-tool-links patient-dashboard-tool-links-single">
          <a href="{{ route('patient.symptom-checker') }}">{{ __('dashboard.patient.open_assistant') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
      </article>
      @endif
    </div>
  </section>

  <dialog class="patient-dashboard-customize-dialog" id="patientDashboardCustomizeDialog">
    <div class="patient-dashboard-customize-modal">
      <header class="patient-dashboard-customize-header">
        <div>
          <span class="patient-dashboard-customize-header-icon"><i class="bi bi-sliders" aria-hidden="true"></i></span>
          <div>
            <small>{{ __('dashboard.patient.care_tools_label') }}</small>
            <h2>{{ __('dashboard.patient.customize_dashboard') }}</h2>
            <p>{{ __('dashboard.patient.customize_dashboard_desc') }}</p>
          </div>
        </div>

        <button type="button" class="patient-dashboard-customize-close" data-dashboard-customize-close aria-label="{{ __('dashboard.patient.close') }}">
          <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
      </header>

      <form method="POST" action="{{ route('patient.dashboard.tools.update') }}" class="patient-dashboard-customize-form">
        @csrf

        <div class="patient-dashboard-customize-grid">
          <label class="patient-dashboard-customize-option patient-dashboard-customize-option-required">
            <input type="hidden" name="tools[]" value="appointments">
            <input type="checkbox" checked disabled>
            <span class="patient-dashboard-customize-option-icon"><i class="bi bi-calendar2-plus" aria-hidden="true"></i></span>
            <span class="patient-dashboard-customize-option-copy">
              <strong>{{ __('dashboard.patient.appointments_title') }}</strong>
              <small>{{ __('dashboard.patient.appointments_card_desc') }}</small>
            </span>
            <span class="patient-dashboard-required-badge">{{ __('dashboard.patient.always_included') }}</span>
          </label>

          <label class="patient-dashboard-customize-option">
            <input type="checkbox" name="tools[]" value="facilities" @checked(in_array('facilities', $selectedDashboardTools, true))>
            <span class="patient-dashboard-customize-option-icon"><i class="bi bi-hospital" aria-hidden="true"></i></span>
            <span class="patient-dashboard-customize-option-copy">
              <strong>{{ __('dashboard.patient.facilities_title') }}</strong>
              <small>{{ __('dashboard.patient.facilities_desc') }}</small>
            </span>
          </label>

          <label class="patient-dashboard-customize-option">
            <input type="checkbox" name="tools[]" value="medicine" @checked(in_array('medicine', $selectedDashboardTools, true))>
            <span class="patient-dashboard-customize-option-icon"><i class="bi bi-capsule-pill" aria-hidden="true"></i></span>
            <span class="patient-dashboard-customize-option-copy">
              <strong>{{ __('dashboard.patient.medicine_order_card_title') }}</strong>
              <small>{{ __('dashboard.patient.medicine_desc') }}</small>
            </span>
          </label>

          <label class="patient-dashboard-customize-option">
            <input type="checkbox" name="tools[]" value="blood" @checked(in_array('blood', $selectedDashboardTools, true))>
            <span class="patient-dashboard-customize-option-icon"><i class="bi bi-droplet-half" aria-hidden="true"></i></span>
            <span class="patient-dashboard-customize-option-copy">
              <strong>{{ __('dashboard.patient.blood_donation_card_title') }}</strong>
              <small>{{ __('dashboard.patient.blood_desc') }}</small>
            </span>
          </label>

          <label class="patient-dashboard-customize-option">
            <input type="checkbox" name="tools[]" value="records" @checked(in_array('records', $selectedDashboardTools, true))>
            <span class="patient-dashboard-customize-option-icon"><i class="bi bi-clipboard2" aria-hidden="true"></i></span>
            <span class="patient-dashboard-customize-option-copy">
              <strong>{{ __('dashboard.patient.records_title') }}</strong>
              <small>{{ __('dashboard.patient.records_card_desc') }}</small>
            </span>
          </label>

          <label class="patient-dashboard-customize-option">
            <input type="checkbox" name="tools[]" value="assistant" @checked(in_array('assistant', $selectedDashboardTools, true))>
            <span class="patient-dashboard-customize-option-icon"><i class="bi bi-shield-check" aria-hidden="true"></i></span>
            <span class="patient-dashboard-customize-option-copy">
              <strong>{{ __('dashboard.patient.symptom_help_title') }}</strong>
              <small>{{ __('dashboard.patient.symptom_help_desc') }}</small>
            </span>
          </label>
        </div>

        <p class="patient-dashboard-customize-note">
          <i class="bi bi-info-circle" aria-hidden="true"></i>
          {{ __('dashboard.patient.customize_note') }}
        </p>

        <footer class="patient-dashboard-customize-actions">
          <button type="button" class="patient-dashboard-customize-cancel" data-dashboard-customize-close>
            {{ __('dashboard.patient.cancel') }}
          </button>
          <button type="submit" class="patient-dashboard-customize-save">
            <i class="bi bi-check2" aria-hidden="true"></i>
            {{ __('dashboard.patient.save_dashboard') }}
          </button>
        </footer>
      </form>
    </div>
  </dialog>

  <footer class="patient-dashboard-footer">
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

@push('scripts')
<script>
(function () {
  const dialog = document.getElementById('patientDashboardCustomizeDialog');
  if (!dialog) return;

  document.querySelectorAll('[data-dashboard-customize-open]').forEach((button) => {
    button.addEventListener('click', () => dialog.showModal());
  });

  document.querySelectorAll('[data-dashboard-customize-close]').forEach((button) => {
    button.addEventListener('click', () => dialog.close());
  });

  dialog.addEventListener('click', (event) => {
    const rect = dialog.getBoundingClientRect();
    const clickedBackdrop =
      event.clientX < rect.left ||
      event.clientX > rect.right ||
      event.clientY < rect.top ||
      event.clientY > rect.bottom;

    if (clickedBackdrop) dialog.close();
  });
})();
</script>
@endpush
@endsection
