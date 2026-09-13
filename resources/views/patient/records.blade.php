@extends('layouts.app')
@section('title', __('patient.records.page_title'))
@section('content')
@php
  $recordIcon = fn (string $type) => match ($type) {
      'prescription' => 'bi-file-earmark-medical',
      'lab_result' => 'bi-flask',
      'diagnosis_note' => 'bi-clipboard2-pulse',
      default => 'bi-file-earmark-text',
  };
  $recordFilterUrl = fn (?string $type = null) => route('patient.records', array_filter([
      'type' => $type,
      'q' => $searchQuery !== '' ? $searchQuery : null,
      'sort' => $sort !== 'recent' ? $sort : null,
  ]));
  $healthValue = fn ($value) => filled($value) ? $value : __('patient.records.not_recorded');
@endphp

<div class="patient-records-page">
  <nav class="patient-appointments-workspace-nav" aria-label="{{ __('patient.records.patient_navigation') }}">
    <a href="{{ route('patient.doctors') }}"><i class="bi bi-calendar2-check" aria-hidden="true"></i><span>{{ __('dashboard.patient.appointments_title') }}</span></a>
    <a href="{{ route('patient.prescriptions') }}"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span>{{ __('dashboard.patient.stat_prescriptions') }}</span></a>
    <a href="{{ route('patient.lab-tests') }}"><i class="bi bi-eyedropper" aria-hidden="true"></i><span>{{ __('dashboard.patient.lab_tests') }}</span></a>
    <a href="{{ route('patient.facilities') }}"><i class="bi bi-hospital" aria-hidden="true"></i><span>{{ __('dashboard.patient.hospital_services') }}</span></a>
    <a class="active" href="{{ route('patient.records') }}"><i class="bi bi-clipboard2" aria-hidden="true"></i><span>{{ __('dashboard.patient.records_short') }}</span></a>
  </nav>

  <section class="patient-records-hero" aria-labelledby="patient-records-title">
    <div class="patient-records-breadcrumb">
      <a href="{{ route('patient.dashboard') }}">{{ __('patient.records.home') }}</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <span>{{ __('patient.records.title') }}</span>
    </div>

    <div class="patient-records-hero-grid">
      <div class="patient-records-hero-copy">
        <h1 id="patient-records-title">{{ __('patient.records.hero_title') }}</h1>
        <p>{{ __('patient.records.hero_desc') }}</p>

        <form method="GET" action="{{ route('patient.records') }}" class="patient-records-search-form">
          <div class="patient-records-search-input">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" name="q" value="{{ $searchQuery }}" placeholder="{{ __('patient.records.search_placeholder') }}" aria-label="{{ __('patient.records.search_aria') }}">
          </div>
          @if ($recordType !== '')<input type="hidden" name="type" value="{{ $recordType }}">@endif
          <button type="submit" class="patient-records-search-button">{{ __('patient.records.search_button') }}</button>
        </form>

        <div class="patient-records-filter-row" aria-label="{{ __('patient.records.filter_aria') }}">
          <a class="patient-records-filter-chip {{ $recordType === '' ? 'active' : '' }}" href="{{ $recordFilterUrl() }}">{{ __('patient.records.all_records') }}</a>
          <a class="patient-records-filter-chip {{ $recordType === 'prescription' ? 'active' : '' }}" href="{{ $recordFilterUrl('prescription') }}">{{ __('patient.records.prescriptions_filter') }}</a>
          <a class="patient-records-filter-chip {{ $recordType === 'lab_result' ? 'active' : '' }}" href="{{ $recordFilterUrl('lab_result') }}">{{ __('patient.records.lab_results_filter') }}</a>
          <a class="patient-records-filter-chip {{ $recordType === 'diagnosis_note' ? 'active' : '' }}" href="{{ $recordFilterUrl('diagnosis_note') }}">{{ __('patient.records.diagnosis_notes_filter') }}</a>
          <a class="patient-records-filter-chip {{ $recordType === 'uploaded_document' ? 'active' : '' }}" href="{{ $recordFilterUrl('uploaded_document') }}">{{ __('patient.records.uploaded_documents_filter') }}</a>
          <button type="button" class="patient-records-filter-chip patient-records-health-filter" data-bs-toggle="modal" data-bs-target="#patientHealthProfileModal">
            <i class="bi bi-person-heart" aria-hidden="true"></i>{{ __('patient.records.personal_health_info') }}
          </button>
        </div>
      </div>

      <div class="patient-records-hero-visual" aria-hidden="true">
        <span class="patient-records-hero-note">{{ __('patient.records.hero_note') }}</span>
        <span class="patient-records-folder"><i class="bi bi-folder2-open"></i></span>
        <span class="patient-records-medical-sheet"><i class="bi bi-file-earmark-medical"></i></span>
        <span class="patient-records-shield"><i class="bi bi-shield-check"></i></span>
      </div>
    </div>
  </section>

  <div class="patient-records-content-grid">
    <section class="patient-records-main-card" aria-labelledby="patient-records-list-title">
      <div class="patient-records-section-heading">
        <div>
          <h2 id="patient-records-list-title">{{ __('patient.records.your_records') }}</h2>
          <span>{{ trans_choice('patient.records.record_count', $records->count(), ['count' => $records->count()]) }}</span>
        </div>
        <div class="patient-records-heading-actions">
          <button type="button" id="print-record-btn" class="patient-records-outline-button"><i class="bi bi-printer" aria-hidden="true"></i>{{ __('patient.records.print_button') }}</button>
          <form method="GET" action="{{ route('patient.records') }}" class="patient-records-sort-form">
            @if ($searchQuery !== '')<input type="hidden" name="q" value="{{ $searchQuery }}">@endif
            @if ($recordType !== '')<input type="hidden" name="type" value="{{ $recordType }}">@endif
            <label for="patient-records-sort">{{ __('patient.records.sort_by') }}</label>
            <select id="patient-records-sort" name="sort" onchange="this.form.submit()">
              <option value="recent" @selected($sort === 'recent')>{{ __('patient.records.most_recent') }}</option>
              <option value="oldest" @selected($sort === 'oldest')>{{ __('patient.records.oldest_first') }}</option>
            </select>
          </form>
        </div>
      </div>

      <div class="patient-records-list">
        @forelse ($records as $record)
          <article class="patient-records-record-row">
            <span class="patient-records-record-icon"><i class="bi {{ $recordIcon($record->record_type) }}" aria-hidden="true"></i></span>
            <div class="patient-records-record-copy">
              <h3>{{ $record->description ?: $record->typeLabel() }}</h3>
              <span>{{ $record->typeLabel() }}</span>
              <small>{{ $record->created_at->format('M d, Y · g:i A') }}</small>
            </div>
            <div class="patient-records-record-actions">
              @if ($record->record_type === 'prescription' && $record->reference_id)
                <a class="patient-records-outline-button" href="{{ route('prescriptions.show', $record->reference_id) }}">{{ __('patient.records.view') }}</a>
              @endif
              @if ($record->file_path)
                <a class="patient-records-primary-button" href="{{ route('records.download', $record) }}"><i class="bi bi-download" aria-hidden="true"></i>{{ __('patient.records.download') }}</a>
              @elseif (!($record->record_type === 'prescription' && $record->reference_id))
                <span class="patient-records-no-file">{{ __('patient.records.no_file') }}</span>
              @endif
            </div>
          </article>
        @empty
          <div class="patient-records-empty-state">
            <span><i class="bi bi-folder2-open" aria-hidden="true"></i></span>
            <h3>{{ __('patient.records.no_records_found') }}</h3>
            <p>{{ __('patient.records.no_records_found_desc') }}</p>
            @if ($searchQuery !== '' || $recordType !== '')
              <a href="{{ route('patient.records') }}">{{ __('patient.records.clear_filters') }}</a>
            @else
              <a href="{{ route('patient.records.create') }}">{{ __('patient.records.add_record') }}</a>
            @endif
          </div>
        @endforelse
      </div>
    </section>

    <aside class="patient-records-sidebar">
      <section class="patient-records-side-card patient-records-health-card" aria-labelledby="patient-records-health-title">
        <div class="patient-records-side-heading">
          <div class="patient-records-side-heading-copy">
            <span class="patient-records-side-icon"><i class="bi bi-person-heart" aria-hidden="true"></i></span>
            <div>
              <h2 id="patient-records-health-title">{{ __('patient.records.health_info_title') }}</h2>
              <p>{{ __('patient.records.health_info_desc') }}</p>
            </div>
          </div>
          <button type="button" class="patient-records-mini-button" data-bs-toggle="modal" data-bs-target="#patientHealthEditModal"><i class="bi bi-pencil" aria-hidden="true"></i>{{ __('patient.records.edit') }}</button>
        </div>

        <div class="patient-records-health-grid">
          <div><span><i class="bi bi-droplet" aria-hidden="true"></i>{{ __('patient.records.blood_group') }}</span><strong>{{ $patient->blood_group }}</strong></div>
          <div><span><i class="bi bi-activity" aria-hidden="true"></i>{{ __('patient.records.blood_pressure') }}</span><strong>{{ $latestVital ? $latestVital->bloodPressureLabel() . ' mmHg' : __('patient.records.not_recorded') }}</strong></div>
          <div><span><i class="bi bi-heart-pulse" aria-hidden="true"></i>{{ __('patient.records.cholesterol') }}</span><strong>{{ $healthValue($healthProfile?->cholesterol_status) }}</strong></div>
          <div><span><i class="bi bi-clipboard2-pulse" aria-hidden="true"></i>{{ __('patient.records.diabetes_risk') }}</span><strong>{{ $healthValue($healthProfile?->diabetes_risk) }}</strong></div>
          <div><span><i class="bi bi-egg-fried" aria-hidden="true"></i>{{ __('patient.records.food_diet') }}</span><strong>{{ $healthValue($healthProfile?->diet_notes ? \Illuminate\Support\Str::limit($healthProfile->diet_notes, 34) : null) }}</strong></div>
          <div><span><i class="bi bi-capsule" aria-hidden="true"></i>{{ __('patient.records.therapy') }}</span><strong>{{ $healthValue($healthProfile?->therapy_notes ? \Illuminate\Support\Str::limit($healthProfile->therapy_notes, 34) : null) }}</strong></div>
        </div>

        <button type="button" class="patient-records-full-profile-button" data-bs-toggle="modal" data-bs-target="#patientHealthProfileModal">{{ __('patient.records.view_full_health_profile') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
      </section>

      <section class="patient-records-side-card patient-records-add-card">
        <span class="patient-records-side-icon"><i class="bi bi-file-earmark-plus" aria-hidden="true"></i></span>
        <div>
          <h2>{{ __('patient.records.add_record_title') }}</h2>
          <p>{{ __('patient.records.add_record_desc') }}</p>
          <a href="{{ route('patient.records.create') }}">{{ __('patient.records.add_record') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
      </section>

      <section class="patient-records-side-card patient-records-access-card" aria-labelledby="patient-records-access-title">
        <div class="patient-records-side-heading simple">
          <div>
            <h2 id="patient-records-access-title">{{ __('patient.records.access_requests') }}</h2>
            <p>{{ __('patient.records.access_desc') }}</p>
          </div>
        </div>

        <div class="patient-records-access-list">
          @forelse ($grants->take(3) as $grant)
            <div class="patient-records-access-item">
              <div class="patient-records-access-copy">
                <strong>Dr. {{ $grant->doctor->full_name }}</strong>
                <small>{{ $grant->requested_at->format('M d, Y · g:i A') }}</small>
                <span>{{ $grant->statusLabel() }}@if ($grant->isActive()) · {{ __('patient.records.until', ['time' => $grant->expires_at->format('M j, g:i A')]) }}@endif</span>
              </div>
              @if ($grant->status === 'otp_sent')
                <div class="patient-records-access-actions">
                  <form method="POST" action="{{ route('patient.records.grants.approve', $grant) }}" data-confirm="{{ __('patient.records.approve_confirm', ['doctor' => $grant->doctor->full_name]) }}">
                    @csrf
                    <input type="text" name="otp_code" inputmode="numeric" maxlength="6" placeholder="{{ __('patient.records.otp_placeholder') }}" required>
                    <button type="submit">{{ __('patient.records.approve') }}</button>
                  </form>
                  <form method="POST" action="{{ route('patient.records.grants.deny', $grant) }}" data-confirm="{{ __('patient.records.deny_confirm', ['doctor' => $grant->doctor->full_name]) }}">
                    @csrf
                    <button type="submit" class="deny">{{ __('patient.records.deny') }}</button>
                  </form>
                </div>
              @endif
            </div>
          @empty
            <div class="patient-records-side-empty"><i class="bi bi-shield-check" aria-hidden="true"></i><p>{{ __('patient.records.no_requests') }}</p></div>
          @endforelse
        </div>
      </section>
    </aside>
  </div>

  <section class="patient-records-health-history" aria-labelledby="patient-records-health-history-title">
    <div class="patient-records-history-heading">
      <div>
        <span>{{ __('patient.records.health_history_kicker') }}</span>
        <h2 id="patient-records-health-history-title">{{ __('patient.records.health_history_title') }}</h2>
      </div>
      <a href="{{ route('patient.vitals') }}">{{ __('patient.records.view_vitals_history') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </div>

    <div class="patient-records-history-grid">
      <div class="patient-records-history-card">
        <div class="patient-records-history-card-heading">
          <div><span class="patient-records-side-icon"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i></span><div><h3>{{ __('patient.records.allergies') }}</h3><p>{{ __('patient.records.allergies_desc') }}</p></div></div>
          <a href="{{ route('patient.allergies.create') }}">{{ __('patient.records.add_allergy') }}</a>
        </div>
        @if ($allergies->isEmpty())
          <p class="patient-records-history-empty">{{ __('patient.records.no_allergies') }}</p>
        @else
          <div class="patient-records-allergy-list">
            @foreach ($allergies as $allergy)
              <div class="patient-records-allergy-item">
                <div><strong>{{ $allergy->allergen }}</strong><span>{{ $allergy->reaction ?: __('patient.records.no_reaction_recorded') }}</span></div>
                <form method="POST" action="{{ route('patient.allergies.destroy', $allergy) }}" data-confirm="{{ __('patient.records.remove_allergy_confirm') }}">
                  @csrf
                  <button type="submit" aria-label="{{ __('patient.records.remove') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                </form>
              </div>
            @endforeach
          </div>
        @endif
      </div>

      <div class="patient-records-history-card">
        <div class="patient-records-history-card-heading">
          <div><span class="patient-records-side-icon"><i class="bi bi-activity" aria-hidden="true"></i></span><div><h3>{{ __('patient.records.latest_vitals') }}</h3><p>{{ __('patient.records.vitals_desc') }}</p></div></div>
        </div>
        @if (!$latestVital)
          <p class="patient-records-history-empty">{{ __('patient.records.no_vitals') }}</p>
        @else
          <div class="patient-records-vitals-grid">
            <div><span>{{ __('patient.records.blood_pressure') }}</span><strong>{{ $latestVital->bloodPressureLabel() }}</strong></div>
            <div><span>{{ __('patient.records.heart_rate') }}</span><strong>{{ $latestVital->heart_rate ?? '—' }}</strong></div>
            <div><span>{{ __('patient.records.temp') }}</span><strong>{{ $latestVital->temperature_celsius ?? '—' }}</strong></div>
            <div><span>{{ __('patient.records.weight') }}</span><strong>{{ $latestVital->weight_kg ?? '—' }}</strong></div>
          </div>
          <p class="patient-records-vitals-meta">{{ __('patient.records.recorded_by') }} Dr. {{ $latestVital->recordedByDoctor->full_name ?? '—' }} · {{ $latestVital->recorded_at->format('M d, Y') }}</p>
        @endif
      </div>
    </div>
  </section>
</div>

<footer class="patient-dashboard-footer patient-records-footer">
  <div class="patient-dashboard-footer-brand">
    <span class="patient-dashboard-footer-dot" aria-hidden="true"></span>
    <div><strong>{{ __('dashboard.patient.platform_name') }}</strong><small>{{ __('home.brand_tagline') }}</small></div>
  </div>
  <p>{{ __('dashboard.patient.footer_tagline') }}</p>
</footer>

<div class="modal" id="patientHealthProfileModal" tabindex="-1" aria-labelledby="patientHealthProfileTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content patient-records-modal-content">
      <div class="patient-records-modal-header">
        <div><span>{{ __('patient.records.personal_health_info') }}</span><h2 id="patientHealthProfileTitle">{{ __('patient.records.full_health_profile_title') }}</h2><p>{{ __('patient.records.full_health_profile_desc') }}</p></div>
        <button type="button" data-bs-dismiss="modal" aria-label="{{ __('patient.records.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
      </div>
      <div class="patient-records-modal-body">
        <div class="patient-records-profile-grid">
          <div><span>{{ __('patient.records.blood_group') }}</span><strong>{{ $patient->blood_group }}</strong></div>
          <div><span>{{ __('patient.records.blood_pressure') }}</span><strong>{{ $latestVital ? $latestVital->bloodPressureLabel() . ' mmHg' : __('patient.records.not_recorded') }}</strong></div>
          <div><span>{{ __('patient.records.heart_rate') }}</span><strong>{{ $latestVital?->heart_rate ? $latestVital->heart_rate . ' bpm' : __('patient.records.not_recorded') }}</strong></div>
          <div><span>{{ __('patient.records.weight') }}</span><strong>{{ $latestVital?->weight_kg ? $latestVital->weight_kg . ' kg' : __('patient.records.not_recorded') }}</strong></div>
          <div><span>{{ __('patient.records.cholesterol') }}</span><strong>{{ $healthValue($healthProfile?->cholesterol_status) }}</strong></div>
          <div><span>{{ __('patient.records.diabetes_risk') }}</span><strong>{{ $healthValue($healthProfile?->diabetes_risk) }}</strong></div>
        </div>
        <div class="patient-records-profile-notes-grid">
          <div><span>{{ __('patient.records.food_diet') }}</span><p>{{ $healthValue($healthProfile?->diet_notes) }}</p></div>
          <div><span>{{ __('patient.records.therapy') }}</span><p>{{ $healthValue($healthProfile?->therapy_notes) }}</p></div>
          <div><span>{{ __('patient.records.major_health_risks') }}</span><p>{{ $healthValue($healthProfile?->major_health_risks) }}</p></div>
          <div><span>{{ __('patient.records.chronic_conditions') }}</span><p>{{ $healthValue($healthProfile?->chronic_conditions) }}</p></div>
          <div class="wide"><span>{{ __('patient.records.lifestyle_notes') }}</span><p>{{ $healthValue($healthProfile?->lifestyle_notes) }}</p></div>
          <div class="wide"><span>{{ __('patient.records.allergies') }}</span><p>{{ $allergies->isNotEmpty() ? $allergies->map(fn ($a) => $a->allergen . ($a->reaction ? ' (' . $a->reaction . ')' : ''))->implode(', ') : __('patient.records.no_allergies') }}</p></div>
        </div>
      </div>
      <div class="patient-records-modal-footer">
        <button type="button" class="patient-records-outline-button" data-bs-dismiss="modal">{{ __('patient.records.close') }}</button>
        <button type="button" class="patient-records-primary-button" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#patientHealthEditModal"><i class="bi bi-pencil" aria-hidden="true"></i>{{ __('patient.records.edit_health_info') }}</button>
      </div>
    </div>
  </div>
</div>

<div class="modal" id="patientHealthEditModal" tabindex="-1" aria-labelledby="patientHealthEditTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content patient-records-modal-content">
      <form method="POST" action="{{ route('patient.records.health-profile.update') }}">
        @csrf
        <div class="patient-records-modal-header">
          <div><span>{{ __('patient.records.personal_health_info') }}</span><h2 id="patientHealthEditTitle">{{ __('patient.records.edit_health_info') }}</h2><p>{{ __('patient.records.edit_health_info_desc') }}</p></div>
          <button type="button" data-bs-dismiss="modal" aria-label="{{ __('patient.records.close') }}"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </div>
        <div class="patient-records-modal-body">
          <div class="patient-records-health-form-grid">
            <label><span>{{ __('patient.records.cholesterol') }}</span><input type="text" name="cholesterol_status" maxlength="100" value="{{ old('cholesterol_status', $healthProfile?->cholesterol_status) }}" placeholder="{{ __('patient.records.cholesterol_placeholder') }}"></label>
            <label><span>{{ __('patient.records.diabetes_risk') }}</span><input type="text" name="diabetes_risk" maxlength="100" value="{{ old('diabetes_risk', $healthProfile?->diabetes_risk) }}" placeholder="{{ __('patient.records.diabetes_placeholder') }}"></label>
            <label><span>{{ __('patient.records.food_diet') }}</span><textarea name="diet_notes" rows="3" maxlength="1000" placeholder="{{ __('patient.records.diet_placeholder') }}">{{ old('diet_notes', $healthProfile?->diet_notes) }}</textarea></label>
            <label><span>{{ __('patient.records.therapy') }}</span><textarea name="therapy_notes" rows="3" maxlength="1000" placeholder="{{ __('patient.records.therapy_placeholder') }}">{{ old('therapy_notes', $healthProfile?->therapy_notes) }}</textarea></label>
            <label><span>{{ __('patient.records.major_health_risks') }}</span><textarea name="major_health_risks" rows="3" maxlength="1000" placeholder="{{ __('patient.records.risks_placeholder') }}">{{ old('major_health_risks', $healthProfile?->major_health_risks) }}</textarea></label>
            <label><span>{{ __('patient.records.chronic_conditions') }}</span><textarea name="chronic_conditions" rows="3" maxlength="1000" placeholder="{{ __('patient.records.conditions_placeholder') }}">{{ old('chronic_conditions', $healthProfile?->chronic_conditions) }}</textarea></label>
            <label class="wide"><span>{{ __('patient.records.lifestyle_notes') }}</span><textarea name="lifestyle_notes" rows="3" maxlength="1000" placeholder="{{ __('patient.records.lifestyle_placeholder') }}">{{ old('lifestyle_notes', $healthProfile?->lifestyle_notes) }}</textarea></label>
          </div>
          <p class="patient-records-health-form-note"><i class="bi bi-info-circle" aria-hidden="true"></i>{{ __('patient.records.health_sources_note') }}</p>
        </div>
        <div class="patient-records-modal-footer">
          <button type="button" class="patient-records-outline-button" data-bs-dismiss="modal">{{ __('patient.records.cancel') }}</button>
          <button type="submit" class="patient-records-primary-button">{{ __('patient.records.save_health_info') }}</button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
  document.getElementById('print-record-btn')?.addEventListener('click', function () {
    const printUrl = @json(route('patient.records.print'));
    showConfirmModal(@json(__('patient.records.print_confirm')), function () {
      window.location.href = printUrl;
    });
  });
</script>
@endpush
@endsection
