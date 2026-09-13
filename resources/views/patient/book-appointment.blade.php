@extends('layouts.app')
@section('title', __('patient.book.page_title', ['doctor' => $doctor->full_name]))
@section('content')
@php
  $doctorPhoto = $doctor->account?->photoUrl();
  $specialtyNames = $doctor->specialties->pluck('specialty_name');
  $profileModes = $activeTemplates->pluck('mode')->unique()->values();
  $onsiteHospitals = collect($windowsByDate)
      ->flatten(1)
      ->map(fn ($window) => $window['template']->mode === 'onsite' ? $window['template']->hospital : null)
      ->filter()
      ->unique('hospital_id')
      ->values();
  $nextAvailableDate = collect(array_keys($windowsByDate))->sort()->first();
  $ratingValue = $doctor->reviews_count > 0 ? number_format((float) $doctor->reviews_avg_rating, 1) : null;
@endphp

<div class="patient-doctor-profile-page">
  <nav class="patient-appointments-workspace-nav" aria-label="{{ __('patient.search.patient_navigation') }}">
    <a class="active" href="{{ route('patient.doctors') }}"><i class="bi bi-calendar2-check" aria-hidden="true"></i><span>{{ __('dashboard.patient.appointments_title') }}</span></a>
    <a href="{{ route('patient.prescriptions') }}"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span>{{ __('dashboard.patient.stat_prescriptions') }}</span></a>
    <a href="{{ route('patient.lab-tests') }}"><i class="bi bi-eyedropper" aria-hidden="true"></i><span>{{ __('dashboard.patient.lab_tests') }}</span></a>
    <a href="{{ route('patient.facilities') }}"><i class="bi bi-buildings" aria-hidden="true"></i><span>{{ __('dashboard.patient.hospital_services') }}</span></a>
    <a href="{{ route('patient.records') }}"><i class="bi bi-clipboard2" aria-hidden="true"></i><span>{{ __('dashboard.patient.records_short') }}</span></a>
  </nav>

  <section class="patient-doctor-profile-shell">
    <div class="patient-doctor-profile-breadcrumb-row">
      <div class="patient-doctor-profile-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('patient.dashboard') }}">{{ __('patient.search.home') }}</a>
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
        <a href="{{ route('patient.doctors') }}">{{ __('patient.book.doctors') }}</a>
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
        <span>Dr. {{ $doctor->full_name }}</span>
      </div>
      <a class="patient-doctor-profile-back" href="{{ route('patient.doctors') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i>{{ __('patient.book.back_to_search') }}</a>
    </div>

    <div class="patient-doctor-profile-grid">
      <div class="patient-doctor-profile-left">
        <section class="patient-doctor-profile-card patient-doctor-profile-summary" aria-labelledby="doctor-profile-name">
          <div class="patient-doctor-profile-summary-top">
            <div class="patient-doctor-profile-avatar" aria-hidden="true">
              @if ($doctorPhoto)
                <img src="{{ $doctorPhoto }}" alt="">
              @else
                <i class="bi bi-person-fill" aria-hidden="true"></i>
              @endif
            </div>

            <div class="patient-doctor-profile-identity">
              <div class="patient-doctor-profile-name-row">
                <div>
                  <h1 id="doctor-profile-name">Dr. {{ $doctor->full_name }}</h1>
                  @if ($doctor->account)
                    <span class="patient-doctor-profile-id">{{ $doctor->account->uidTag() }}</span>
                  @endif
                </div>
                <form method="POST" action="{{ route('patient.doctors.favorite', $doctor) }}" class="patient-doctor-profile-favorite-form">
                  @csrf
                  <button type="submit" class="patient-doctor-profile-favorite {{ $isFavorite ? 'is-favorite' : '' }}" title="{{ $isFavorite ? __('patient.search.remove_favorite') : __('patient.search.add_favorite') }}" aria-label="{{ $isFavorite ? __('patient.search.remove_favorite') : __('patient.search.add_favorite') }}">
                    <i class="bi {{ $isFavorite ? 'bi-heart-fill' : 'bi-heart' }}" aria-hidden="true"></i>
                  </button>
                </form>
              </div>

              <p class="patient-doctor-profile-specialty-line">{{ $specialtyNames->isNotEmpty() ? $specialtyNames->implode(', ') : __('patient.search.general_care') }}</p>

              <div class="patient-doctor-profile-meta-row">
                @if ($doctor->reviews_count > 0)
                  <span class="patient-doctor-profile-rating"><i class="bi bi-star-fill" aria-hidden="true"></i><strong>{{ $ratingValue }}</strong><span>{{ trans_choice('patient.search.review_count', $doctor->reviews_count, ['count' => $doctor->reviews_count]) }}</span></span>
                @else
                  <span class="patient-doctor-profile-muted"><i class="bi bi-star" aria-hidden="true"></i>{{ __('patient.book.no_ratings') }}</span>
                @endif

                @foreach ($profileModes as $mode)
                  <span class="patient-doctor-profile-mode"><i class="bi {{ $mode === 'online' ? 'bi-camera-video' : 'bi-geo-alt' }}" aria-hidden="true"></i>{{ $mode === 'online' ? __('patient.search.online_consultation') : __('patient.search.in_person_consultation') }}</span>
                @endforeach
              </div>
            </div>
          </div>

          <div class="patient-doctor-profile-fee-strip">
            <span>{{ __('patient.search.consultation_fee_label') }}</span>
            <strong>BDT {{ number_format($doctor->consultation_fee, 2) }}</strong>
          </div>
        </section>

        <section class="patient-doctor-profile-card patient-doctor-profile-about" aria-labelledby="doctor-about-title">
          <div class="patient-doctor-profile-section-heading">
            <span class="patient-doctor-profile-section-icon"><i class="bi bi-person" aria-hidden="true"></i></span>
            <div>
              <h2 id="doctor-about-title">{{ __('patient.book.about_doctor') }}</h2>
              <p>{{ __('patient.book.about_doctor_desc') }}</p>
            </div>
          </div>

          <div class="patient-doctor-profile-about-copy">
            <p>{{ $doctor->bio ?: __('patient.book.no_bio') }}</p>
          </div>

          <div class="patient-doctor-profile-divider"></div>

          <div class="patient-doctor-profile-specialties">
            <h3>{{ __('patient.book.specialties') }}</h3>
            <div class="patient-doctor-profile-specialty-chips">
              @forelse ($doctor->specialties as $specialty)
                <span>{{ $specialty->specialty_name }}</span>
              @empty
                <span>{{ __('patient.search.general_care') }}</span>
              @endforelse
            </div>
          </div>
        </section>

        @if ($onsiteHospitals->isNotEmpty())
          <section class="patient-doctor-profile-card patient-doctor-profile-locations" aria-labelledby="doctor-locations-title">
            <div class="patient-doctor-profile-section-heading compact">
              <span class="patient-doctor-profile-section-icon"><i class="bi bi-buildings" aria-hidden="true"></i></span>
              <div>
                <h2 id="doctor-locations-title">{{ __('patient.book.onsite_locations') }}</h2>
                <p>{{ __('patient.book.onsite_locations_desc') }}</p>
              </div>
            </div>
            <div class="patient-doctor-profile-location-list">
              @foreach ($onsiteHospitals as $hospital)
                <div class="patient-doctor-profile-location-item">
                  <span><i class="bi bi-hospital" aria-hidden="true"></i></span>
                  <div>
                    <strong>{{ $hospital->hospital_name }}</strong>
                    <small>{{ $hospital->city ?: $hospital->address }}</small>
                  </div>
                </div>
              @endforeach
            </div>
          </section>
        @endif
      </div>

      <aside class="patient-doctor-profile-right">
        <section class="patient-doctor-profile-card patient-doctor-booking-card" aria-labelledby="doctor-booking-title">
          <div class="patient-doctor-booking-heading">
            <span><i class="bi bi-calendar2-check" aria-hidden="true"></i></span>
            <div>
              <h2 id="doctor-booking-title">{{ __('patient.book.booking_title') }}</h2>
              <p>{{ __('patient.book.booking_desc', ['doctor' => $doctor->full_name]) }}</p>
            </div>
          </div>

          @if (empty($windowsByDate))
            <div class="patient-doctor-booking-empty">
              <span><i class="bi bi-calendar2-x" aria-hidden="true"></i></span>
              <strong>{{ __('patient.book.no_windows') }}</strong>
              <p>{{ __('patient.book.no_windows_desc') }}</p>
            </div>
          @else
            <div class="patient-doctor-booking-calendar-label"><i class="bi bi-calendar3" aria-hidden="true"></i>{{ __('patient.book.pick_date') }}</div>
            <p class="patient-doctor-booking-calendar-copy">{{ __('patient.book.pick_date_desc_short') }}</p>

            <div id="appointment-calendar" class="patient-doctor-booking-calendar"></div>

            <div id="appointment-date-detail" class="patient-doctor-booking-date-hint">
              <i class="bi bi-arrow-up-circle" aria-hidden="true"></i>
              <span>{{ __('patient.book.pick_date_hint') }}</span>
            </div>

            @php
              $calendarDates = [];
              foreach ($windowsByDate as $date => $windows) {
                  $allAlreadyBooked = true;
                  foreach ($windows as $window) {
                      if (!$window['already_booked']) {
                          $allAlreadyBooked = false;
                          break;
                      }
                  }
                  $calendarDates[] = ['date' => $date, 'already_booked' => $allAlreadyBooked];
              }
            @endphp

            @foreach ($windowsByDate as $date => $windows)
              <div class="patient-doctor-booking-date-block date-block" id="date-block-{{ $date }}" style="display:none;">
                <div class="patient-doctor-booking-date-title">
                  <span>{{ __('patient.book.available_windows') }}</span>
                  <strong>{{ \Illuminate\Support\Carbon::parse($date)->format('l, F j') }}</strong>
                </div>

                <div class="patient-doctor-booking-window-list">
                  @foreach ($windows as $window)
                    @php $template = $window['template']; @endphp
                    <article class="patient-doctor-booking-window {{ $window['already_booked'] ? 'is-booked' : '' }}">
                      <div class="patient-doctor-booking-window-main">
                        <span class="patient-doctor-booking-window-mode {{ $template->mode }}"><i class="bi {{ $template->mode === 'online' ? 'bi-camera-video' : 'bi-geo-alt' }}" aria-hidden="true"></i>{{ $template->mode === 'online' ? __('patient.book.online') : __('patient.book.onsite') }}</span>
                        <strong>{{ \Illuminate\Support\Carbon::parse($template->start_time)->format('g:i A') }} – {{ \Illuminate\Support\Carbon::parse($template->end_time)->format('g:i A') }}</strong>
                        @if ($template->mode === 'onsite' && $template->hospital)
                          <small><i class="bi bi-hospital" aria-hidden="true"></i>{{ $template->hospital->hospital_name }}</small>
                        @endif
                        @if (!$window['already_booked'])
                          <small><i class="bi bi-people" aria-hidden="true"></i>{{ __('patient.book.spots_left', ['left' => $window['spots_left'], 'max' => $template->max_patients]) }}</small>
                        @endif
                      </div>

                      <div class="patient-doctor-booking-window-action">
                        @if ($window['already_booked'])
                          <button type="button" disabled><i class="bi bi-check2" aria-hidden="true"></i>{{ __('patient.book.booked_button') }}</button>
                        @else
                          <form method="POST" action="{{ route('patient.doctors.book', $doctor) }}" data-confirm="Book this appointment with Dr. {{ $doctor->full_name }}? BDT {{ number_format($doctor->consultation_fee, 2) }} will be charged now (Cash) &mdash; refunded automatically if the doctor never marks you visited.">
                            @csrf
                            <input type="hidden" name="template_id" value="{{ $template->template_id }}">
                            <input type="hidden" name="date" value="{{ $date }}">
                            <button type="submit">{{ __('patient.book.book_button') }}</button>
                          </form>
                        @endif
                      </div>
                    </article>
                  @endforeach
                </div>
              </div>
            @endforeach

            <div class="patient-doctor-booking-note">
              <i class="bi bi-info-circle" aria-hidden="true"></i>
              <p>{{ __('patient.book.queue_note') }}</p>
            </div>

            @push('scripts')
              <script src="{{ asset('js/booking-calendar.js') }}?v={{ filemtime(public_path('js/booking-calendar.js')) }}"></script>
              <script>
                const appointmentAvailableDates = @json($calendarDates);
                renderBookingCalendar('appointment-calendar', appointmentAvailableDates, function (dateStr) {
                  document.querySelectorAll('.date-block').forEach(function (el) { el.style.display = 'none'; });
                  document.getElementById('appointment-date-detail').style.display = 'none';
                  const block = document.getElementById('date-block-' + dateStr);
                  if (block) block.style.display = 'block';
                });
              </script>
            @endpush
          @endif
        </section>

        <section class="patient-doctor-profile-card patient-doctor-quick-card" aria-labelledby="doctor-quick-title">
          <div class="patient-doctor-profile-section-heading compact">
            <span class="patient-doctor-profile-section-icon"><i class="bi bi-info-circle" aria-hidden="true"></i></span>
            <div><h2 id="doctor-quick-title">{{ __('patient.book.quick_information') }}</h2></div>
          </div>

          <dl class="patient-doctor-quick-list">
            <div><dt>{{ __('patient.search.consultation_fee_label') }}</dt><dd>BDT {{ number_format($doctor->consultation_fee, 2) }}</dd></div>
            <div><dt>{{ __('patient.book.consultation_modes') }}</dt><dd>{{ $profileModes->map(fn ($mode) => $mode === 'online' ? __('patient.book.online') : __('patient.book.onsite'))->implode(' · ') ?: '—' }}</dd></div>
            <div><dt>{{ __('patient.book.next_available') }}</dt><dd>{{ $nextAvailableDate ? \Illuminate\Support\Carbon::parse($nextAvailableDate)->format('D, M j') : '—' }}</dd></div>
            <div><dt>{{ __('patient.book.rating_summary') }}</dt><dd>{{ $doctor->reviews_count > 0 ? $ratingValue . ' / 5 · ' . $doctor->reviews_count : __('patient.book.no_ratings') }}</dd></div>
          </dl>
        </section>

        @if ($activeTemplates->isNotEmpty())
          <section class="patient-doctor-profile-card patient-doctor-waitlist-card" aria-labelledby="doctor-waitlist-title">
            <div class="patient-doctor-profile-section-heading compact">
              <span class="patient-doctor-profile-section-icon"><i class="bi bi-hourglass-split" aria-hidden="true"></i></span>
              <div>
                <h2 id="doctor-waitlist-title">{{ __('patient.book.waitlist_title') }}</h2>
                <p>{{ __('patient.book.waitlist_desc_short') }}</p>
              </div>
            </div>

            <form method="POST" action="{{ route('patient.doctors.waitlist', $doctor) }}" class="patient-doctor-waitlist-form">
              @csrf
              <label>
                <span>{{ __('patient.book.window_label') }}</span>
                <select name="template_id" required>
                  @foreach ($activeTemplates as $t)
                    <option value="{{ $t->template_id }}">
                      {{ \App\Models\DoctorAvailabilityTemplate::DAY_NAMES[$t->day_of_week] }},
                      {{ \Illuminate\Support\Carbon::parse($t->start_time)->format('g:i A') }} - {{ \Illuminate\Support\Carbon::parse($t->end_time)->format('g:i A') }}
                      ({{ $t->mode === 'online' ? __('patient.book.online') : __('patient.book.onsite') }})
                    </option>
                  @endforeach
                </select>
              </label>
              <label>
                <span>{{ __('patient.book.date_label') }}</span>
                <input type="date" name="date" min="{{ now()->toDateString() }}" required>
              </label>
              <button type="submit">{{ __('patient.book.join_waitlist') }}</button>
            </form>
          </section>
        @endif
      </aside>
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
