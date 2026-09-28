@extends('layouts.app')
@section('title', __('register.delivery_page_title'))
@section('content')
<div class="patient-register-page delivery-register-page">
  <section class="patient-register-shell patient-register-hero" aria-labelledby="delivery-register-title">
    <div class="patient-register-intro">
      <a class="patient-register-back" href="{{ route('register.choose') }}">
        <i class="bi bi-arrow-left" aria-hidden="true"></i>
        <span>{{ __('register.delivery_back') }}</span>
      </a>
      <h1 id="delivery-register-title">
        {{ __('register.delivery_title_prefix') }} <span>{{ __('register.delivery_title_highlight') }}</span>
      </h1>
      <p>{{ __('register.delivery_subtitle') }}</p>
    </div>

    <ol class="patient-register-progress" aria-label="{{ __('register.delivery_progress_label') }}">
      <li class="is-active">
        <span>1</span>
        <strong>{{ __('register.delivery_progress_details') }}</strong>
      </li>
      <li>
        <span>2</span>
        <strong>{{ __('register.delivery_progress_verify') }}</strong>
      </li>
      <li>
        <span>3</span>
        <strong>{{ __('register.delivery_progress_ready') }}</strong>
      </li>
    </ol>
  </section>

  <section class="patient-register-shell patient-register-layout delivery-register-layout">
    <div class="delivery-register-form-column">
      <form method="POST" action="{{ route('register.delivery') }}" class="patient-register-form delivery-register-form" novalidate>
        @csrf

        <fieldset class="delivery-register-section-card">
          <legend class="visually-hidden">{{ __('register.delivery_section_personal') }}</legend>
          <div class="delivery-register-section-heading">
            <span class="patient-register-form-icon" aria-hidden="true"><i class="bi bi-person"></i></span>
            <div>
              <h2>{{ __('register.delivery_section_personal') }}</h2>
              <p>{{ __('register.delivery_personal_desc') }}</p>
            </div>
          </div>

          <div class="patient-register-grid patient-register-grid-2">
            <div class="patient-register-field">
              <label for="full_name">{{ __('register.delivery_full_name') }} <span>*</span></label>
              <div class="patient-register-control @error('full_name') is-invalid @enderror">
                <i class="bi bi-person" aria-hidden="true"></i>
                <input type="text" id="full_name" name="full_name" value="{{ old('full_name') }}" autocomplete="name" placeholder="{{ __('register.delivery_full_name_placeholder') }}" required>
              </div>
              @error('full_name')<p class="patient-register-error">{{ $message }}</p>@enderror
            </div>

            <div class="patient-register-field">
              <label for="age">{{ __('register.delivery_age') }} <span>*</span></label>
              <div class="patient-register-control @error('age') is-invalid @enderror">
                <i class="bi bi-calendar3" aria-hidden="true"></i>
                <input type="number" id="age" name="age" min="18" max="70" inputmode="numeric" value="{{ old('age') }}" placeholder="{{ __('register.delivery_age_placeholder') }}" required>
              </div>
              @error('age')<p class="patient-register-error">{{ $message }}</p>@enderror
            </div>

            <div class="patient-register-field">
              <label for="blood_group">{{ __('register.delivery_blood_group') }} <span>*</span></label>
              <div class="patient-register-control @error('blood_group') is-invalid @enderror">
                <i class="bi bi-droplet" aria-hidden="true"></i>
                <select id="blood_group" name="blood_group" required>
                  <option value="">{{ __('register.delivery_select_blood') }}</option>
                  @foreach (['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $bg)
                    <option value="{{ $bg }}" @selected(old('blood_group') === $bg)>{{ $bg }}</option>
                  @endforeach
                </select>
              </div>
              @error('blood_group')<p class="patient-register-error">{{ $message }}</p>@enderror
            </div>

            <div class="patient-register-field">
              <label for="gender">{{ __('register.delivery_gender') }} <span>*</span></label>
              <div class="patient-register-control @error('gender') is-invalid @enderror">
                <i class="bi bi-gender-ambiguous" aria-hidden="true"></i>
                <select id="gender" name="gender" required>
                  <option value="">{{ __('register.delivery_select') }}</option>
                  <option value="male" @selected(old('gender') === 'male')>{{ __('register.delivery_gender_male') }}</option>
                  <option value="female" @selected(old('gender') === 'female')>{{ __('register.delivery_gender_female') }}</option>
                </select>
              </div>
              @error('gender')<p class="patient-register-error">{{ $message }}</p>@enderror
            </div>
          </div>
        </fieldset>

        <fieldset class="delivery-register-section-card">
          <legend class="visually-hidden">{{ __('register.delivery_section_contact') }}</legend>
          <div class="delivery-register-section-heading">
            <span class="patient-register-form-icon" aria-hidden="true"><i class="bi bi-telephone"></i></span>
            <div>
              <h2>{{ __('register.delivery_section_contact') }}</h2>
              <p>{{ __('register.delivery_contact_desc') }}</p>
            </div>
          </div>

          <div class="patient-register-grid patient-register-grid-2">
            <div class="patient-register-field">
              <label for="mobile">{{ __('register.delivery_mobile') }} <span>*</span></label>
              <div class="patient-register-control @error('mobile') is-invalid @enderror">
                <i class="bi bi-phone" aria-hidden="true"></i>
                <input type="tel" id="mobile" name="mobile" value="{{ old('mobile') }}" autocomplete="tel" inputmode="tel" placeholder="01XXXXXXXXX" required>
              </div>
              @error('mobile')<p class="patient-register-error">{{ $message }}</p>@enderror
            </div>

            <div class="patient-register-field">
              <label for="email">{{ __('register.delivery_email') }} <span>*</span></label>
              <div class="patient-register-control @error('email') is-invalid @enderror">
                <i class="bi bi-envelope" aria-hidden="true"></i>
                <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="{{ __('register.delivery_email_placeholder') }}" required>
              </div>
              @error('email')<p class="patient-register-error">{{ $message }}</p>@enderror
            </div>
          </div>
        </fieldset>

        <fieldset class="delivery-register-section-card delivery-register-security-card">
          <legend class="visually-hidden">{{ __('register.delivery_section_security') }}</legend>
          <div class="delivery-register-section-heading">
            <span class="patient-register-form-icon" aria-hidden="true"><i class="bi bi-lock"></i></span>
            <div>
              <h2>{{ __('register.delivery_section_security') }}</h2>
              <p>{{ __('register.delivery_security_section_desc') }}</p>
            </div>
          </div>

          <div class="patient-register-grid patient-register-grid-2">
            <div class="patient-register-field">
              <label for="password">{{ __('register.delivery_password') }} <span>*</span></label>
              <div class="patient-register-control @error('password') is-invalid @enderror">
                <i class="bi bi-lock" aria-hidden="true"></i>
                <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" placeholder="{{ __('register.patient_password_placeholder') }}" required>
              </div>
              @error('password')<p class="patient-register-error">{{ $message }}</p>@enderror
            </div>

            <div class="patient-register-field">
              <label for="password_confirmation">{{ __('register.delivery_confirm_password') }} <span>*</span></label>
              <div class="patient-register-control">
                <i class="bi bi-shield-lock" aria-hidden="true"></i>
                <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" autocomplete="new-password" placeholder="{{ __('register.patient_confirm_placeholder') }}" required>
              </div>
            </div>
          </div>

          <div class="patient-register-security-note delivery-register-security-note">
            <i class="bi bi-shield-check" aria-hidden="true"></i>
            <div>
              <strong>{{ __('register.delivery_security_title') }}</strong>
              <p>{{ __('register.delivery_security_desc') }}</p>
            </div>
          </div>

          <div class="patient-register-actions delivery-register-actions">
            <a class="patient-register-cancel" href="{{ route('register.choose') }}">{{ __('register.patient_cancel') }}</a>
            <button type="submit" class="patient-register-submit">
              <span>{{ __('register.delivery_create_account') }}</span>
              <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </button>
          </div>
        </fieldset>
      </form>
    </div>

    <aside class="patient-register-aside delivery-register-aside" aria-label="{{ __('register.delivery_aside_label') }}">
      <span class="patient-register-aside-eyebrow">{{ __('register.delivery_aside_eyebrow') }}</span>
      <h2>{{ __('register.delivery_aside_title_prefix') }} <span>{{ __('register.delivery_aside_title_highlight') }}</span></h2>
      <p class="patient-register-aside-lead">{{ __('register.delivery_aside_desc') }}</p>

      <div class="delivery-register-workflow" aria-label="{{ __('register.delivery_workflow_label') }}">
        <div class="delivery-register-workflow-item">
          <span class="delivery-register-workflow-number">01</span>
          <span class="delivery-register-workflow-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
          <div>
            <strong>{{ __('register.delivery_benefit_available_title') }}</strong>
            <p>{{ __('register.delivery_benefit_available_desc') }}</p>
          </div>
        </div>
        <div class="delivery-register-workflow-item">
          <span class="delivery-register-workflow-number">02</span>
          <span class="delivery-register-workflow-icon"><i class="bi bi-truck" aria-hidden="true"></i></span>
          <div>
            <strong>{{ __('register.delivery_benefit_accept_title') }}</strong>
            <p>{{ __('register.delivery_benefit_accept_desc') }}</p>
          </div>
        </div>
        <div class="delivery-register-workflow-item">
          <span class="delivery-register-workflow-number">03</span>
          <span class="delivery-register-workflow-icon"><i class="bi bi-shield-check" aria-hidden="true"></i></span>
          <div>
            <strong>{{ __('register.delivery_benefit_otp_title') }}</strong>
            <p>{{ __('register.delivery_benefit_otp_desc') }}</p>
          </div>
        </div>
        <div class="delivery-register-workflow-item">
          <span class="delivery-register-workflow-number">04</span>
          <span class="delivery-register-workflow-icon"><i class="bi bi-chat-dots" aria-hidden="true"></i></span>
          <div>
            <strong>{{ __('register.delivery_benefit_inbox_title') }}</strong>
            <p>{{ __('register.delivery_benefit_inbox_desc') }}</p>
          </div>
        </div>
        <div class="delivery-register-workflow-item">
          <span class="delivery-register-workflow-number">05</span>
          <span class="delivery-register-workflow-icon"><i class="bi bi-star" aria-hidden="true"></i></span>
          <div>
            <strong>{{ __('register.delivery_benefit_reviews_title') }}</strong>
            <p>{{ __('register.delivery_benefit_reviews_desc') }}</p>
          </div>
        </div>
      </div>

      <div class="patient-register-next-step delivery-register-next-step">
        <span class="patient-register-next-step-icon"><i class="bi bi-envelope-check" aria-hidden="true"></i></span>
        <div>
          <small>{{ __('register.delivery_next_step_label') }}</small>
          <strong>{{ __('register.delivery_next_step_title') }}</strong>
          <p>{{ __('register.delivery_next_step_desc') }}</p>
        </div>
      </div>
    </aside>
  </section>

</div>
@endsection
