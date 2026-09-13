@extends('layouts.app')
@section('title', auth()->user()->role === 'patient' ? __('patient.prescriptions.detail_page_title') : 'Prescription')
@section('content')
@if (auth()->user()->role === 'patient')
  @php
    $doctorPhoto = $prescription->doctor->account?->photoUrl();
    $specialties = $prescription->doctor->specialties->pluck('specialty_name')->implode(', ');
    $hasActiveMedicine = $prescription->items->contains(function ($item) use ($prescription) {
      return !$item->duration_days || now()->lte($prescription->issued_at->copy()->addDays((int) $item->duration_days));
    });
    $statusLabel = $hasActiveMedicine
      ? __('patient.prescriptions.active_course')
      : ($prescription->items->isNotEmpty()
          ? __('patient.prescriptions.completed_course')
          : __('patient.prescriptions.tests_operations'));
    $statusClass = $hasActiveMedicine
      ? 'active'
      : ($prescription->items->isNotEmpty() ? 'completed' : 'procedure');
    $appointmentType = $prescription->appointment?->appointment_type;
    $hospitalName = $prescription->appointment?->hospital?->hospital_name;
  @endphp

  <div class="patient-prescription-detail-page">
    <nav class="patient-appointments-workspace-nav" aria-label="{{ __('patient.prescriptions.patient_navigation') }}">
      <a href="{{ route('patient.doctors') }}"><i class="bi bi-calendar2-check" aria-hidden="true"></i><span>{{ __('dashboard.patient.appointments_title') }}</span></a>
      <a class="active" href="{{ route('patient.prescriptions') }}"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span>{{ __('dashboard.patient.stat_prescriptions') }}</span></a>
      <a href="{{ route('patient.lab-tests') }}"><i class="bi bi-eyedropper" aria-hidden="true"></i><span>{{ __('dashboard.patient.lab_tests') }}</span></a>
      <a href="{{ route('patient.facilities') }}"><i class="bi bi-hospital" aria-hidden="true"></i><span>{{ __('dashboard.patient.hospital_services') }}</span></a>
      <a href="{{ route('patient.records') }}"><i class="bi bi-clipboard2" aria-hidden="true"></i><span>{{ __('dashboard.patient.records_short') }}</span></a>
    </nav>

    <section class="patient-prescription-detail-hero" aria-labelledby="patient-prescription-detail-title">
      <div class="patient-prescription-detail-breadcrumb">
        <a href="{{ route('patient.dashboard') }}">{{ __('patient.prescriptions.home') }}</a>
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
        <a href="{{ route('patient.prescriptions') }}">{{ __('patient.prescriptions.title') }}</a>
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
        <span>{{ __('patient.prescriptions.detail_title') }}</span>
      </div>

      <div class="patient-prescription-detail-hero-row">
        <div>
          <h1 id="patient-prescription-detail-title">{{ __('patient.prescriptions.detail_title') }}</h1>
          <p>{{ __('patient.prescriptions.detail_hero_desc') }}</p>
        </div>
        <a class="patient-prescription-detail-back" href="{{ route('patient.prescriptions') }}">
          <i class="bi bi-arrow-left" aria-hidden="true"></i>
          <span>{{ __('patient.prescriptions.back_to_prescriptions') }}</span>
        </a>
      </div>
    </section>

    <div class="patient-prescription-detail-grid">
      <main class="patient-prescription-detail-main">
        <section class="patient-prescription-summary-card" aria-label="{{ __('patient.prescriptions.summary_aria') }}">
          <div class="patient-prescription-summary-doctor">
            <span class="patient-prescription-summary-avatar">
              @if ($doctorPhoto)
                <img src="{{ $doctorPhoto }}" alt="">
              @else
                <i class="bi bi-person-fill" aria-hidden="true"></i>
              @endif
            </span>
            <div class="patient-prescription-summary-doctor-copy">
              <h2>{{ __('patient.prescriptions.doctor_prefix') }} {{ $prescription->doctor->full_name }}</h2>
              @if ($specialties)
                <p>{{ $specialties }}</p>
              @endif
              <div class="patient-prescription-summary-context">
                @if ($hospitalName)
                  <span><i class="bi bi-hospital" aria-hidden="true"></i>{{ $hospitalName }}</span>
                @endif
                @if ($appointmentType)
                  <span><i class="bi {{ $appointmentType === 'online' ? 'bi-camera-video' : 'bi-geo-alt' }}" aria-hidden="true"></i>{{ $appointmentType === 'online' ? __('patient.prescriptions.online_consultation') : __('patient.prescriptions.onsite_consultation') }}</span>
                @endif
              </div>
              <a href="{{ route('patient.doctors.show', $prescription->doctor) }}">{{ __('patient.prescriptions.view_doctor_profile') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>
          </div>

          <div class="patient-prescription-summary-meta">
            <div class="patient-prescription-summary-meta-item">
              <span><i class="bi bi-file-earmark-text" aria-hidden="true"></i>{{ __('patient.prescriptions.issued_on') }}</span>
              <strong>{{ $prescription->issued_at->locale(app()->getLocale())->translatedFormat('M d, Y') }}</strong>
            </div>
            <div class="patient-prescription-summary-meta-item">
              <span><i class="bi bi-hash" aria-hidden="true"></i>{{ __('patient.prescriptions.prescription_id') }}</span>
              <strong>#{{ $prescription->prescription_id }}</strong>
            </div>
            @if ($prescription->appointment)
              <div class="patient-prescription-summary-meta-item">
                <span><i class="bi bi-calendar-check" aria-hidden="true"></i>{{ __('patient.prescriptions.appointment_label') }}</span>
                <strong>#{{ $prescription->appointment_id }}</strong>
              </div>
            @endif
            <div class="patient-prescription-summary-status patient-prescription-summary-status-{{ $statusClass }}">
              <span><i class="bi {{ $hasActiveMedicine ? 'bi-check-circle-fill' : 'bi-check2-circle' }}" aria-hidden="true"></i>{{ $statusLabel }}</span>
              <small>{{ __('patient.prescriptions.read_only_visit') }}</small>
            </div>
          </div>
        </section>

        @if ($prescription->items->isNotEmpty())
          <section class="patient-prescription-detail-card" id="prescription-medicines" aria-labelledby="prescription-medicines-title">
            <div class="patient-prescription-detail-card-heading">
              <div>
                <h2 id="prescription-medicines-title">{{ __('patient.prescriptions.prescribed_medicines') }}</h2>
                <span>{{ trans_choice('patient.prescriptions.item_count', $prescription->items->count(), ['count' => $prescription->items->count()]) }}</span>
              </div>
              <button type="button" class="patient-prescription-print-button" onclick="window.print()">
                <i class="bi bi-printer" aria-hidden="true"></i>{{ __('patient.prescriptions.print_prescription') }}
              </button>
            </div>

            <div class="patient-prescription-medicine-table-wrap">
              <table class="patient-prescription-medicine-table">
                <thead>
                  <tr>
                    <th>{{ __('patient.prescriptions.medicine') }}</th>
                    <th>{{ __('patient.prescriptions.for_illness') }}</th>
                    <th>{{ __('patient.prescriptions.dosage') }}</th>
                    <th>{{ __('patient.prescriptions.frequency') }}</th>
                    <th>{{ __('patient.prescriptions.duration') }}</th>
                    <th>{{ __('patient.prescriptions.instructions') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($prescription->items as $item)
                    <tr>
                      <td>
                        <div class="patient-prescription-medicine-name">
                          <span><i class="bi bi-capsule-pill" aria-hidden="true"></i></span>
                          <div>
                            <strong>{{ $item->medicine->generic_name ?? '—' }}</strong>
                            <small>
                              @if ($item->medicine?->brand_name){{ $item->medicine->brand_name }}@endif
                              @if ($item->medicine?->strength){{ $item->medicine?->brand_name ? ' · ' : '' }}{{ $item->medicine->strength }}@endif
                              @if ($item->medicine?->form){{ ($item->medicine?->brand_name || $item->medicine?->strength) ? ' · ' : '' }}{{ $item->medicine->form }}@endif
                            </small>
                          </div>
                        </div>
                      </td>
                      <td>{{ $item->for_illness ?? '—' }}</td>
                      <td>{{ $item->dosage ?? '—' }}</td>
                      <td>{{ $item->frequency ?? '—' }}</td>
                      <td>{{ $item->duration_days ? trans_choice('patient.prescriptions.duration_days', $item->duration_days, ['count' => $item->duration_days]) : '—' }}</td>
                      <td>{{ $item->notes ?? '—' }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </section>
        @endif

        @if ($prescription->facilityItems->isNotEmpty())
          <section class="patient-prescription-detail-card" aria-labelledby="prescription-procedures-title">
            <div class="patient-prescription-detail-card-heading">
              <div>
                <h2 id="prescription-procedures-title">{{ __('patient.prescriptions.recommended_tests_operations') }}</h2>
                <span>{{ trans_choice('patient.prescriptions.recommendation_count', $prescription->facilityItems->count(), ['count' => $prescription->facilityItems->count()]) }}</span>
              </div>
            </div>

            <div class="patient-prescription-procedure-list">
              @foreach ($prescription->facilityItems as $item)
                <div class="patient-prescription-procedure-row">
                  <span class="patient-prescription-procedure-icon"><i class="bi bi-flask" aria-hidden="true"></i></span>
                  <div>
                    <strong>{{ $item->facilityType->name ?? '—' }}</strong>
                    <span>{{ $item->facilityType->category->category_name ?? '—' }}</span>
                    @if ($item->notes)<small>{{ $item->notes }}</small>@endif
                  </div>
                  <a href="{{ route('patient.facilities', ['facility_type_id' => $item->facility_type_id]) }}">{{ __('patient.prescriptions.compare_book') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </div>
              @endforeach
            </div>
          </section>
        @endif

        @if ($prescription->diagnosis_notes)
          <section class="patient-prescription-note-card">
            <span><i class="bi bi-file-earmark-medical" aria-hidden="true"></i></span>
            <div>
              <h2>{{ __('patient.prescriptions.clinical_notes') }}</h2>
              <p>{{ $prescription->diagnosis_notes }}</p>
            </div>
          </section>
        @endif

        @if ($prescription->appointment?->consultationSession?->summary)
          <section class="patient-prescription-note-card patient-prescription-consultation-card">
            <span><i class="bi bi-chat-square-text" aria-hidden="true"></i></span>
            <div>
              <h2>{{ __('patient.prescriptions.consultation_summary') }}</h2>
              <small>{{ __('patient.prescriptions.consultation_summary_note') }}</small>
              <p>{{ $prescription->appointment->consultationSession->summary }}</p>
            </div>
          </section>
        @endif

        <section class="patient-prescription-care-note">
          <span><i class="bi bi-check-lg" aria-hidden="true"></i></span>
          <div>
            <strong>{{ __('patient.prescriptions.follow_instructions_title') }}</strong>
            <p>{{ __('patient.prescriptions.follow_instructions_desc') }}</p>
          </div>
        </section>
      </main>

      <aside class="patient-prescription-detail-sidebar">
        <section class="patient-prescription-side-card">
          <span class="patient-prescription-side-icon"><i class="bi bi-alarm" aria-hidden="true"></i></span>
          <div>
            <h2>{{ __('patient.prescriptions.reminder_title') }}</h2>
            <p>{{ __('patient.prescriptions.reminder_detail_desc') }}</p>
          </div>
          <a href="{{ route('patient.prescriptions') }}#patient-prescriptions-list-title">{{ __('patient.prescriptions.manage_reminders') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </section>

        @if ($prescription->items->isNotEmpty())
          <section class="patient-prescription-side-card">
            <span class="patient-prescription-side-icon"><i class="bi bi-cart3" aria-hidden="true"></i></span>
            <div>
              <h2>{{ __('patient.prescriptions.order_medicines_title') }}</h2>
              <p>{{ __('patient.prescriptions.order_medicines_desc') }}</p>
            </div>
            <a class="primary" href="{{ route('patient.medicine') }}">{{ __('patient.prescriptions.compare_order') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
          </section>
        @endif

        <section class="patient-prescription-side-card">
          <span class="patient-prescription-side-icon"><i class="bi bi-chat-square-heart" aria-hidden="true"></i></span>
          <div>
            <h2>{{ __('patient.prescriptions.detail_help_title') }}</h2>
            <p>{{ __('patient.prescriptions.detail_help_desc') }}</p>
          </div>
          <a href="{{ route('help.index') }}">{{ __('patient.prescriptions.open_help') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </section>

        <section class="patient-prescription-info-card">
          <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
          <p>{{ __('patient.prescriptions.read_only_detail') }}</p>
        </section>
      </aside>
    </div>
  </div>

  <footer class="patient-dashboard-footer patient-prescription-detail-footer">
    <div class="patient-dashboard-footer-brand">
      <span class="patient-dashboard-footer-dot"></span>
      <div><strong>{{ __('dashboard.patient.platform_name') }}</strong><small>{{ __('home.brand_tagline') }}</small></div>
    </div>
    <p>{{ __('dashboard.patient.footer_tagline') }}</p>
  </footer>
@else
  <div class="card">
    <h1>Prescription for {{ $prescription->patient->full_name }}</h1>
    <p class="muted">Issued by Dr. {{ $prescription->doctor->full_name }} on {{ $prescription->issued_at->format('D, M j Y g:i A') }}</p>
    @if ($prescription->diagnosis_notes)
      <p><strong>Diagnosis notes:</strong> {{ $prescription->diagnosis_notes }}</p>
    @endif
  </div>

  @if ($prescription->appointment?->consultationSession?->summary)
    <div class="card">
      <h2>Consultation Summary</h2>
      <p class="muted" style="font-size:0.85rem;">Auto-generated from the call's chat log and this prescription — not a transcript of the spoken conversation.</p>
      <p>{{ $prescription->appointment->consultationSession->summary }}</p>
    </div>
  @endif

  <div class="card">
    <h2>Medicines</h2>
    <table>
      <thead><tr><th>Medicine</th><th>For Illness</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th>Notes</th></tr></thead>
      <tbody>
        @foreach ($prescription->items as $item)
          <tr>
            <td>{{ $item->medicine->generic_name ?? '—' }}@if($item->medicine?->brand_name) ({{ $item->medicine->brand_name }})@endif <span class="muted">#m{{ $item->medicine_master_id }}</span></td>
            <td class="muted">{{ $item->for_illness ?? '—' }}</td>
            <td class="muted">{{ $item->dosage ?? '—' }}</td>
            <td class="muted">{{ $item->frequency ?? '—' }}</td>
            <td class="muted">{{ $item->duration_days ? $item->duration_days . ' day(s)' : '—' }}</td>
            <td class="muted">{{ $item->notes ?? '—' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  @if ($prescription->facilityItems->isNotEmpty())
    <div class="card">
      <h2>Tests &amp; Operations</h2>
      <table>
        <thead><tr><th>Test/Operation</th><th>Category</th><th>Notes</th>@if(auth()->user()->role === 'patient')<th></th>@endif</tr></thead>
        <tbody>
          @foreach ($prescription->facilityItems as $item)
            <tr>
              <td>{{ $item->facilityType->name ?? '—' }}</td>
              <td class="muted">{{ $item->facilityType->category->category_name ?? '—' }}</td>
              <td class="muted">{{ $item->notes ?? '—' }}</td>
              @if (auth()->user()->role === 'patient')
                <td><a href="{{ route('patient.facilities', ['facility_type_id' => $item->facility_type_id]) }}" class="btn" style="padding:0.3rem 0.7rem;">Compare &amp; Book</a></td>
              @endif
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif

  <p><a href="{{ url()->previous() }}">&larr; Back</a></p>
@endif
@endsection
