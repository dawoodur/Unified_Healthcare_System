@extends('layouts.app')
@section('title', __('patient.record_create.title'))
@section('content')
<div class="patient-records-page patient-record-create-page">
  <nav class="patient-appointments-workspace-nav" aria-label="{{ __('patient.records.patient_navigation') }}">
    <a href="{{ route('patient.doctors') }}"><i class="bi bi-calendar2-check" aria-hidden="true"></i><span>{{ __('dashboard.patient.appointments_title') }}</span></a>
    <a href="{{ route('patient.prescriptions') }}"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span>{{ __('dashboard.patient.stat_prescriptions') }}</span></a>
    <a href="{{ route('patient.lab-tests') }}"><i class="bi bi-eyedropper" aria-hidden="true"></i><span>{{ __('dashboard.patient.lab_tests') }}</span></a>
    <a href="{{ route('patient.facilities') }}"><i class="bi bi-hospital" aria-hidden="true"></i><span>{{ __('dashboard.patient.hospital_services') }}</span></a>
    <a class="active" href="{{ route('patient.records') }}"><i class="bi bi-clipboard2" aria-hidden="true"></i><span>{{ __('dashboard.patient.records_short') }}</span></a>
  </nav>

  <section class="patient-record-create-hero" aria-labelledby="patient-record-create-title">
    <div class="patient-record-create-breadcrumb">
      <a href="{{ route('patient.dashboard') }}">{{ __('patient.record_create.home') }}</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <a href="{{ route('patient.records') }}">{{ __('patient.record_create.records') }}</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <span>{{ __('patient.record_create.title') }}</span>
    </div>
    <h1 id="patient-record-create-title">{{ __('patient.record_create.hero_title') }}</h1>
    <p>{{ __('patient.record_create.hero_desc') }}</p>
  </section>

  <div class="patient-record-create-layout">
    <section class="patient-record-create-card" aria-labelledby="patient-record-create-form-title">
      <div class="patient-record-create-card-heading">
        <span class="patient-record-create-heading-icon"><i class="bi bi-file-earmark-plus" aria-hidden="true"></i></span>
        <div>
          <h2 id="patient-record-create-form-title">{{ __('patient.record_create.details_title') }}</h2>
          <p>{{ __('patient.record_create.details_desc') }}</p>
        </div>
      </div>

      <form method="POST" action="{{ route('patient.records.store') }}" enctype="multipart/form-data" data-confirm="Add this record?" class="patient-record-create-form">
        @csrf

        <div class="patient-record-create-field">
          <label for="record_type">{{ __('patient.record_create.type_label') }}</label>
          <div class="patient-record-create-control">
            <i class="bi bi-ui-checks-grid" aria-hidden="true"></i>
            <select id="record_type" name="record_type" required>
              <option value="prescription" @selected(old('record_type') === 'prescription')>{{ __('patient.record_create.type_prescription') }}</option>
              <option value="lab_result" @selected(old('record_type') === 'lab_result')>{{ __('patient.record_create.type_lab_result') }}</option>
              <option value="diagnosis_note" @selected(old('record_type') === 'diagnosis_note')>{{ __('patient.record_create.type_diagnosis_note') }}</option>
              <option value="uploaded_document" @selected(old('record_type') === 'uploaded_document')>{{ __('patient.record_create.type_uploaded_document') }}</option>
            </select>
          </div>
        </div>

        <div class="patient-record-create-field">
          <label for="description">{{ __('patient.record_create.description_label') }}</label>
          <div class="patient-record-create-control">
            <i class="bi bi-card-text" aria-hidden="true"></i>
            <input
              type="text"
              id="description"
              name="description"
              value="{{ old('description') }}"
              maxlength="255"
              placeholder="{{ __('patient.record_create.description_placeholder') }}"
              required
            >
          </div>
        </div>

        <div class="patient-record-create-upload-section">
          <div class="patient-record-create-upload-heading">
            <div>
              <h3>{{ __('patient.record_create.upload_title') }}</h3>
              <p>{{ __('patient.record_create.upload_desc') }}</p>
            </div>
            <span>{{ __('patient.record_create.optional') }}</span>
          </div>
          <label class="patient-record-create-file" for="file">
            <span class="patient-record-create-file-icon"><i class="bi bi-cloud-arrow-up" aria-hidden="true"></i></span>
            <span class="patient-record-create-file-copy">
              <strong>{{ __('patient.record_create.file_label_short') }}</strong>
              <small>{{ __('patient.record_create.file_formats') }}</small>
            </span>
            <input type="file" id="file" name="file" accept=".pdf,.jpg,.jpeg,.png">
          </label>
        </div>

        <div class="patient-record-create-actions">
          <a href="{{ route('patient.records') }}" class="patient-record-create-cancel">{{ __('patient.record_create.cancel') }}</a>
          <button type="submit" class="patient-record-create-submit">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            {{ __('patient.record_create.submit') }}
          </button>
        </div>
      </form>
    </section>

    <aside class="patient-record-create-aside">
      <section class="patient-record-create-note-card">
        <span class="patient-record-create-note-icon"><i class="bi bi-info-circle" aria-hidden="true"></i></span>
        <div>
          <h2>{{ __('patient.record_create.note_title') }}</h2>
          <p>{{ __('patient.record_create.intro') }}</p>
        </div>
      </section>

      <section class="patient-record-create-note-card">
        <span class="patient-record-create-note-icon"><i class="bi bi-shield-check" aria-hidden="true"></i></span>
        <div>
          <h2>{{ __('patient.record_create.privacy_title') }}</h2>
          <p>{{ __('patient.record_create.privacy_desc') }}</p>
        </div>
      </section>
    </aside>
  </div>
</div>
@endsection
