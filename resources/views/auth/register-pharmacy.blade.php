@extends('layouts.app')
@section('title', __('register.pharmacy_page_title'))
@section('content')
<div class="patient-register-page pharmacy-register-page">
  <section class="patient-register-shell patient-register-hero" aria-labelledby="pharmacy-register-title">
    <div class="patient-register-intro">
      <a class="patient-register-back" href="{{ route('register.choose') }}">
        <i class="bi bi-arrow-left" aria-hidden="true"></i>
        <span>{{ __('register.pharmacy_back') }}</span>
      </a>
      <h1 id="pharmacy-register-title">
        {{ __('register.pharmacy_title_prefix') }} <span>{{ __('register.pharmacy_title_highlight') }}</span>
      </h1>
      <p>{{ __('register.pharmacy_subtitle') }}</p>
    </div>

    <ol class="patient-register-progress" aria-label="{{ __('register.pharmacy_progress_label') }}">
      <li class="is-active">
        <span>1</span>
        <strong>{{ __('register.pharmacy_progress_details') }}</strong>
      </li>
      <li>
        <span>2</span>
        <strong>{{ __('register.pharmacy_progress_verify') }}</strong>
      </li>
      <li>
        <span>3</span>
        <strong>{{ __('register.pharmacy_progress_ready') }}</strong>
      </li>
    </ol>
  </section>

  <section class="patient-register-shell patient-register-layout">
    <div class="patient-register-form-card">
      <div class="patient-register-form-heading">
        <span class="patient-register-form-icon" aria-hidden="true"><i class="bi bi-shop"></i></span>
        <div>
          <h2>{{ __('register.pharmacy_form_title') }}</h2>
          <p>{{ __('register.pharmacy_form_subtitle') }}</p>
        </div>
      </div>

      <form method="POST" action="{{ route('register.pharmacy') }}" class="patient-register-form" novalidate>
        @csrf

        <fieldset class="patient-register-section">
          <legend>{{ __('register.pharmacy_section_information') }}</legend>
          <div class="patient-register-grid patient-register-grid-2">
            <div class="patient-register-field">
              <label for="pharmacy_name">{{ __('register.pharmacy_name') }} <span>*</span></label>
              <div class="patient-register-control @error('pharmacy_name') is-invalid @enderror">
                <i class="bi bi-shop" aria-hidden="true"></i>
                <input type="text" id="pharmacy_name" name="pharmacy_name" value="{{ old('pharmacy_name') }}" autocomplete="organization" placeholder="{{ __('register.pharmacy_name_placeholder') }}" required>
              </div>
              @error('pharmacy_name')<p class="patient-register-error">{{ $message }}</p>@enderror
            </div>

            <div class="patient-register-field">
              <label for="etin_number">{{ __('register.pharmacy_etin') }} <span>*</span></label>
              <div class="patient-register-control @error('etin_number') is-invalid @enderror">
                <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                <input type="text" id="etin_number" name="etin_number" value="{{ old('etin_number') }}" placeholder="{{ __('register.pharmacy_etin_placeholder') }}" required>
              </div>
              @error('etin_number')<p class="patient-register-error">{{ $message }}</p>@enderror
            </div>

            <div class="patient-register-field">
              <label for="email">{{ __('register.pharmacy_email') }} <span>*</span></label>
              <div class="patient-register-control @error('email') is-invalid @enderror">
                <i class="bi bi-envelope" aria-hidden="true"></i>
                <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="{{ __('register.pharmacy_email_placeholder') }}" required>
              </div>
              @error('email')<p class="patient-register-error">{{ $message }}</p>@enderror
            </div>

            <div class="patient-register-field">
              <label for="mobile">{{ __('register.pharmacy_mobile') }} <small>{{ __('register.patient_optional') }}</small></label>
              <div class="patient-register-control @error('mobile') is-invalid @enderror">
                <i class="bi bi-telephone" aria-hidden="true"></i>
                <input type="tel" id="mobile" name="mobile" value="{{ old('mobile') }}" autocomplete="tel" inputmode="tel" placeholder="{{ __('register.pharmacy_mobile_placeholder') }}">
              </div>
              @error('mobile')<p class="patient-register-error">{{ $message }}</p>@enderror
            </div>

            <div class="patient-register-field patient-register-field-wide">
              <label for="address">{{ __('register.pharmacy_address') }} <small>{{ __('register.patient_optional') }}</small></label>
              <div class="patient-register-control @error('address') is-invalid @enderror">
                <i class="bi bi-geo-alt" aria-hidden="true"></i>
                <input type="text" id="address" name="address" value="{{ old('address') }}" autocomplete="street-address" placeholder="{{ __('register.pharmacy_address_placeholder') }}">
              </div>
              @error('address')<p class="patient-register-error">{{ $message }}</p>@enderror
            </div>
          </div>
        </fieldset>

        <fieldset class="patient-register-section">
          <legend>{{ __('register.pharmacy_section_security') }}</legend>
          <div class="patient-register-grid patient-register-grid-2">
            <div class="patient-register-field">
              <label for="password">{{ __('register.pharmacy_password') }} <span>*</span></label>
              <div class="patient-register-control @error('password') is-invalid @enderror">
                <i class="bi bi-lock" aria-hidden="true"></i>
                <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" placeholder="{{ __('register.patient_password_placeholder') }}" required>
              </div>
              @error('password')<p class="patient-register-error">{{ $message }}</p>@enderror
            </div>

            <div class="patient-register-field">
              <label for="password_confirmation">{{ __('register.pharmacy_confirm_password') }} <span>*</span></label>
              <div class="patient-register-control">
                <i class="bi bi-shield-lock" aria-hidden="true"></i>
                <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" autocomplete="new-password" placeholder="{{ __('register.patient_confirm_placeholder') }}" required>
              </div>
            </div>
          </div>
        </fieldset>

        <div class="patient-register-security-note pharmacy-register-security-note">
          <i class="bi bi-shield-check" aria-hidden="true"></i>
          <div>
            <strong>{{ __('register.pharmacy_security_title') }}</strong>
            <p>{{ __('register.pharmacy_security_desc') }}</p>
          </div>
        </div>

        <div class="patient-register-actions">
          <a class="patient-register-cancel" href="{{ route('register.choose') }}">{{ __('register.patient_cancel') }}</a>
          <button type="submit" class="patient-register-submit">
            <span>{{ __('register.pharmacy_create_account') }}</span>
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
          </button>
        </div>
      </form>
    </div>

    <aside class="patient-register-aside pharmacy-register-aside" aria-label="{{ __('register.pharmacy_aside_label') }}">
      <span class="patient-register-aside-eyebrow">{{ __('register.pharmacy_aside_eyebrow') }}</span>
      <h2>{{ __('register.pharmacy_aside_title_prefix') }} <span>{{ __('register.pharmacy_aside_title_highlight') }}</span></h2>
      <p class="patient-register-aside-lead">{{ __('register.pharmacy_aside_desc') }}</p>

      <div class="patient-register-benefits">
        <div class="patient-register-benefit">
          <span><i class="bi bi-bag-check" aria-hidden="true"></i></span>
          <div><strong>{{ __('register.pharmacy_benefit_orders_title') }}</strong><p>{{ __('register.pharmacy_benefit_orders_desc') }}</p></div>
        </div>
        <div class="patient-register-benefit">
          <span><i class="bi bi-box-seam" aria-hidden="true"></i></span>
          <div><strong>{{ __('register.pharmacy_benefit_inventory_title') }}</strong><p>{{ __('register.pharmacy_benefit_inventory_desc') }}</p></div>
        </div>
        <div class="patient-register-benefit">
          <span><i class="bi bi-capsule" aria-hidden="true"></i></span>
          <div><strong>{{ __('register.pharmacy_benefit_catalog_title') }}</strong><p>{{ __('register.pharmacy_benefit_catalog_desc') }}</p></div>
        </div>
        <div class="patient-register-benefit">
          <span><i class="bi bi-star" aria-hidden="true"></i></span>
          <div><strong>{{ __('register.pharmacy_benefit_reviews_title') }}</strong><p>{{ __('register.pharmacy_benefit_reviews_desc') }}</p></div>
        </div>
      </div>

      <div class="patient-register-next-step pharmacy-register-next-step">
        <span class="patient-register-next-step-icon"><i class="bi bi-envelope-check" aria-hidden="true"></i></span>
        <div>
          <small>{{ __('register.pharmacy_next_step_label') }}</small>
          <strong>{{ __('register.pharmacy_next_step_title') }}</strong>
          <p>{{ __('register.pharmacy_next_step_desc') }}</p>
        </div>
      </div>
    </aside>
  </section>

</div>
@endsection
