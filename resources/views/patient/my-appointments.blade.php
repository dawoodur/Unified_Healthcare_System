@extends('layouts.app')
@section('title', __('patient.appointments.title'))
@section('content')
@php
  $pastAppointments = $completed->filter(fn ($appointment) => in_array($appointment->status, ['completed', 'no_show'], true))->sortByDesc(fn ($appointment) => $appointment->appointment_date->toDateString() . ' ' . $appointment->appointment_time)->values();
  $cancelledAppointments = $completed->filter(fn ($appointment) => $appointment->status === 'cancelled')->sortByDesc(fn ($appointment) => $appointment->appointment_date->toDateString() . ' ' . $appointment->appointment_time)->values();
  $appointmentCalendarDates = $pending->pluck('appointment_date')->map(fn ($date) => $date->toDateString());
  $reviewedDoctorIds = \App\Models\Review::where('patient_id', auth()->user()->patient->patient_id)
    ->whereNotNull('doctor_id')
    ->pluck('doctor_id')
    ->all();
@endphp
<div class="patient-my-appointments-page">
  <nav class="patient-appointments-workspace-nav" aria-label="{{ __('dashboard.patient.quick_nav_aria') }}">
    <a class="active" href="{{ route('patient.doctors') }}"><i class="bi bi-calendar2-check" aria-hidden="true"></i><span>{{ __('dashboard.patient.appointments_title') }}</span></a>
    <a href="{{ route('patient.prescriptions') }}"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span>{{ __('dashboard.patient.stat_prescriptions') }}</span></a>
    <a href="{{ route('patient.lab-tests') }}"><i class="bi bi-eyedropper" aria-hidden="true"></i><span>{{ __('dashboard.patient.lab_tests') }}</span></a>
    <a href="{{ route('patient.facilities') }}"><i class="bi bi-buildings" aria-hidden="true"></i><span>{{ __('dashboard.patient.hospital_services') }}</span></a>
    <a href="{{ route('patient.records') }}"><i class="bi bi-clipboard" aria-hidden="true"></i><span>{{ __('dashboard.patient.records_short') }}</span></a>
  </nav>

  <section class="patient-my-appointments-header">
    <div>
      <nav class="patient-my-appointments-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('patient.dashboard') }}">{{ __('patient.search.home') }}</a>
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
        <a href="{{ route('patient.doctors') }}">{{ __('dashboard.patient.appointments_title') }}</a>
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
        <span>{{ __('patient.appointments.title') }}</span>
      </nav>
      <h1>{{ __('patient.appointments.title') }}</h1>
      <p>{{ __('patient.appointments.manage_intro') }}</p>
    </div>
    <a class="patient-my-appointments-new" href="{{ route('patient.doctors') }}">
      <i class="bi bi-calendar2-plus" aria-hidden="true"></i>
      <span>{{ __('patient.appointments.book_new') }}</span>
    </a>
  </section>

  <div class="patient-my-appointments-layout">
    <section class="patient-my-appointments-main-card">
      <div class="patient-my-appointments-tabs" role="tablist" aria-label="{{ __('patient.appointments.title') }}">
        <button type="button" class="active" role="tab" aria-selected="true" data-appointment-tab="upcoming">
          <i class="bi bi-calendar2-check" aria-hidden="true"></i>
          <span>{{ __('patient.appointments.upcoming') }}</span>
          <strong>{{ $pending->count() }}</strong>
        </button>
        <button type="button" role="tab" aria-selected="false" data-appointment-tab="past">
          <i class="bi bi-clock-history" aria-hidden="true"></i>
          <span>{{ __('patient.appointments.past') }}</span>
          <strong>{{ $pastAppointments->count() }}</strong>
        </button>
        <button type="button" role="tab" aria-selected="false" data-appointment-tab="cancelled">
          <i class="bi bi-x-circle" aria-hidden="true"></i>
          <span>{{ __('patient.appointments.cancelled_tab') }}</span>
          <strong>{{ $cancelledAppointments->count() }}</strong>
        </button>
      </div>

      <div class="patient-my-appointments-panel" data-appointment-panel="upcoming">
        @forelse ($pending as $a)
          <article class="patient-my-appointments-card">
            <div class="patient-my-appointments-date-block">
              <span>{{ strtoupper($a->appointment_date->format('M')) }}</span>
              <strong>{{ $a->appointment_date->format('d') }}</strong>
              <small>{{ $a->appointment_date->format('D') }}</small>
            </div>

            <div class="patient-my-appointments-doctor">
              <span class="patient-my-appointments-doctor-icon"><i class="bi bi-person-fill" aria-hidden="true"></i></span>
              <div>
                <h2>Dr. {{ $a->doctor->full_name }}</h2>
                <div class="patient-my-appointments-mode-row">
                  <span class="patient-my-appointments-mode {{ $a->appointment_type === 'online' ? 'online' : 'onsite' }}">
                    <i class="bi {{ $a->appointment_type === 'online' ? 'bi-camera-video' : 'bi-geo-alt' }}" aria-hidden="true"></i>
                    {{ $a->appointment_type === 'online' ? __('patient.book.online') : __('patient.book.onsite') }}
                  </span>
                  @if ($a->appointment_type === 'onsite' && $a->hospital)
                    <span class="patient-my-appointments-hospital"><i class="bi bi-building" aria-hidden="true"></i>{{ $a->hospital->hospital_name }}</span>
                  @endif
                </div>
              </div>
            </div>

            <div class="patient-my-appointments-details">
              <div><i class="bi bi-clock" aria-hidden="true"></i><span>{{ __('patient.appointments.visiting_window') }}</span><strong>{{ $a->timeRangeLabel() }}</strong></div>
              <div><i class="bi bi-hash" aria-hidden="true"></i><span>{{ __('patient.appointments.queue_serial') }}</span><strong>#{{ $a->serial_number }}</strong></div>
              @if ($a->payment)
                <div><i class="bi bi-credit-card" aria-hidden="true"></i><span>{{ __('patient.appointments.payment') }}</span><strong>BDT {{ number_format($a->payment->amount, 2) }}</strong></div>
              @endif
              @if ($a->appointment_date->isToday() && $queueStatusByDoctor->has($a->doctor_id))
                <div class="patient-my-appointments-now-serving"><i class="bi bi-people" aria-hidden="true"></i><span>{{ __('patient.appointments.now_serving', ['number' => $queueStatusByDoctor[$a->doctor_id]->current_serial]) }}</span></div>
              @endif
            </div>

            <div class="patient-my-appointments-actions">
              <span class="patient-my-appointments-status {{ $a->status }}">{{ $a->statusLabel() }}</span>
              @if ($a->appointment_type === 'online')
                @if ($a->isJoinableNow())
                  <a class="patient-my-appointments-primary-action" href="{{ route('consultation.show', $a) }}"><i class="bi bi-camera-video" aria-hidden="true"></i>{{ __('patient.appointments.join_video') }}</a>
                @else
                  <button type="button" class="patient-my-appointments-primary-action" disabled><i class="bi bi-camera-video" aria-hidden="true"></i>{{ __('patient.appointments.join_video') }}</button>
                  <small>{{ __('patient.appointments.opens_at', ['time' => $a->timeRangeLabel()]) }}</small>
                @endif
              @endif
              <a class="patient-my-appointments-secondary-action" href="{{ route('patient.doctors.show', $a->doctor) }}">{{ __('patient.appointments.view_doctor') }}</a>
              <form method="POST" action="{{ route('patient.appointments.cancel', $a) }}" data-confirm="Cancel this appointment with Dr. {{ $a->doctor->full_name }}? {{ $a->payment && $a->payment->status === 'completed' ? 'Your payment will be refunded.' : '' }}">
                @csrf
                <button type="submit" class="patient-my-appointments-cancel-action">{{ __('patient.appointments.cancel') }}</button>
              </form>
            </div>
          </article>
        @empty
          <div class="patient-my-appointments-empty">
            <span><i class="bi bi-calendar2-plus" aria-hidden="true"></i></span>
            <h2>{{ __('patient.appointments.no_upcoming_title') }}</h2>
            <p>{{ __('patient.appointments.no_pending') }}</p>
            <a href="{{ route('patient.doctors') }}">{{ __('patient.appointments.find_doctor') }}</a>
          </div>
        @endforelse
      </div>

      <div class="patient-my-appointments-panel" data-appointment-panel="past" hidden>
        @forelse ($pastAppointments as $a)
          <article class="patient-my-appointments-card patient-my-appointments-card-history">
            <div class="patient-my-appointments-date-block">
              <span>{{ strtoupper($a->appointment_date->format('M')) }}</span>
              <strong>{{ $a->appointment_date->format('d') }}</strong>
              <small>{{ $a->appointment_date->format('Y') }}</small>
            </div>
            <div class="patient-my-appointments-doctor">
              <span class="patient-my-appointments-doctor-icon"><i class="bi bi-person-fill" aria-hidden="true"></i></span>
              <div>
                <h2>Dr. {{ $a->doctor->full_name }}</h2>
                <div class="patient-my-appointments-mode-row">
                  <span class="patient-my-appointments-mode {{ $a->appointment_type === 'online' ? 'online' : 'onsite' }}">
                    <i class="bi {{ $a->appointment_type === 'online' ? 'bi-camera-video' : 'bi-geo-alt' }}" aria-hidden="true"></i>
                    {{ $a->appointment_type === 'online' ? __('patient.book.online') : __('patient.book.onsite') }}
                  </span>
                  @if ($a->appointment_type === 'onsite' && $a->hospital)
                    <span class="patient-my-appointments-hospital">{{ $a->hospital->hospital_name }}</span>
                  @endif
                </div>
              </div>
            </div>
            <div class="patient-my-appointments-details">
              <div><i class="bi bi-clock" aria-hidden="true"></i><span>{{ __('patient.appointments.visiting_window') }}</span><strong>{{ $a->timeRangeLabel() }}</strong></div>
              <div><i class="bi bi-hash" aria-hidden="true"></i><span>{{ __('patient.appointments.queue_serial') }}</span><strong>#{{ $a->serial_number }}</strong></div>
              @if ($a->status === 'no_show')
                <div class="patient-my-appointments-history-note"><i class="bi bi-info-circle" aria-hidden="true"></i><span>{{ __('patient.appointments.not_marked_visited') }}</span></div>
              @endif
            </div>
            <div class="patient-my-appointments-actions">
              <span class="patient-my-appointments-status {{ $a->status }}">{{ $a->statusLabel() }}</span>
              @if ($a->status === 'completed')
                @if (in_array($a->doctor_id, $reviewedDoctorIds, true))
                  <button
                    type="button"
                    class="patient-my-appointments-primary-action"
                    disabled
                    aria-disabled="true"
                    style="opacity: .45; cursor: not-allowed; filter: grayscale(1);"
                  >
                    <i class="bi bi-star-fill" aria-hidden="true"></i>
                    {{ __('patient.reviews.reviewed') }}
                  </button>
                @else
                  <button
                    type="button"
                    class="patient-my-appointments-primary-action"
                    data-review-doctor
                    data-doctor-id="{{ $a->doctor_id }}"
                    data-doctor-name="Dr. {{ $a->doctor->full_name }}"
                  >
                    <i class="bi bi-star" aria-hidden="true"></i>
                    {{ __('patient.reviews.rate_now') }}
                  </button>
                @endif
              @endif
              <a class="patient-my-appointments-secondary-action" href="{{ route('patient.doctors.show', $a->doctor) }}">{{ __('patient.appointments.view_doctor') }}</a>
            </div>
          </article>
        @empty
          <div class="patient-my-appointments-empty">
            <span><i class="bi bi-clock-history" aria-hidden="true"></i></span>
            <h2>{{ __('patient.appointments.no_past_title') }}</h2>
            <p>{{ __('patient.appointments.no_completed') }}</p>
          </div>
        @endforelse
      </div>

      <div class="patient-my-appointments-panel" data-appointment-panel="cancelled" hidden>
        @forelse ($cancelledAppointments as $a)
          <article class="patient-my-appointments-card patient-my-appointments-card-history">
            <div class="patient-my-appointments-date-block muted-state">
              <span>{{ strtoupper($a->appointment_date->format('M')) }}</span>
              <strong>{{ $a->appointment_date->format('d') }}</strong>
              <small>{{ $a->appointment_date->format('Y') }}</small>
            </div>
            <div class="patient-my-appointments-doctor">
              <span class="patient-my-appointments-doctor-icon muted-state"><i class="bi bi-person-fill" aria-hidden="true"></i></span>
              <div>
                <h2>Dr. {{ $a->doctor->full_name }}</h2>
                <div class="patient-my-appointments-mode-row">
                  <span class="patient-my-appointments-mode {{ $a->appointment_type === 'online' ? 'online' : 'onsite' }}">
                    {{ $a->appointment_type === 'online' ? __('patient.book.online') : __('patient.book.onsite') }}
                  </span>
                </div>
              </div>
            </div>
            <div class="patient-my-appointments-details">
              <div><i class="bi bi-clock" aria-hidden="true"></i><span>{{ __('patient.appointments.visiting_window') }}</span><strong>{{ $a->timeRangeLabel() }}</strong></div>
              @if ($a->payment)
                <div><i class="bi bi-credit-card" aria-hidden="true"></i><span>{{ __('patient.appointments.payment') }}</span><strong>{{ __('statuses.payment.' . $a->payment->status) }}</strong></div>
              @endif
            </div>
            <div class="patient-my-appointments-actions">
              <span class="patient-my-appointments-status cancelled">{{ $a->statusLabel() }}</span>
              <a class="patient-my-appointments-secondary-action" href="{{ route('patient.doctors.show', $a->doctor) }}">{{ __('patient.appointments.view_doctor') }}</a>
            </div>
          </article>
        @empty
          <div class="patient-my-appointments-empty">
            <span><i class="bi bi-x-circle" aria-hidden="true"></i></span>
            <h2>{{ __('patient.appointments.no_cancelled_title') }}</h2>
            <p>{{ __('patient.appointments.no_cancelled') }}</p>
          </div>
        @endforelse
      </div>
    </section>

    <aside class="patient-my-appointments-sidebar">
      <section class="patient-my-appointments-side-card patient-my-appointments-book-card">
        <span class="patient-my-appointments-side-icon"><i class="bi bi-calendar2-plus" aria-hidden="true"></i></span>
        <div>
          <h2>{{ __('patient.appointments.book_new') }}</h2>
          <p>{{ __('patient.appointments.book_new_desc') }}</p>
        </div>
        <a href="{{ route('patient.doctors') }}">{{ __('patient.appointments.find_doctor') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
      </section>

      <section class="patient-my-appointments-side-card patient-my-appointments-calendar">
        <div class="patient-my-appointments-side-heading">
          <div>
            <span><i class="bi bi-calendar3" aria-hidden="true"></i></span>
            <div>
              <h2>{{ __('patient.appointments.calendar_title') }}</h2>
              <p>{{ __('patient.appointments.calendar_desc') }}</p>
            </div>
          </div>
        </div>
        @include('partials.mini-calendar', ['busyDates' => $appointmentCalendarDates])
      </section>

      <section class="patient-my-appointments-side-card">
        <div class="patient-my-appointments-side-heading">
          <div>
            <span><i class="bi bi-hourglass-split" aria-hidden="true"></i></span>
            <div>
              <h2>{{ __('patient.appointments.my_waitlist') }}</h2>
              <p>{{ __('patient.appointments.waitlist_desc_short') }}</p>
            </div>
          </div>
        </div>
        @if ($waitlist->isEmpty())
          <div class="patient-my-appointments-waitlist-empty">{{ __('patient.appointments.no_waitlist') }}</div>
        @else
          <div class="patient-my-appointments-waitlist-list">
            @foreach ($waitlist as $w)
              <article>
                <div>
                  <strong>Dr. {{ $w->doctor->full_name }}</strong>
                  <span>{{ $w->requested_date->format('D, M j Y') }}</span>
                  <small>{{ \Illuminate\Support\Carbon::parse($w->template->start_time)->format('g:i A') }} - {{ \Illuminate\Support\Carbon::parse($w->template->end_time)->format('g:i A') }}</small>
                </div>
                <form method="POST" action="{{ route('patient.waitlist.leave', $w) }}" data-confirm="Leave this waitlist?">
                  @csrf
                  <button type="submit">{{ __('patient.appointments.leave') }}</button>
                </form>
              </article>
            @endforeach
          </div>
        @endif
      </section>

      <section class="patient-my-appointments-side-card patient-my-appointments-info-card">
        <div class="patient-my-appointments-side-heading">
          <div>
            <span><i class="bi bi-info-circle" aria-hidden="true"></i></span>
            <div><h2>{{ __('patient.appointments.appointment_info') }}</h2></div>
          </div>
        </div>
        <ul>
          <li><i class="bi bi-check2" aria-hidden="true"></i><span>{{ __('patient.appointments.info_queue') }}</span></li>
          <li><i class="bi bi-check2" aria-hidden="true"></i><span>{{ __('patient.appointments.info_video') }}</span></li>
          <li><i class="bi bi-check2" aria-hidden="true"></i><span>{{ __('patient.appointments.info_cancel') }}</span></li>
        </ul>
      </section>
    </aside>
  </div>

  <dialog class="patient-appointment-review-dialog" id="patientAppointmentReviewDialog">
    <div class="patient-appointment-review-modal">
      <header class="patient-appointment-review-header">
        <div>
          <span class="patient-appointment-review-icon"><i class="bi bi-star" aria-hidden="true"></i></span>
          <div>
            <small>{{ __('patient.reviews.type_doctor') }}</small>
            <h2 id="patientAppointmentReviewTitle">{{ __('patient.reviews.rate_now') }}</h2>
          </div>
        </div>
        <button type="button" class="patient-appointment-review-close" data-review-close aria-label="Close">
          <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
      </header>

      <form method="POST" action="{{ route('patient.reviews.rate.store') }}" class="patient-appointment-review-form">
        @csrf
        <input type="hidden" name="type" value="doctor">
        <input type="hidden" name="id" id="patientAppointmentReviewDoctorId">

        <div class="patient-appointment-review-field">
          <label>{{ __('patient.reviews.rating_label') }}</label>
          <div class="patient-appointment-review-stars" role="radiogroup" aria-label="{{ __('patient.reviews.rating_label') }}">
            @for ($rating = 5; $rating >= 1; $rating--)
              <input
                type="radio"
                id="appointment-review-rating-{{ $rating }}"
                name="rating"
                value="{{ $rating }}"
                required
              >
              <label for="appointment-review-rating-{{ $rating }}" aria-label="{{ $rating }} / 5">
                <i class="bi bi-star-fill" aria-hidden="true"></i>
              </label>
            @endfor
          </div>
        </div>

        <div class="patient-appointment-review-field">
          <label for="patientAppointmentReviewComment">{{ __('patient.reviews.comment_label') }}</label>
          <textarea
            id="patientAppointmentReviewComment"
            name="comment"
            rows="4"
            maxlength="500"
            placeholder="{{ __('patient.reviews.comment_label') }}"
          ></textarea>
        </div>

        <p class="patient-appointment-review-note">
          <i class="bi bi-shield-check" aria-hidden="true"></i>
          {{ __('patient.reviews.rate_desc', ['type' => __('patient.reviews.type_doctor')]) }}
        </p>

        <footer class="patient-appointment-review-actions">
          <button type="button" class="patient-appointment-review-cancel" data-review-close>
            Cancel
          </button>
          <button type="submit" class="patient-appointment-review-submit">
            <i class="bi bi-check2" aria-hidden="true"></i>
            {{ __('patient.reviews.submit_rating') }}
          </button>
        </footer>
      </form>
    </div>
  </dialog>

  <style>
    .patient-appointment-review-dialog {
      width: min(470px, calc(100% - 32px));
      max-width: none;
      padding: 0;
      border: 0;
      border-radius: 16px;
      background: transparent;
      box-shadow: 0 24px 70px rgba(9, 42, 65, 0.22);
    }

    .patient-appointment-review-dialog::backdrop {
      background: rgba(8, 31, 48, 0.48);
      backdrop-filter: blur(2px);
    }

    .patient-appointment-review-modal {
      overflow: hidden;
      border: 1px solid #dce8ed;
      border-radius: 16px;
      background: #ffffff;
      color: #17354d;
    }

    .patient-appointment-review-header {
      min-height: 72px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      padding: 14px 16px;
      border-bottom: 1px solid #e3ecef;
      background: #fbfefe;
    }

    .patient-appointment-review-header > div {
      min-width: 0;
      display: flex;
      align-items: center;
      gap: 11px;
    }

    .patient-appointment-review-icon {
      width: 40px;
      height: 40px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex: 0 0 40px;
      border-radius: 10px;
      color: #078e8b;
      background: #e6f6f4;
      font-size: 1rem;
    }

    .patient-appointment-review-header small {
      display: block;
      color: #078582;
      font-size: 0.52rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.045em;
    }

    .patient-appointment-review-header h2 {
      margin: 2px 0 0;
      color: #102f48;
      font-size: 0.86rem;
      font-weight: 800;
    }

    .patient-appointment-review-close {
      width: 32px;
      height: 32px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0;
      border: 0;
      border-radius: 8px;
      color: #708698;
      background: transparent;
      box-shadow: none;
    }

    .patient-appointment-review-close:hover {
      background: #eef5f6;
      color: #173851;
      transform: none;
    }

    .patient-appointment-review-form {
      display: grid;
    }

    .patient-appointment-review-field {
      display: grid;
      gap: 8px;
      padding: 15px 16px;
      border-bottom: 1px solid #edf1f3;
    }

    .patient-appointment-review-field > label {
      color: #173851;
      font-size: 0.62rem;
      font-weight: 800;
    }

    .patient-appointment-review-stars {
      display: flex;
      flex-direction: row-reverse;
      justify-content: flex-end;
      gap: 5px;
    }

    .patient-appointment-review-stars input {
      position: absolute;
      opacity: 0;
      pointer-events: none;
    }

    .patient-appointment-review-stars label {
      cursor: pointer;
      color: #d5e0e4;
      font-size: 1.55rem;
      line-height: 1;
      transition: color 0.15s ease, transform 0.15s ease;
    }

    .patient-appointment-review-stars label:hover,
    .patient-appointment-review-stars label:hover ~ label,
    .patient-appointment-review-stars input:checked ~ label {
      color: #e3a429;
    }

    .patient-appointment-review-stars label:hover {
      transform: translateY(-1px);
    }

    .patient-appointment-review-field textarea {
      width: 100%;
      min-height: 92px;
      padding: 10px 11px;
      resize: vertical;
      border: 1px solid #d7e5e9;
      border-radius: 9px;
      color: #17354d;
      background: #ffffff;
      font: inherit;
      font-size: 0.64rem;
      line-height: 1.5;
      outline: none;
      box-shadow: none;
    }

    .patient-appointment-review-field textarea:focus {
      border-color: #65c0c2;
      box-shadow: 0 0 0 3px rgba(10, 157, 154, 0.08);
    }

    .patient-appointment-review-note {
      display: grid;
      grid-template-columns: 16px minmax(0, 1fr);
      gap: 7px;
      margin: 0;
      padding: 11px 16px;
      color: #6f8597;
      background: #f7fafb;
      font-size: 0.56rem;
      line-height: 1.45;
    }

    .patient-appointment-review-note i {
      color: #078e8b;
      margin-top: 1px;
    }

    .patient-appointment-review-actions {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: 8px;
      padding: 13px 16px 15px;
    }

    .patient-appointment-review-cancel,
    .patient-appointment-review-submit {
      min-height: 38px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 7px;
      padding: 0 14px;
      border-radius: 8px;
      font-size: 0.59rem;
      font-weight: 800;
      box-shadow: none;
    }

    .patient-appointment-review-cancel {
      border: 1px solid #d4e3e8;
      color: #567185;
      background: #ffffff;
    }

    .patient-appointment-review-submit {
      min-width: 126px;
      border: 1px solid #0a9d9a;
      color: #ffffff;
      background: #0a9d9a;
    }

    .patient-appointment-review-cancel:hover,
    .patient-appointment-review-submit:hover {
      transform: none;
    }

    .patient-appointment-review-submit:hover {
      color: #ffffff;
      background: #087f7c;
      border-color: #087f7c;
    }

    @media (max-width: 520px) {
      .patient-appointment-review-actions {
        align-items: stretch;
        flex-direction: column-reverse;
      }

      .patient-appointment-review-cancel,
      .patient-appointment-review-submit {
        width: 100%;
      }
    }
  </style>

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
  const tabs = Array.from(document.querySelectorAll('[data-appointment-tab]'));
  const panels = Array.from(document.querySelectorAll('[data-appointment-panel]'));
  if (!tabs.length || !panels.length) return;

  const activate = (name) => {
    tabs.forEach((tab) => {
      const active = tab.dataset.appointmentTab === name;
      tab.classList.toggle('active', active);
      tab.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    panels.forEach((panel) => {
      panel.hidden = panel.dataset.appointmentPanel !== name;
    });
  };

  tabs.forEach((tab) => tab.addEventListener('click', () => activate(tab.dataset.appointmentTab)));

  const reviewDialog = document.getElementById('patientAppointmentReviewDialog');
  const reviewDoctorId = document.getElementById('patientAppointmentReviewDoctorId');
  const reviewTitle = document.getElementById('patientAppointmentReviewTitle');
  const reviewComment = document.getElementById('patientAppointmentReviewComment');

  document.querySelectorAll('[data-review-doctor]').forEach((button) => {
    button.addEventListener('click', () => {
      if (!reviewDialog || !reviewDoctorId || !reviewTitle) return;

      reviewDoctorId.value = button.dataset.doctorId || '';
      reviewTitle.textContent = button.dataset.doctorName || @json(__('patient.reviews.rate_now'));

      reviewDialog.querySelectorAll('input[name="rating"]').forEach((input) => {
        input.checked = false;
      });

      if (reviewComment) reviewComment.value = '';
      reviewDialog.showModal();
    });
  });

  document.querySelectorAll('[data-review-close]').forEach((button) => {
    button.addEventListener('click', () => reviewDialog?.close());
  });

  reviewDialog?.addEventListener('click', (event) => {
    const rect = reviewDialog.getBoundingClientRect();
    const clickedBackdrop =
      event.clientX < rect.left ||
      event.clientX > rect.right ||
      event.clientY < rect.top ||
      event.clientY > rect.bottom;

    if (clickedBackdrop) reviewDialog.close();
  });
})();
</script>
@endpush
