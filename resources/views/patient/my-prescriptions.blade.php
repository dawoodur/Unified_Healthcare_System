@extends('layouts.app')
@section('title', __('patient.prescriptions.page_title'))
@section('content')
@php
  $filterQuery = fn (string $value) => array_filter(['q' => $search, 'status' => $value === 'all' ? null : $value, 'sort' => $sort === 'recent' ? null : $sort]);
@endphp

<div class="patient-prescriptions-page">
  <nav class="patient-appointments-workspace-nav" aria-label="{{ __('patient.prescriptions.patient_navigation') }}">
    <a href="{{ route('patient.doctors') }}"><i class="bi bi-calendar2-check" aria-hidden="true"></i><span>{{ __('dashboard.patient.appointments_title') }}</span></a>
    <a class="active" href="{{ route('patient.prescriptions') }}"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span>{{ __('dashboard.patient.stat_prescriptions') }}</span></a>
    <a href="{{ route('patient.lab-tests') }}"><i class="bi bi-eyedropper" aria-hidden="true"></i><span>{{ __('dashboard.patient.lab_tests') }}</span></a>
    <a href="{{ route('patient.facilities') }}"><i class="bi bi-hospital" aria-hidden="true"></i><span>{{ __('dashboard.patient.hospital_services') }}</span></a>
    <a href="{{ route('patient.records') }}"><i class="bi bi-clipboard2" aria-hidden="true"></i><span>{{ __('dashboard.patient.records_short') }}</span></a>
  </nav>

  <section class="patient-prescriptions-hero" aria-labelledby="patient-prescriptions-title">
    <div class="patient-prescriptions-breadcrumb">
      <a href="{{ route('patient.dashboard') }}">{{ __('patient.prescriptions.home') }}</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <span>{{ __('patient.prescriptions.title') }}</span>
    </div>

    <div class="patient-prescriptions-hero-grid">
      <div class="patient-prescriptions-hero-copy">
        <h1 id="patient-prescriptions-title">{{ __('patient.prescriptions.hero_title') }}</h1>
        <p>{{ __('patient.prescriptions.hero_desc') }}</p>

        <form method="GET" action="{{ route('patient.prescriptions') }}" class="patient-prescriptions-search-form">
          <div class="patient-prescriptions-search-input">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('patient.prescriptions.search_placeholder') }}" aria-label="{{ __('patient.prescriptions.search_aria') }}">
          </div>
          @if ($status !== 'all')<input type="hidden" name="status" value="{{ $status }}">@endif
          @if ($sort !== 'recent')<input type="hidden" name="sort" value="{{ $sort }}">@endif
          <button type="submit" class="patient-prescriptions-search-button">{{ __('patient.prescriptions.search_button') }}</button>
        </form>

        <div class="patient-prescriptions-filter-row" aria-label="{{ __('patient.prescriptions.filters') }}">
          <a class="patient-prescriptions-filter-chip {{ $status === 'all' ? 'active' : '' }}" href="{{ route('patient.prescriptions', $filterQuery('all')) }}">{{ __('patient.prescriptions.filter_all') }}</a>
          <a class="patient-prescriptions-filter-chip {{ $status === 'active' ? 'active' : '' }}" href="{{ route('patient.prescriptions', $filterQuery('active')) }}">{{ __('patient.prescriptions.filter_active') }}</a>
          <a class="patient-prescriptions-filter-chip {{ $status === 'completed' ? 'active' : '' }}" href="{{ route('patient.prescriptions', $filterQuery('completed')) }}">{{ __('patient.prescriptions.filter_completed') }}</a>
          <a class="patient-prescriptions-filter-chip {{ $status === 'procedures' ? 'active' : '' }}" href="{{ route('patient.prescriptions', $filterQuery('procedures')) }}">{{ __('patient.prescriptions.filter_procedures') }}</a>
        </div>
      </div>

      <div class="patient-prescriptions-hero-visual" aria-hidden="true">
        <span class="patient-prescriptions-hero-note">{{ __('patient.prescriptions.hero_note') }}</span>
        <span class="patient-prescriptions-hero-rx"><i class="bi bi-file-earmark-medical"></i></span>
        <span class="patient-prescriptions-hero-pill"><i class="bi bi-capsule-pill"></i></span>
      </div>
    </div>
  </section>

  <div class="patient-prescriptions-content-grid">
    <section class="patient-prescriptions-list-section" aria-labelledby="patient-prescriptions-list-title">
      <div class="patient-prescriptions-section-heading">
        <div>
          <h2 id="patient-prescriptions-list-title">{{ __('patient.prescriptions.list_title') }}</h2>
          <span>{{ trans_choice('patient.prescriptions.prescription_count', $prescriptions->count(), ['count' => $prescriptions->count()]) }}</span>
        </div>

        <form method="GET" action="{{ route('patient.prescriptions') }}" class="patient-prescriptions-sort-form">
          @if ($search !== '')<input type="hidden" name="q" value="{{ $search }}">@endif
          @if ($status !== 'all')<input type="hidden" name="status" value="{{ $status }}">@endif
          <label for="patientPrescriptionSort">{{ __('patient.prescriptions.sort_by') }}</label>
          <select id="patientPrescriptionSort" name="sort" onchange="this.form.submit()">
            <option value="recent" @selected($sort === 'recent')>{{ __('patient.prescriptions.sort_recent') }}</option>
            <option value="oldest" @selected($sort === 'oldest')>{{ __('patient.prescriptions.sort_oldest') }}</option>
            <option value="doctor" @selected($sort === 'doctor')>{{ __('patient.prescriptions.sort_doctor') }}</option>
          </select>
        </form>
      </div>

      <div class="patient-prescriptions-list">
        @forelse ($prescriptions as $prescription)
          @php
            $doctorPhoto = $prescription->doctor->account?->photoUrl();
            $specialties = $prescription->doctor->specialties->pluck('specialty_name')->implode(', ');
            $hasActiveMedicine = $prescription->items->contains(function ($item) use ($prescription) {
              return !$item->duration_days || now()->lte($prescription->issued_at->copy()->addDays((int) $item->duration_days));
            });
          @endphp
          <article class="patient-prescriptions-card">
            <header class="patient-prescriptions-card-header">
              <div class="patient-prescriptions-doctor">
                <span class="patient-prescriptions-doctor-avatar">
                  @if ($doctorPhoto)
                    <img src="{{ $doctorPhoto }}" alt="">
                  @else
                    <i class="bi bi-person-fill" aria-hidden="true"></i>
                  @endif
                </span>
                <div>
                  <strong>Dr. {{ $prescription->doctor->full_name }}</strong>
                  @if ($specialties)<span>{{ $specialties }}</span>@endif
                  <small><i class="bi bi-calendar3" aria-hidden="true"></i>{{ $prescription->issued_at->format('M d, Y') }}</small>
                </div>
              </div>

              <div class="patient-prescriptions-card-status">
                @if ($hasActiveMedicine)
                  <span class="active"><i class="bi bi-check-circle-fill" aria-hidden="true"></i>{{ __('patient.prescriptions.active_course') }}</span>
                @elseif ($prescription->items->isNotEmpty())
                  <span class="completed"><i class="bi bi-check2" aria-hidden="true"></i>{{ __('patient.prescriptions.completed_course') }}</span>
                @elseif ($prescription->facilityItems->isNotEmpty())
                  <span class="procedure"><i class="bi bi-flask" aria-hidden="true"></i>{{ __('patient.prescriptions.tests_operations') }}</span>
                @endif
                <a href="{{ route('prescriptions.show', $prescription) }}">{{ __('patient.prescriptions.view_prescription') }}</a>
              </div>
            </header>

            @if ($prescription->diagnosis_notes)
              <div class="patient-prescriptions-diagnosis">
                <span>{{ __('patient.prescriptions.clinical_note') }}</span>
                <p>{{ $prescription->diagnosis_notes }}</p>
              </div>
            @endif

            @if ($prescription->items->isNotEmpty())
              <div class="patient-prescriptions-items">
                @foreach ($prescription->items as $item)
                  @php
                    $itemActive = !$item->duration_days || now()->lte($prescription->issued_at->copy()->addDays((int) $item->duration_days));
                  @endphp
                  <div class="patient-prescriptions-medicine-row">
                    <div class="patient-prescriptions-medicine-main">
                      <span class="patient-prescriptions-medicine-icon"><i class="bi bi-capsule-pill" aria-hidden="true"></i></span>
                      <div>
                        <strong>{{ $item->medicine->generic_name }}</strong>
                        <span>
                          @if ($item->medicine->brand_name){{ $item->medicine->brand_name }}@endif
                          @if ($item->medicine->strength){{ $item->medicine->brand_name ? ' · ' : '' }}{{ $item->medicine->strength }}@endif
                          @if ($item->medicine->form){{ ($item->medicine->brand_name || $item->medicine->strength) ? ' · ' : '' }}{{ $item->medicine->form }}@endif
                        </span>
                      </div>
                    </div>

                    <div class="patient-prescriptions-instructions">
                      @if ($item->dosage)<span><strong>{{ __('patient.prescriptions.dosage') }}</strong>{{ $item->dosage }}</span>@endif
                      @if ($item->frequency)<span><strong>{{ __('patient.prescriptions.frequency') }}</strong>{{ $item->frequency }}</span>@endif
                      @if ($item->duration_days)<span><strong>{{ __('patient.prescriptions.duration') }}</strong>{{ __('patient.prescriptions.days', ['count' => $item->duration_days]) }}</span>@endif
                      @if ($item->for_illness)<span><strong>{{ __('patient.prescriptions.for_illness') }}</strong>{{ $item->for_illness }}</span>@endif
                    </div>

                    <div class="patient-prescriptions-reminder-block">
                      @if ($itemActive)
                        <span class="patient-prescriptions-reminder-label"><i class="bi bi-alarm" aria-hidden="true"></i>{{ __('patient.prescriptions.reminders') }}</span>
                        <div class="patient-prescriptions-reminder-times">
                          @foreach ($item->reminderTimes as $reminder)
                            <form method="POST" action="{{ route('patient.reminders.destroy', $reminder) }}" data-confirm="{{ __('patient.prescriptions.remove_reminder_confirm') }}">
                              @csrf
                              <button type="submit" title="{{ __('patient.prescriptions.remove_reminder') }}">{{ \Illuminate\Support\Carbon::parse($reminder->reminder_time)->format('g:i A') }} <i class="bi bi-x" aria-hidden="true"></i></button>
                            </form>
                          @endforeach
                          <form method="POST" action="{{ route('patient.reminders.store', $item) }}" class="patient-prescriptions-reminder-add">
                            @csrf
                            <input type="time" name="reminder_time" required aria-label="{{ __('patient.prescriptions.add_reminder_time') }}">
                            <button type="submit" aria-label="{{ __('patient.prescriptions.add_reminder') }}"><i class="bi bi-plus-lg" aria-hidden="true"></i></button>
                          </form>
                        </div>
                      @else
                        <span class="patient-prescriptions-course-finished"><i class="bi bi-check2-circle" aria-hidden="true"></i>{{ __('patient.prescriptions.course_finished') }}</span>
                      @endif
                    </div>

                    <a class="patient-prescriptions-order-action" href="{{ route('patient.medicine', ['medicine_master_id' => $item->medicine_master_id]) }}">
                      {{ __('patient.prescriptions.compare_order') }} <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                  </div>
                  @if ($item->notes)
                    <div class="patient-prescriptions-item-note"><i class="bi bi-info-circle" aria-hidden="true"></i><span>{{ $item->notes }}</span></div>
                  @endif
                @endforeach
              </div>
            @endif

            @if ($prescription->facilityItems->isNotEmpty())
              <div class="patient-prescriptions-facilities">
                <div class="patient-prescriptions-subheading">
                  <i class="bi bi-flask" aria-hidden="true"></i>
                  <strong>{{ __('patient.prescriptions.recommended_tests_operations') }}</strong>
                </div>
                @foreach ($prescription->facilityItems as $item)
                  <div class="patient-prescriptions-facility-row">
                    <div>
                      <strong>{{ $item->facilityType->name ?? '—' }}</strong>
                      <span>{{ $item->facilityType->category->category_name ?? '—' }}</span>
                      @if ($item->notes)<small>{{ $item->notes }}</small>@endif
                    </div>
                    <a href="{{ route('patient.facilities', ['facility_type_id' => $item->facility_type_id]) }}">{{ __('patient.prescriptions.compare_book') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                  </div>
                @endforeach
              </div>
            @endif
          </article>
        @empty
          <div class="patient-prescriptions-empty-state">
            <span><i class="bi bi-file-earmark-medical" aria-hidden="true"></i></span>
            <h3>{{ __('patient.prescriptions.empty_title') }}</h3>
            <p>{{ $search !== '' || $status !== 'all' ? __('patient.prescriptions.empty_filtered') : __('patient.prescriptions.empty') }}</p>
            <a href="{{ route('patient.doctors') }}">{{ __('patient.prescriptions.book_appointment') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
          </div>
        @endforelse
      </div>
    </section>

    <aside class="patient-prescriptions-sidebar">
      <section class="patient-prescriptions-side-card patient-prescriptions-order-card">
        <div class="patient-prescriptions-side-icon"><i class="bi bi-capsule-pill" aria-hidden="true"></i></div>
        <div>
          <h2>{{ __('patient.prescriptions.order_medicines_title') }}</h2>
          <p>{{ __('patient.prescriptions.order_medicines_desc') }}</p>
        </div>
        <a class="patient-prescriptions-primary-side-action" href="{{ route('patient.medicine') }}">{{ __('patient.prescriptions.compare_pharmacy_prices') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        <div class="patient-prescriptions-side-links">
          <a href="{{ route('patient.cart') }}">{{ __('patient.prescriptions.cart') }}</a>
          <a href="{{ route('patient.orders') }}">{{ __('patient.prescriptions.my_orders') }}</a>
        </div>
      </section>

      <section class="patient-prescriptions-side-card">
        <div class="patient-prescriptions-side-icon"><i class="bi bi-alarm" aria-hidden="true"></i></div>
        <div>
          <h2>{{ __('patient.prescriptions.reminder_title') }}</h2>
          <p>{{ __('patient.prescriptions.reminder_desc') }}</p>
        </div>
        <a class="patient-prescriptions-outline-side-action" href="#patient-prescriptions-list-title">{{ __('patient.prescriptions.manage_reminders') }} <i class="bi bi-arrow-down" aria-hidden="true"></i></a>
      </section>

      <section class="patient-prescriptions-side-card">
        <div class="patient-prescriptions-side-icon"><i class="bi bi-chat-square-heart" aria-hidden="true"></i></div>
        <div>
          <h2>{{ __('patient.prescriptions.need_prescription_title') }}</h2>
          <p>{{ __('patient.prescriptions.need_prescription_desc') }}</p>
        </div>
        <a class="patient-prescriptions-outline-side-action" href="{{ route('patient.doctors') }}">{{ __('patient.prescriptions.book_appointment') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
      </section>

      <section class="patient-prescriptions-side-card" aria-labelledby="patient-prescription-orders-title">
        <div class="patient-prescriptions-side-heading">
          <h2 id="patient-prescription-orders-title">{{ __('patient.prescriptions.recent_orders') }}</h2>
          <a href="{{ route('patient.orders') }}">{{ __('patient.prescriptions.view_all') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
        @forelse ($recentOrders as $order)
          <a class="patient-prescriptions-recent-order" href="{{ route('patient.orders') }}">
            <span><i class="bi bi-bag-check" aria-hidden="true"></i></span>
            <div>
              <strong>#{{ $order->order_id }} · {{ $order->pharmacy->pharmacy_name }}</strong>
              <small>{{ $order->created_at->format('M d, Y') }}</small>
            </div>
            <em class="patient-prescriptions-order-status patient-prescriptions-order-status-{{ $order->status }}">{{ $order->statusLabel() }}</em>
          </a>
        @empty
          <div class="patient-prescriptions-side-empty">
            <span><i class="bi bi-bag" aria-hidden="true"></i></span>
            <p>{{ __('patient.prescriptions.no_orders') }}</p>
          </div>
        @endforelse
      </section>

      <section class="patient-prescriptions-support-card">
        <span class="patient-prescriptions-support-icon"><i class="bi bi-headset" aria-hidden="true"></i></span>
        <div>
          <strong>{{ __('patient.prescriptions.need_help') }}</strong>
          <p>{{ __('patient.prescriptions.support_desc') }}</p>
        </div>
        <a href="{{ route('help.index') }}">{{ __('patient.prescriptions.contact_support') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
      </section>
    </aside>
  </div>
</div>

<footer class="patient-dashboard-footer patient-prescriptions-footer">
  <div class="patient-dashboard-footer-brand">
    <span class="patient-dashboard-footer-dot"></span>
    <div><strong>{{ __('dashboard.patient.platform_name') }}</strong><small>{{ __('home.brand_tagline') }}</small></div>
  </div>
  <p>{{ __('dashboard.patient.footer_tagline') }}</p>
</footer>
@endsection
