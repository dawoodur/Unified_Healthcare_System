@extends('layouts.app')
@section('title', __('register.patient_page_title'))
@section('content')
<div class="patient-register-page">
  <section class="patient-register-shell patient-register-hero" aria-labelledby="patient-register-title">
    <div class="patient-register-intro">
      <a class="patient-register-back" href="{{ route('register.choose') }}">
        <i class="bi bi-arrow-left" aria-hidden="true"></i>
        <span>{{ __('register.patient_back') }}</span>
      </a>
      <h1 id="patient-register-title">
        {{ __('register.patient_title_prefix') }} <span>{{ __('register.patient_title_highlight') }}</span>
      </h1>
      <p>{{ __('register.patient_subtitle') }}</p>
    </div>

    <ol class="patient-register-progress" aria-label="{{ __('register.patient_progress_label') }}">
      <li class="is-active">
        <span>1</span>
        <strong>{{ __('register.patient_progress_details') }}</strong>
      </li>
      <li>
        <span>2</span>
        <strong>{{ __('register.patient_progress_verify') }}</strong>
      </li>
      <li>
        <span>3</span>
        <strong>{{ __('register.patient_progress_ready') }}</strong>
      </li>
    </ol>
  </section>

  <section class="patient-register-shell patient-register-layout">
    <div class="patient-register-form-card">
      <div class="patient-register-form-heading">
        <span class="patient-register-form-icon" aria-hidden="true"><i class="bi bi-person"></i></span>
        <div>
          <h2>{{ __('register.patient_form_title') }}</h2>
          <p>{{ __('register.patient_form_subtitle') }}</p>
        </div>
      </div>

      <form method="POST" action="{{ route('register.patient') }}" class="patient-register-form" novalidate>
        @csrf

        <fieldset class="patient-register-section">
          <legend>{{ __('register.patient_section_personal') }}</legend>
          <div class="patient-register-grid patient-register-grid-3">
            <div class="patient-register-field patient-register-field-wide">
              <label for="full_name">{{ __('register.patient_full_name') }} <span>*</span></label>
              <div class="patient-register-control @error('full_name') is-invalid @enderror">
                <i class="bi bi-person" aria-hidden="true"></i>
                <input type="text" id="full_name" name="full_name" value="{{ old('full_name') }}" autocomplete="name" placeholder="{{ __('register.patient_full_name_placeholder') }}" required>
              </div>
              @error('full_name')<p class="patient-register-error">{{ $message }}</p>@enderror
            </div>

            <div class="patient-register-field">
              <label for="age">{{ __('register.patient_age') }} <span>*</span></label>
              <div class="patient-register-control @error('age') is-invalid @enderror">
                <i class="bi bi-calendar3" aria-hidden="true"></i>
                <input type="number" id="age" name="age" min="1" max="120" inputmode="numeric" value="{{ old('age') }}" placeholder="{{ __('register.patient_age_placeholder') }}" required>
              </div>
              @error('age')<p class="patient-register-error">{{ $message }}</p>@enderror
            </div>

            <div class="patient-register-field">
              <label for="gender">{{ __('register.patient_gender') }} <span>*</span></label>
              <div class="patient-register-control @error('gender') is-invalid @enderror">
                <i class="bi bi-gender-ambiguous" aria-hidden="true"></i>
                <select id="gender" name="gender" required>
                  <option value="">{{ __('register.patient_select') }}</option>
                  <option value="male" @selected(old('gender') === 'male')>{{ __('register.patient_gender_male') }}</option>
                  <option value="female" @selected(old('gender') === 'female')>{{ __('register.patient_gender_female') }}</option>
                </select>
              </div>
              @error('gender')<p class="patient-register-error">{{ $message }}</p>@enderror
            </div>

            <div class="patient-register-field">
              <label for="blood_group">{{ __('register.patient_blood_group') }} <span>*</span></label>
              <div class="patient-register-control @error('blood_group') is-invalid @enderror">
                <i class="bi bi-droplet" aria-hidden="true"></i>
                <select id="blood_group" name="blood_group" required>
                  <option value="">{{ __('register.patient_select_blood') }}</option>
                  @foreach (['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $bg)
                    <option value="{{ $bg }}" @selected(old('blood_group') === $bg)>{{ $bg }}</option>
                  @endforeach
                </select>
              </div>
              @error('blood_group')<p class="patient-register-error">{{ $message }}</p>@enderror
            </div>
          </div>
        </fieldset>

        <fieldset class="patient-register-section">
          <legend>{{ __('register.patient_section_contact') }}</legend>
          <div class="patient-register-grid patient-register-grid-2">
            <div class="patient-register-field">
              <label for="mobile">{{ __('register.patient_mobile') }} <span>*</span></label>
              <div class="patient-register-control @error('mobile') is-invalid @enderror">
                <i class="bi bi-phone" aria-hidden="true"></i>
                <input type="tel" id="mobile" name="mobile" value="{{ old('mobile') }}" autocomplete="tel" inputmode="tel" placeholder="017XXXXXXXX" required>
              </div>
              @error('mobile')<p class="patient-register-error">{{ $message }}</p>@enderror
            </div>

            <div class="patient-register-field">
              <label for="email">{{ __('register.patient_email') }} <span>*</span></label>
              <div class="patient-register-control @error('email') is-invalid @enderror">
                <i class="bi bi-envelope" aria-hidden="true"></i>
                <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="name@example.com" required>
              </div>
              @error('email')<p class="patient-register-error">{{ $message }}</p>@enderror
            </div>

            <div class="patient-register-field patient-register-field-wide">
              <label for="address">{{ __('register.patient_address') }} <small>{{ __('register.patient_optional') }}</small></label>
              <div class="patient-register-control @error('address') is-invalid @enderror">
                <i class="bi bi-geo-alt" aria-hidden="true"></i>
                <input type="text" id="address" name="address" value="{{ old('address') }}" autocomplete="street-address" placeholder="{{ __('register.patient_address_placeholder') }}">
              </div>
              @error('address')<p class="patient-register-error">{{ $message }}</p>@enderror
            </div>
          </div>
        </fieldset>

        <fieldset class="patient-register-section">
          <legend>{{ __('register.patient_section_security') }}</legend>
          <div class="patient-register-grid patient-register-grid-2">
            <div class="patient-register-field">
              <label for="password">{{ __('register.patient_password') }} <span>*</span></label>
              <div class="patient-register-control @error('password') is-invalid @enderror">
                <i class="bi bi-lock" aria-hidden="true"></i>
                <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" placeholder="{{ __('register.patient_password_placeholder') }}" required>
              </div>
              @error('password')<p class="patient-register-error">{{ $message }}</p>@enderror
            </div>

            <div class="patient-register-field">
              <label for="password_confirmation">{{ __('register.patient_confirm_password') }} <span>*</span></label>
              <div class="patient-register-control">
                <i class="bi bi-shield-lock" aria-hidden="true"></i>
                <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" autocomplete="new-password" placeholder="{{ __('register.patient_confirm_placeholder') }}" required>
              </div>
            </div>
          </div>
        </fieldset>

        <div class="patient-register-security-note">
          <i class="bi bi-shield-check" aria-hidden="true"></i>
          <div>
            <strong>{{ __('register.patient_security_title') }}</strong>
            <p>{{ __('register.patient_security_desc') }}</p>
          </div>
        </div>

        <div class="patient-register-actions">
          <a class="patient-register-cancel" href="{{ route('register.choose') }}">{{ __('register.patient_cancel') }}</a>
          <button type="submit" class="patient-register-submit">
            <span>{{ __('register.patient_create_account') }}</span>
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
          </button>
        </div>
      </form>
    </div>

    <aside class="patient-register-aside" aria-label="{{ __('register.patient_aside_label') }}">
      <span class="patient-register-aside-eyebrow">{{ __('register.patient_aside_eyebrow') }}</span>
      <h2>{{ __('register.patient_aside_title_prefix') }} <span>{{ __('register.patient_aside_title_highlight') }}</span></h2>
      <p class="patient-register-aside-lead">{{ __('register.patient_aside_desc') }}</p>

      <div class="patient-register-benefits">
        <div class="patient-register-benefit">
          <span><i class="bi bi-calendar2-check" aria-hidden="true"></i></span>
          <div><strong>{{ __('register.patient_benefit_appointments_title') }}</strong><p>{{ __('register.patient_benefit_appointments_desc') }}</p></div>
        </div>
        <div class="patient-register-benefit">
          <span><i class="bi bi-file-earmark-medical" aria-hidden="true"></i></span>
          <div><strong>{{ __('register.patient_benefit_records_title') }}</strong><p>{{ __('register.patient_benefit_records_desc') }}</p></div>
        </div>
        <div class="patient-register-benefit">
          <span><i class="bi bi-bell" aria-hidden="true"></i></span>
          <div><strong>{{ __('register.patient_benefit_reminders_title') }}</strong><p>{{ __('register.patient_benefit_reminders_desc') }}</p></div>
        </div>
        <div class="patient-register-benefit">
          <span><i class="bi bi-shield-check" aria-hidden="true"></i></span>
          <div><strong>{{ __('register.patient_benefit_secure_title') }}</strong><p>{{ __('register.patient_benefit_secure_desc') }}</p></div>
        </div>
      </div>

      <div class="patient-register-next-step">
        <span class="patient-register-next-step-icon"><i class="bi bi-envelope-check" aria-hidden="true"></i></span>
        <div>
          <small>{{ __('register.patient_next_step_label') }}</small>
          <strong>{{ __('register.patient_next_step_title') }}</strong>
          <p>{{ __('register.patient_next_step_desc') }}</p>
        </div>
      </div>
    </aside>
  </section>

  <footer class="patient-register-footer">
    <div class="patient-register-shell patient-register-footer-grid">
      <div class="patient-register-footer-item">
        <i class="bi bi-lock" aria-hidden="true"></i>
        <div><strong>{{ __('register.footer_secure_title') }}</strong><p>{{ __('register.footer_secure_desc') }}</p></div>
      </div>
      <div class="patient-register-footer-item">
        <i class="bi bi-people" aria-hidden="true"></i>
        <div><strong>{{ __('register.footer_trusted_title') }}</strong><p>{{ __('register.footer_trusted_desc') }}</p></div>
      </div>
      <div class="patient-register-footer-item">
        <i class="bi bi-heart" aria-hidden="true"></i>
        <div><strong>{{ __('register.footer_healthier_lives') }}</strong><p>{{ __('register.patient_footer_healthier_desc') }}</p></div>
      </div>
    </div>
  </footer>
</div>
@endsection
