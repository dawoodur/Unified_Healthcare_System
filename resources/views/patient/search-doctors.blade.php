@extends('layouts.app')
@section('title', __('patient.search.page_title'))
@section('content')
@php
  $topSpecialties = $specialties->take(5);
@endphp

<div class="patient-appointments-page patient-doctor-discovery-page">
  <nav class="patient-appointments-workspace-nav" aria-label="{{ __('patient.search.patient_navigation') }}">
    <a class="active" href="{{ route('patient.doctors') }}"><i class="bi bi-calendar2-check" aria-hidden="true"></i><span>{{ __('dashboard.patient.appointments_title') }}</span></a>
    <a href="{{ route('patient.prescriptions') }}"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span>{{ __('dashboard.patient.stat_prescriptions') }}</span></a>
    <a href="{{ route('patient.lab-tests') }}"><i class="bi bi-eyedropper" aria-hidden="true"></i><span>{{ __('dashboard.patient.lab_tests') }}</span></a>
    <a href="{{ route('patient.facilities') }}"><i class="bi bi-hospital" aria-hidden="true"></i><span>{{ __('dashboard.patient.hospital_services') }}</span></a>
    <a href="{{ route('patient.records') }}"><i class="bi bi-clipboard2" aria-hidden="true"></i><span>{{ __('dashboard.patient.records_short') }}</span></a>
  </nav>

  <section class="patient-appointments-hero" aria-labelledby="patient-doctor-search-title">
    <div class="patient-appointments-breadcrumb">
      <a href="{{ route('patient.dashboard') }}">{{ __('patient.search.home') }}</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <span>{{ __('dashboard.patient.appointments_title') }}</span>
    </div>

    <div class="patient-appointments-hero-content">
      <div class="patient-appointments-hero-copy">
        <span class="patient-doctor-discovery-eyebrow">{{ __('dashboard.patient.appointments_title') }}</span>
        <h1 id="patient-doctor-search-title">{{ __('patient.search.hero_title') }}</h1>
        <p>{{ __('patient.search.hero_desc') }}</p>

        <form method="GET" action="{{ route('patient.doctors') }}" class="patient-appointments-search-form">
          <div class="patient-appointments-search-input">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" name="name" value="{{ $name }}" placeholder="{{ __('patient.search.search_placeholder') }}" aria-label="{{ __('patient.search.name_label') }}">
          </div>
          @if ($selectedSpecialtyId)<input type="hidden" name="specialty_id" value="{{ $selectedSpecialtyId }}">@endif
          @if ($uidInput)<input type="hidden" name="u_id" value="{{ $uidInput }}">@endif
          @if ($availability)<input type="hidden" name="availability" value="{{ $availability }}">@endif
          @if ($gender)<input type="hidden" name="gender" value="{{ $gender }}">@endif
          @if ($consultationType)<input type="hidden" name="consultation_type" value="{{ $consultationType }}">@endif
          @if ($sort !== 'recommended')<input type="hidden" name="sort" value="{{ $sort }}">@endif
          <button type="submit" class="patient-appointments-search-button">{{ __('patient.search.search_button') }}</button>
        </form>

        <div class="patient-appointments-specialty-row" aria-label="{{ __('patient.search.specialty_label') }}">
          <a class="patient-appointments-specialty-chip {{ !$selectedSpecialtyId ? 'active' : '' }}" href="{{ route('patient.doctors', request()->except(['specialty_id'])) }}">{{ __('patient.search.all_specialties') }}</a>
          @foreach ($topSpecialties as $specialty)
            @php $chipQuery = array_merge(request()->except(['specialty_id']), ['specialty_id' => $specialty->specialty_id]); @endphp
            <a class="patient-appointments-specialty-chip {{ $selectedSpecialtyId === $specialty->specialty_id ? 'active' : '' }}" href="{{ route('patient.doctors', $chipQuery) }}">{{ $specialty->specialty_name }}</a>
          @endforeach
          <button type="button" class="patient-appointments-more-button" id="patientDoctorMoreFilters" aria-controls="patientDoctorFilterPanel" aria-expanded="false">
            {{ __('patient.search.more_filters') }} <i class="bi bi-chevron-down" aria-hidden="true"></i>
          </button>

        </div>
      </div>

      <div class="patient-appointments-hero-visual" aria-hidden="true">
        <img class="patient-appointments-hero-doctor-illustration" src="{{ asset('images/patient/doctor-search-illustration.png') }}" alt="">
      </div>
    </div>

    <form method="GET" action="{{ route('patient.doctors') }}" class="patient-appointments-filter-panel" id="patientDoctorFilterPanel" hidden>
      <div class="patient-appointments-filter-heading">
        <strong>{{ __('patient.search.filter_title') }}</strong>
        <div>
          <a href="{{ route('patient.doctors') }}">{{ __('patient.search.clear_all') }}</a>
          <button type="button" id="patientDoctorFilterClose" aria-label="{{ __('patient.search.close_filters') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </div>
      </div>

      <input type="hidden" name="name" value="{{ $name }}">
      <input type="hidden" name="sort" value="{{ $sort }}">
      <div class="patient-appointments-filter-grid">
        <label>
          <span>{{ __('patient.search.specialty_label') }}</span>
          <select name="specialty_id">
            <option value="">{{ __('patient.search.all_specialties') }}</option>
            @foreach ($specialties as $specialty)
              <option value="{{ $specialty->specialty_id }}" @selected($selectedSpecialtyId === $specialty->specialty_id)>{{ $specialty->specialty_name }}</option>
            @endforeach
          </select>
        </label>
        <label>
          <span>{{ __('patient.search.availability_label') }}</span>
          <select name="availability">
            <option value="">{{ __('patient.search.any_availability') }}</option>
            <option value="today" @selected($availability === 'today')>{{ __('patient.search.available_today') }}</option>
            <option value="tomorrow" @selected($availability === 'tomorrow')>{{ __('patient.search.available_tomorrow') }}</option>
            <option value="scheduled" @selected($availability === 'scheduled')>{{ __('patient.search.has_schedule') }}</option>
          </select>
        </label>
        <label>
          <span>{{ __('patient.search.gender_label') }}</span>
          <select name="gender">
            <option value="">{{ __('patient.search.any_gender') }}</option>
            <option value="male" @selected($gender === 'male')>{{ __('patient.search.male') }}</option>
            <option value="female" @selected($gender === 'female')>{{ __('patient.search.female') }}</option>
          </select>
        </label>
        <label>
          <span>{{ __('patient.search.consultation_type_label') }}</span>
          <select name="consultation_type">
            <option value="">{{ __('patient.search.any_consultation_type') }}</option>
            <option value="online" @selected($consultationType === 'online')>{{ __('patient.search.online_consultation') }}</option>
            <option value="onsite" @selected($consultationType === 'onsite')>{{ __('patient.search.in_person_consultation') }}</option>
          </select>
        </label>
        <label>
          <span>{{ __('patient.search.uid_label') }}</span>
          <input type="text" name="u_id" value="{{ $uidInput }}" placeholder="{{ __('patient.search.uid_placeholder') }}">
        </label>
      </div>

      <div class="patient-appointments-filter-actions">
        <a class="patient-appointments-filter-reset" href="{{ route('patient.doctors') }}">{{ __('patient.search.reset_filters') }}</a>
        <button class="patient-appointments-filter-apply" type="submit">{{ __('patient.search.apply_filters') }}</button>
      </div>
    </form>
  </section>

  <section class="patient-doctor-discovery-results" aria-labelledby="patient-available-doctors-title">
    <div class="patient-doctor-discovery-heading">
      <div>
        <h2 id="patient-available-doctors-title">{{ __('patient.search.available_doctors') }}</h2>
        <p>{{ trans_choice('patient.search.doctor_count', $doctors->count(), ['count' => $doctors->count()]) }}</p>
      </div>
      <div class="patient-doctor-discovery-heading-actions">
        <form method="GET" action="{{ route('patient.doctors') }}" class="patient-appointments-sort-form">
          @foreach (request()->except(['sort']) as $key => $value)
            @if (is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
          @endforeach
          <label for="patientDoctorSort">{{ __('patient.search.sort_by') }}</label>
          <select id="patientDoctorSort" name="sort" onchange="this.form.submit()">
            <option value="recommended" @selected($sort === 'recommended')>{{ __('patient.search.sort_recommended') }}</option>
            <option value="rating" @selected($sort === 'rating')>{{ __('patient.search.sort_rating') }}</option>
            <option value="fee_low" @selected($sort === 'fee_low')>{{ __('patient.search.sort_fee_low') }}</option>
            <option value="fee_high" @selected($sort === 'fee_high')>{{ __('patient.search.sort_fee_high') }}</option>
            <option value="name" @selected($sort === 'name')>{{ __('patient.search.sort_name') }}</option>
          </select>
        </form>
        <a class="patient-doctor-discovery-my-appointments" href="{{ route('patient.appointments') }}">
          <i class="bi bi-calendar2-check" aria-hidden="true"></i>
          <span>{{ __('patient.appointments.title') }}</span>
        </a>
      </div>
    </div>

    <div class="patient-doctor-discovery-grid">
      @forelse ($doctors as $doctor)
        @php
          $doctorPhoto = $doctor->account?->photoUrl();
          $activeTemplates = $doctor->availabilityTemplates->where('is_active', true);
          $leaveDates = $doctor->leaveDates->pluck('leave_date')->map(fn ($date) => $date->toDateString());
          $todayAvailable = !$leaveDates->contains(today()->toDateString()) && $activeTemplates->contains('day_of_week', today()->dayOfWeek);
          $tomorrow = today()->copy()->addDay();
          $tomorrowAvailable = !$leaveDates->contains($tomorrow->toDateString()) && $activeTemplates->contains('day_of_week', $tomorrow->dayOfWeek);
          $modes = $activeTemplates->pluck('mode')->unique();
          $onsiteHospital = $activeTemplates->first(fn ($template) => $template->mode === 'onsite' && $template->hospital)?->hospital;
          $specialtyNames = $doctor->specialties->pluck('specialty_name');
        @endphp
        <article class="patient-doctor-discovery-card">
          <div class="patient-doctor-discovery-card-top">
            <span class="patient-doctor-discovery-avatar">
              @if ($doctorPhoto)
                <img src="{{ $doctorPhoto }}" alt="">
              @else
                <i class="bi bi-person-fill" aria-hidden="true"></i>
              @endif
            </span>
            <div class="patient-doctor-discovery-identity">
              <h3><a href="{{ route('patient.doctors.show', $doctor) }}">Dr. {{ $doctor->full_name }}</a></h3>
              <p>{{ $specialtyNames->implode(', ') ?: __('patient.search.general_care') }}</p>
              @if ($onsiteHospital)
                <span class="patient-doctor-discovery-hospital"><i class="bi bi-hospital" aria-hidden="true"></i>{{ $onsiteHospital->hospital_name }}</span>
              @endif
            </div>
            <form method="POST" action="{{ route('patient.doctors.favorite', $doctor) }}" class="patient-doctor-discovery-favorite-form">
              @csrf
              <button type="submit" class="patient-doctor-discovery-favorite" aria-label="{{ $favoriteDoctorIds->contains($doctor->doctor_id) ? __('patient.search.remove_favorite') : __('patient.search.add_favorite') }}">
                <i class="bi {{ $favoriteDoctorIds->contains($doctor->doctor_id) ? 'bi-heart-fill' : 'bi-heart' }}" aria-hidden="true"></i>
              </button>
            </form>
          </div>

          <div class="patient-doctor-discovery-rating">
            @if ($doctor->reviews_count > 0)
              <i class="bi bi-star-fill" aria-hidden="true"></i>
              <strong>{{ number_format((float) $doctor->reviews_avg_rating, 1) }}</strong>
              <span>({{ trans_choice('patient.search.review_count', $doctor->reviews_count, ['count' => $doctor->reviews_count]) }})</span>
            @else
              <span>{{ __('patient.search.no_ratings') }}</span>
            @endif
          </div>

          @if ($specialtyNames->isNotEmpty())
            <div class="patient-doctor-discovery-tags" aria-label="{{ __('patient.search.specialty_label') }}">
              @foreach ($specialtyNames->take(3) as $specialtyName)
                <span>{{ $specialtyName }}</span>
              @endforeach
            </div>
          @endif

          <div class="patient-doctor-discovery-meta">
            @if ($todayAvailable)
              <span class="patient-doctor-discovery-availability available"><i class="bi bi-calendar2-check" aria-hidden="true"></i>{{ __('patient.search.available_today') }}</span>
            @elseif ($tomorrowAvailable)
              <span class="patient-doctor-discovery-availability tomorrow"><i class="bi bi-calendar2-check" aria-hidden="true"></i>{{ __('patient.search.available_tomorrow') }}</span>
            @elseif ($activeTemplates->isNotEmpty())
              <span class="patient-doctor-discovery-availability"><i class="bi bi-calendar2-check" aria-hidden="true"></i>{{ __('patient.search.schedule_available') }}</span>
            @else
              <span class="patient-doctor-discovery-availability unavailable"><i class="bi bi-calendar2-x" aria-hidden="true"></i>{{ __('patient.search.no_active_schedule') }}</span>
            @endif

            <div class="patient-doctor-discovery-modes">
              @if ($modes->contains('online'))
                <span><i class="bi bi-camera-video" aria-hidden="true"></i>{{ __('patient.search.online_consultation') }}</span>
              @endif
              @if ($modes->contains('onsite'))
                <span><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ __('patient.search.in_person_consultation') }}</span>
              @endif
            </div>
          </div>

          <div class="patient-doctor-discovery-fee">
            <strong>BDT {{ number_format($doctor->consultation_fee, 2) }}</strong>
            <span>{{ __('patient.search.consultation_fee_label') }}</span>
          </div>

          <div class="patient-doctor-discovery-actions">
            <a class="patient-doctor-discovery-book" href="{{ route('patient.doctors.show', $doctor) }}">{{ __('patient.search.book_appointment') }}</a>
            <a class="patient-doctor-discovery-profile" href="{{ route('patient.doctors.show', $doctor) }}">{{ __('patient.search.view_profile') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
          </div>
        </article>
      @empty
        <div class="patient-appointments-empty-state patient-doctor-discovery-empty">
          <span><i class="bi bi-search" aria-hidden="true"></i></span>
          <h3>{{ __('patient.search.no_results_title') }}</h3>
          <p>{{ __('patient.search.no_results') }}</p>
          <a href="{{ route('patient.doctors') }}">{{ __('patient.search.clear_button') }}</a>
        </div>
      @endforelse
    </div>
  </section>

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

@push('scripts')
<script>
(() => {
  const moreButton = document.getElementById('patientDoctorMoreFilters');
  const panel = document.getElementById('patientDoctorFilterPanel');
  const closeButton = document.getElementById('patientDoctorFilterClose');
  if (!moreButton || !panel) return;

  const setOpen = (open) => {
    panel.hidden = !open;
    moreButton.setAttribute('aria-expanded', open ? 'true' : 'false');
  };

  moreButton.addEventListener('click', () => setOpen(panel.hidden));
  closeButton?.addEventListener('click', () => setOpen(false));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !panel.hidden) setOpen(false);
  });
})();
</script>
@endpush
