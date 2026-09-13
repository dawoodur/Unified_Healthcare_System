@extends('layouts.app')
@section('title', $account->role === 'patient' ? __('patient.profile.page_title') : 'My Profile')
@section('content')
@php
  $profile = $account->profile();
@endphp

@if ($account->role === 'patient')
  <div class="patient-profile-page">
    <nav class="patient-appointments-workspace-nav" aria-label="{{ __('patient.profile.patient_navigation') }}">
      <a href="{{ route('patient.doctors') }}"><i class="bi bi-calendar2-check" aria-hidden="true"></i><span>{{ __('dashboard.patient.appointments_title') }}</span></a>
      <a href="{{ route('patient.prescriptions') }}"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span>{{ __('dashboard.patient.stat_prescriptions') }}</span></a>
      <a href="{{ route('patient.lab-tests') }}"><i class="bi bi-eyedropper" aria-hidden="true"></i><span>{{ __('dashboard.patient.lab_tests') }}</span></a>
      <a href="{{ route('patient.facilities') }}"><i class="bi bi-hospital" aria-hidden="true"></i><span>{{ __('dashboard.patient.hospital_services') }}</span></a>
      <a href="{{ route('patient.records') }}"><i class="bi bi-clipboard2" aria-hidden="true"></i><span>{{ __('dashboard.patient.records_short') }}</span></a>
    </nav>

    <section class="patient-profile-hero" aria-labelledby="patient-profile-title">
      <div class="patient-profile-breadcrumb">
        <a href="{{ route('patient.dashboard') }}">{{ __('patient.profile.home') }}</a>
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
        <span>{{ __('patient.profile.title') }}</span>
      </div>

      <div class="patient-profile-hero-grid">
        <div>
          <h1 id="patient-profile-title">{{ __('patient.profile.hero_title') }}</h1>
          <p>{{ __('patient.profile.hero_desc') }}</p>
        </div>

        <div class="patient-profile-hero-note">
          <span><i class="bi bi-person-check" aria-hidden="true"></i></span>
          <div>
            <strong>{{ __('patient.profile.account_title') }}</strong>
            <small>{{ __('patient.profile.account_desc') }}</small>
          </div>
        </div>
      </div>
    </section>

    <div class="patient-profile-layout">
      <aside class="patient-profile-sidebar">
        <section class="patient-profile-identity-card">
          <div class="patient-profile-avatar-wrap">
            @include('partials.avatar', ['account' => $account, 'size' => 'avatar-circle-xl'])
          </div>

          <div class="patient-profile-identity-copy">
            <h2>{{ $profile->full_name }}</h2>
            <p>{{ __('patient.profile.patient_label') }} · {{ $account->uidTag() }}</p>
          </div>

          <dl class="patient-profile-account-meta">
            <div>
              <dt>{{ __('patient.profile.email') }}</dt>
              <dd>{{ $account->email }}</dd>
            </div>
            <div>
              <dt>{{ __('patient.profile.mobile') }}</dt>
              <dd>{{ $account->mobile }}</dd>
            </div>
            <div>
              <dt>{{ __('patient.profile.blood_group') }}</dt>
              <dd>{{ $profile->blood_group }}</dd>
            </div>
          </dl>
        </section>

        <section class="patient-profile-photo-card">
          <div class="patient-profile-side-heading">
            <span><i class="bi bi-camera" aria-hidden="true"></i></span>
            <div>
              <h2>{{ __('patient.profile.photo_title') }}</h2>
              <p>{{ __('patient.profile.photo_desc') }}</p>
            </div>
          </div>

          <form method="POST" action="{{ route('profile.photo.update') }}" enctype="multipart/form-data" class="patient-profile-photo-form">
            @csrf
            <label for="profile-photo">{{ __('patient.profile.choose_photo') }}</label>
            <input id="profile-photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" required>
            <small>{{ __('patient.profile.photo_hint') }}</small>
            <button type="submit">
              <i class="bi bi-upload" aria-hidden="true"></i>
              {{ __('patient.profile.upload_photo') }}
            </button>
          </form>

          @if ($account->photo_path)
            <form method="POST" action="{{ route('profile.photo.destroy') }}" class="patient-profile-remove-photo-form">
              @csrf
              <button type="submit">
                <i class="bi bi-trash3" aria-hidden="true"></i>
                {{ __('patient.profile.remove_photo') }}
              </button>
            </form>
          @endif
        </section>

        <section class="patient-profile-help-card">
          <span><i class="bi bi-question-circle" aria-hidden="true"></i></span>
          <div>
            <h2>{{ __('patient.profile.help_title') }}</h2>
            <p>{{ __('patient.profile.help_desc') }}</p>
          </div>
          <a href="{{ route('help.index') }}">
            {{ __('patient.profile.help_action') }}
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
          </a>
        </section>
      </aside>

      <main class="patient-profile-main">
        <section class="patient-profile-form-card">
          <header class="patient-profile-section-heading">
            <span><i class="bi bi-person-lines-fill" aria-hidden="true"></i></span>
            <div>
              <h2>{{ __('patient.profile.personal_details') }}</h2>
              <p>{{ __('patient.profile.personal_details_desc') }}</p>
            </div>
          </header>

          <form method="POST" action="{{ route('profile.update') }}" class="patient-profile-form">
            @csrf

            <div class="patient-profile-form-section">
              <div class="patient-profile-form-section-title">
                <span><i class="bi bi-person" aria-hidden="true"></i></span>
                <div>
                  <h3>{{ __('patient.profile.basic_information') }}</h3>
                  <p>{{ __('patient.profile.basic_information_desc') }}</p>
                </div>
              </div>

              <div class="patient-profile-form-grid">
                <div class="patient-profile-field patient-profile-field-wide">
                  <label for="profile-full-name">{{ __('patient.profile.full_name') }}</label>
                  <input id="profile-full-name" name="full_name" maxlength="150" value="{{ old('full_name', $profile->full_name) }}" required>
                </div>

                <div class="patient-profile-field">
                  <label for="profile-age">{{ __('patient.profile.age') }}</label>
                  <input id="profile-age" type="number" name="age" min="1" max="120" value="{{ old('age', $profile->age) }}" required>
                </div>

                <div class="patient-profile-field">
                  <label for="profile-gender">{{ __('patient.profile.gender') }}</label>
                  <select id="profile-gender" name="gender" required>
                    <option value="male" @selected(old('gender', $profile->gender) === 'male')>{{ __('patient.profile.gender_male') }}</option>
                    <option value="female" @selected(old('gender', $profile->gender) === 'female')>{{ __('patient.profile.gender_female') }}</option>
                  </select>
                </div>

                <div class="patient-profile-field">
                  <label for="profile-blood">{{ __('patient.profile.blood_group') }}</label>
                  <select id="profile-blood" name="blood_group" required>
                    @foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $group)
                      <option value="{{ $group }}" @selected(old('blood_group', $profile->blood_group) === $group)>{{ $group }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
            </div>

            <div class="patient-profile-form-section">
              <div class="patient-profile-form-section-title">
                <span><i class="bi bi-envelope-at" aria-hidden="true"></i></span>
                <div>
                  <h3>{{ __('patient.profile.contact_information') }}</h3>
                  <p>{{ __('patient.profile.contact_information_desc') }}</p>
                </div>
              </div>

              <div class="patient-profile-form-grid">
                <div class="patient-profile-field">
                  <label for="profile-email">{{ __('patient.profile.email') }}</label>
                  <input id="profile-email" type="email" name="email" maxlength="190" value="{{ old('email', $account->email) }}" required>
                </div>

                <div class="patient-profile-field">
                  <label for="profile-mobile">{{ __('patient.profile.mobile') }}</label>
                  <input id="profile-mobile" name="mobile" value="{{ old('mobile', $account->mobile) }}" required>
                </div>

                <div class="patient-profile-field patient-profile-field-wide">
                  <label for="profile-address">{{ __('patient.profile.address') }}</label>
                  <input id="profile-address" name="address" maxlength="255" value="{{ old('address', $profile->address) }}" placeholder="{{ __('patient.profile.address_placeholder') }}">
                </div>
              </div>
            </div>

            <footer class="patient-profile-form-actions">
              <span>
                <i class="bi bi-info-circle" aria-hidden="true"></i>
                {{ __('patient.profile.save_note') }}
              </span>

              <button type="submit">
                <i class="bi bi-check2" aria-hidden="true"></i>
                {{ __('patient.profile.save_changes') }}
              </button>
            </footer>
          </form>
        </section>
      </main>
    </div>
  </div>

  <footer class="patient-dashboard-footer patient-profile-footer">
    <div class="patient-dashboard-footer-brand">
      <span class="patient-dashboard-footer-dot"></span>
      <div><strong>{{ __('dashboard.patient.platform_name') }}</strong><small>{{ __('home.brand_tagline') }}</small></div>
    </div>
    <p>{{ __('dashboard.patient.footer_tagline') }}</p>
  </footer>
@else
@php
  $profile = $account->profile();
  $person = in_array($account->role, ['patient', 'doctor', 'delivery']);
  $profileFields = match ($account->role) {
    'hospital' => ['hospital_name' => 'Hospital name', 'registration_number' => 'Registration number', 'city' => 'City', 'address' => 'Address'],
    'pharmacy' => ['pharmacy_name' => 'Pharmacy name', 'etin_number' => 'ETIN number', 'address' => 'Address'],
    default => ['full_name' => 'Full name'],
  };
@endphp
<div class="card">
  <div class="d-flex align-items-center gap-3">
    @include('partials.avatar', ['account' => $account, 'size' => 'avatar-circle-xl'])
    <div><h1>My Profile</h1><p class="muted">{{ ucfirst($account->role) }} · {{ $account->uidTag() }}</p></div>
  </div>
</div>
<div class="card">
  <h2>Personal Details</h2>
  <form method="POST" action="{{ route('profile.update') }}">
    @csrf
    @foreach ($profileFields as $field => $label)
      <label for="profile-{{ $field }}">{{ $label }}</label>
      <input class="form-control mb-3" id="profile-{{ $field }}" name="{{ $field }}" value="{{ old($field, $profile->$field) }}" maxlength="{{ in_array($field, ['registration_number', 'etin_number', 'city']) ? 100 : ($field === 'address' ? 255 : ($field === 'full_name' ? 150 : 190)) }}" @required(!in_array($field, ['city', 'address']))>
    @endforeach
    <label for="profile-email">Email</label>
    <input class="form-control mb-3" id="profile-email" type="email" name="email" maxlength="190" value="{{ old('email', $account->email) }}" required>
    @if ($person || $account->role === 'pharmacy')
      <label for="profile-mobile">Mobile</label>
      <input class="form-control mb-3" id="profile-mobile" name="mobile" value="{{ old('mobile', $account->mobile) }}" @required($person)>
    @endif
    @if ($person)
      <label for="profile-age">Age</label>
      <input class="form-control mb-3" id="profile-age" type="number" name="age" min="{{ $account->role === 'doctor' ? 21 : ($account->role === 'delivery' ? 18 : 1) }}" max="{{ $account->role === 'doctor' ? 100 : ($account->role === 'delivery' ? 70 : 120) }}" value="{{ old('age', $profile->age) }}" required>
      <label for="profile-blood">Blood group</label>
      <select class="form-select mb-3" id="profile-blood" name="blood_group" required>
        @foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $group)
          <option @selected(old('blood_group', $profile->blood_group) === $group)>{{ $group }}</option>
        @endforeach
      </select>
      <label for="profile-gender">Gender</label>
      <select class="form-select mb-3" id="profile-gender" name="gender" required>
        @foreach (['male', 'female'] as $gender)
          <option value="{{ $gender }}" @selected(old('gender', $profile->gender) === $gender)>{{ ucfirst($gender) }}</option>
        @endforeach
      </select>
    @endif
    @if ($account->role === 'patient')
      <label for="profile-address">Address</label>
      <input class="form-control mb-3" id="profile-address" name="address" maxlength="255" value="{{ old('address', $profile->address) }}">
    @endif
    @if ($account->role === 'doctor')
      <label for="profile-bio">Biography</label>
      <textarea class="form-control mb-3" id="profile-bio" name="bio">{{ old('bio', $profile->bio) }}</textarea>
      <label for="profile-fee">Consultation fee (BDT)</label>
      <input class="form-control mb-3" id="profile-fee" type="number" name="consultation_fee" min="0" step="0.01" value="{{ old('consultation_fee', $profile->consultation_fee) }}">
      <fieldset class="mb-3"><legend>Specialties</legend>
        @foreach ($specialties as $specialty)
          <label class="d-block"><input type="checkbox" name="specialty_ids[]" value="{{ $specialty->specialty_id }}" @checked(in_array($specialty->specialty_id, old('specialty_ids', $profile->specialties->pluck('specialty_id')->all())))> {{ $specialty->specialty_name }}</label>
        @endforeach
      </fieldset>
    @endif
    <button class="btn btn-primary" type="submit">Save changes</button>
  </form>
</div>
<div class="card">
  <h2>Profile Photo</h2>
  <form method="POST" action="{{ route('profile.photo.update') }}" enctype="multipart/form-data">
    @csrf
    <label for="profile-photo">Choose a JPG, PNG, or WebP image (up to 2 MB)</label>
    <input class="form-control mb-3" id="profile-photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" required>
    <button class="btn btn-primary" type="submit">Upload photo</button>
  </form>
  @if ($account->photo_path)
    <form class="mt-3" method="POST" action="{{ route('profile.photo.destroy') }}">@csrf<button class="btn btn-outline-secondary" type="submit">Remove photo</button></form>
  @endif
</div>

@endif
@endsection
