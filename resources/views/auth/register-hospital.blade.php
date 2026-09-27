@extends('layouts.app')
@section('title', __('register.hospital_page_title'))
@section('content')
<div class="patient-register-page hospital-register-page">
  <section class="patient-register-shell patient-register-hero" aria-labelledby="hospital-register-title">
    <div class="patient-register-intro">
      <a class="patient-register-back" href="{{ route('register.choose') }}">
        <i class="bi bi-arrow-left" aria-hidden="true"></i>
        <span>{{ __('register.hospital_back') }}</span>
      </a>
      <h1 id="hospital-register-title">
        {{ __('register.hospital_title_prefix') }} <span>{{ __('register.hospital_title_highlight') }}</span>
      </h1>
      <p>{{ __('register.hospital_subtitle') }}</p>
    </div>

    <ol class="patient-register-progress" aria-label="{{ __('register.hospital_progress_label') }}">
      <li class="is-active">
        <span>1</span>
        <strong>{{ __('register.hospital_progress_details') }}</strong>
      </li>
      <li>
        <span>2</span>
        <strong>{{ __('register.hospital_progress_verify') }}</strong>
      </li>
      <li>
        <span>3</span>
        <strong>{{ __('register.hospital_progress_ready') }}</strong>
      </li>
    </ol>
  </section>

  <section class="patient-register-shell patient-register-layout">
    <div class="patient-register-form-card">
      <div class="patient-register-form-heading">
        <span class="patient-register-form-icon" aria-hidden="true"><i class="bi bi-hospital"></i></span>
        <div>
          <h2>{{ __('register.hospital_form_title') }}</h2>
          <p>{{ __('register.hospital_form_subtitle') }}</p>
        </div>
      </div>

      <form method="POST" action="{{ route('register.hospital') }}" class="patient-register-form" novalidate>
        @csrf

        <fieldset class="patient-register-section">
          <legend>{{ __('register.hospital_section_identity') }}</legend>
          <div class="patient-register-grid patient-register-grid-2">
            <div class="patient-register-field">
              <label for="hospital_name">{{ __('register.hospital_name') }} <span>*</span></label>
              <div class="patient-register-control @error('hospital_name') is-invalid @enderror">
                <i class="bi bi-hospital" aria-hidden="true"></i>
                <input type="text" id="hospital_name" name="hospital_name" value="{{ old('hospital_name') }}" autocomplete="organization" placeholder="{{ __('register.hospital_name_placeholder') }}" required>
              </div>
              @error('hospital_name')<p class="patient-register-error">{{ $message }}</p>@enderror
            </div>

            <div class="patient-register-field">
              <label for="registration_number">{{ __('register.hospital_registration_number') }} <span>*</span></label>
              <div class="patient-register-control @error('registration_number') is-invalid @enderror">
                <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                <input type="text" id="registration_number" name="registration_number" value="{{ old('registration_number') }}" placeholder="{{ __('register.hospital_registration_placeholder') }}" required>
              </div>
              @error('registration_number')<p class="patient-register-error">{{ $message }}</p>@enderror
            </div>

            <div class="patient-register-field patient-register-field-wide">
              <label for="email">{{ __('register.hospital_email') }} <span>*</span></label>
              <div class="patient-register-control @error('email') is-invalid @enderror">
                <i class="bi bi-envelope" aria-hidden="true"></i>
                <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="{{ __('register.hospital_email_placeholder') }}" required>
              </div>
              @error('email')<p class="patient-register-error">{{ $message }}</p>@enderror
            </div>
          </div>
        </fieldset>

        <fieldset class="patient-register-section">
          <legend>{{ __('register.hospital_section_location') }}</legend>
          <div class="patient-register-grid patient-register-grid-2">
            <div class="patient-register-field">
              <label for="city">{{ __('register.hospital_city') }} <small>{{ __('register.patient_optional') }}</small></label>
              <div class="patient-register-control @error('city') is-invalid @enderror">
                <i class="bi bi-geo-alt" aria-hidden="true"></i>
                <input type="text" id="city" name="city" value="{{ old('city') }}" autocomplete="address-level2" placeholder="{{ __('register.hospital_city_placeholder') }}">
              </div>
              @error('city')<p class="patient-register-error">{{ $message }}</p>@enderror
            </div>

            <div class="patient-register-field">
              <label for="address">{{ __('register.hospital_address') }} <small>{{ __('register.patient_optional') }}</small></label>
              <div class="patient-register-control @error('address') is-invalid @enderror">
                <i class="bi bi-building" aria-hidden="true"></i>
                <input type="text" id="address" name="address" value="{{ old('address') }}" autocomplete="street-address" placeholder="{{ __('register.hospital_address_placeholder') }}">
              </div>
              @error('address')<p class="patient-register-error">{{ $message }}</p>@enderror
            </div>
          </div>
        </fieldset>

        <fieldset class="patient-register-section">
          <legend>{{ __('register.hospital_section_security') }}</legend>
          <div class="patient-register-grid patient-register-grid-2">
            <div class="patient-register-field">
              <label for="password">{{ __('register.hospital_password') }} <span>*</span></label>
              <div class="patient-register-control @error('password') is-invalid @enderror">
                <i class="bi bi-lock" aria-hidden="true"></i>
                <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" placeholder="{{ __('register.patient_password_placeholder') }}" required>
              </div>
              @error('password')<p class="patient-register-error">{{ $message }}</p>@enderror
            </div>

            <div class="patient-register-field">
              <label for="password_confirmation">{{ __('register.hospital_confirm_password') }} <span>*</span></label>
              <div class="patient-register-control">
                <i class="bi bi-shield-lock" aria-hidden="true"></i>
                <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" autocomplete="new-password" placeholder="{{ __('register.patient_confirm_placeholder') }}" required>
              </div>
            </div>
          </div>
        </fieldset>

        <div class="patient-register-security-note hospital-register-security-note">
          <i class="bi bi-shield-check" aria-hidden="true"></i>
          <div>
            <strong>{{ __('register.hospital_security_title') }}</strong>
            <p>{{ __('register.hospital_security_desc') }}</p>
          </div>
        </div>

        <div class="patient-register-actions">
          <a class="patient-register-cancel" href="{{ route('register.choose') }}">{{ __('register.patient_cancel') }}</a>
          <button type="submit" class="patient-register-submit">
            <span>{{ __('register.hospital_create_account') }}</span>
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
          </button>
        </div>
      </form>
    </div>

    <aside class="patient-register-aside hospital-register-aside" aria-label="{{ __('register.hospital_aside_label') }}">
      <span class="patient-register-aside-eyebrow">{{ __('register.hospital_aside_eyebrow') }}</span>
      <h2>{{ __('register.hospital_aside_title_prefix') }} <span>{{ __('register.hospital_aside_title_highlight') }}</span></h2>
      <p class="patient-register-aside-lead">{{ __('register.hospital_aside_desc') }}</p>

      <div class="patient-register-benefits">
        <div class="patient-register-benefit">
          <span><i class="bi bi-person-badge" aria-hidden="true"></i></span>
          <div><strong>{{ __('register.hospital_benefit_doctors_title') }}</strong><p>{{ __('register.hospital_benefit_doctors_desc') }}</p></div>
        </div>
        <div class="patient-register-benefit">
          <span><i class="bi bi-buildings" aria-hidden="true"></i></span>
          <div><strong>{{ __('register.hospital_benefit_facilities_title') }}</strong><p>{{ __('register.hospital_benefit_facilities_desc') }}</p></div>
        </div>
        <div class="patient-register-benefit">
          <span><i class="bi bi-calendar2-check" aria-hidden="true"></i></span>
          <div><strong>{{ __('register.hospital_benefit_bookings_title') }}</strong><p>{{ __('register.hospital_benefit_bookings_desc') }}</p></div>
        </div>
        <div class="patient-register-benefit">
          <span><i class="bi bi-credit-card" aria-hidden="true"></i></span>
          <div><strong>{{ __('register.hospital_benefit_payments_title') }}</strong><p>{{ __('register.hospital_benefit_payments_desc') }}</p></div>
        </div>
      </div>

      <div class="patient-register-next-step hospital-register-next-step">
        <span class="patient-register-next-step-icon"><i class="bi bi-envelope-check" aria-hidden="true"></i></span>
        <div>
          <small>{{ __('register.hospital_next_step_label') }}</small>
          <strong>{{ __('register.hospital_next_step_title') }}</strong>
          <p>{{ __('register.hospital_next_step_desc') }}</p>
        </div>
      </div>
    </aside>
  </section>

</div>
@endsection
