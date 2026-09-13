@extends('layouts.app')
@section('title', __('register.choice_page_title') . ' — Telemedicine Platform')

@section('content')
<section class="register-choice-page" aria-labelledby="register-choice-title">
  <div class="register-choice-shell">
    <header class="register-choice-hero">
      <div class="register-choice-intro">
        <div class="register-choice-eyebrow">
          <span aria-hidden="true"></span>
          {{ __('register.choice_eyebrow') }}
        </div>
        <h1 id="register-choice-title">{{ __('register.choice_title') }}</h1>
        <p>{{ __('register.choice_subtitle') }}</p>
      </div>

      <aside class="register-choice-note" aria-label="{{ __('register.choice_note_title') }}">
        <strong>{{ __('register.choice_note_title') }}</strong>
        <p>{{ __('register.choice_note_desc') }}</p>
      </aside>
    </header>

    <div class="register-choice-grid">
      <a class="register-role-card register-role-card--featured" href="{{ route('register.patient') }}">
        <span class="register-role-icon"><i class="bi bi-person" aria-hidden="true"></i></span>
        <h2>{{ __('register.patient') }}</h2>
        <p class="register-role-desc">{{ __('register.choice_patient_desc') }}</p>
        <ul class="register-role-benefits">
          <li><i class="bi bi-check2" aria-hidden="true"></i><span>{{ __('register.choice_patient_benefit_1') }}</span></li>
          <li><i class="bi bi-check2" aria-hidden="true"></i><span>{{ __('register.choice_patient_benefit_2') }}</span></li>
          <li><i class="bi bi-check2" aria-hidden="true"></i><span>{{ __('register.choice_patient_benefit_3') }}</span></li>
        </ul>
        <span class="register-role-action">
          <span>{{ __('register.continue') }}</span>
          <span class="register-role-arrow"><i class="bi bi-arrow-right" aria-hidden="true"></i></span>
        </span>
      </a>

      <a class="register-role-card" href="{{ route('register.doctor') }}">
        <span class="register-role-icon"><i class="bi bi-stethoscope" aria-hidden="true"></i></span>
        <h2>{{ __('register.doctor') }}</h2>
        <p class="register-role-desc">{{ __('register.choice_doctor_desc') }}</p>
        <ul class="register-role-benefits">
          <li><i class="bi bi-check2" aria-hidden="true"></i><span>{{ __('register.choice_doctor_benefit_1') }}</span></li>
          <li><i class="bi bi-check2" aria-hidden="true"></i><span>{{ __('register.choice_doctor_benefit_2') }}</span></li>
          <li><i class="bi bi-check2" aria-hidden="true"></i><span>{{ __('register.choice_doctor_benefit_3') }}</span></li>
        </ul>
        <span class="register-role-action">
          <span>{{ __('register.continue') }}</span>
          <span class="register-role-arrow"><i class="bi bi-arrow-right" aria-hidden="true"></i></span>
        </span>
      </a>

      <a class="register-role-card" href="{{ route('register.hospital') }}">
        <span class="register-role-icon"><i class="bi bi-hospital" aria-hidden="true"></i></span>
        <h2>{{ __('register.hospital') }}</h2>
        <p class="register-role-desc">{{ __('register.choice_hospital_desc') }}</p>
        <ul class="register-role-benefits">
          <li><i class="bi bi-check2" aria-hidden="true"></i><span>{{ __('register.choice_hospital_benefit_1') }}</span></li>
          <li><i class="bi bi-check2" aria-hidden="true"></i><span>{{ __('register.choice_hospital_benefit_2') }}</span></li>
          <li><i class="bi bi-check2" aria-hidden="true"></i><span>{{ __('register.choice_hospital_benefit_3') }}</span></li>
        </ul>
        <span class="register-role-action">
          <span>{{ __('register.continue') }}</span>
          <span class="register-role-arrow"><i class="bi bi-arrow-right" aria-hidden="true"></i></span>
        </span>
      </a>

      <a class="register-role-card" href="{{ route('register.pharmacy') }}">
        <span class="register-role-icon"><i class="bi bi-capsule" aria-hidden="true"></i></span>
        <h2>{{ __('register.pharmacy') }}</h2>
        <p class="register-role-desc">{{ __('register.choice_pharmacy_desc') }}</p>
        <ul class="register-role-benefits">
          <li><i class="bi bi-check2" aria-hidden="true"></i><span>{{ __('register.choice_pharmacy_benefit_1') }}</span></li>
          <li><i class="bi bi-check2" aria-hidden="true"></i><span>{{ __('register.choice_pharmacy_benefit_2') }}</span></li>
          <li><i class="bi bi-check2" aria-hidden="true"></i><span>{{ __('register.choice_pharmacy_benefit_3') }}</span></li>
        </ul>
        <span class="register-role-action">
          <span>{{ __('register.continue') }}</span>
          <span class="register-role-arrow"><i class="bi bi-arrow-right" aria-hidden="true"></i></span>
        </span>
      </a>
    </div>

    <div class="register-delivery-row">
      <span>{{ __('register.delivery_prompt') }}</span>
      <a href="{{ route('register.delivery') }}">{{ __('register.delivery_cta') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </div>

    <section class="register-login-panel" aria-label="{{ __('register.already_have_account') }}">
      <div class="register-login-visual" aria-hidden="true">
        <i class="bi bi-people"></i>
        <span><i class="bi bi-person"></i></span>
      </div>
      <div class="register-login-divider" aria-hidden="true"></div>
      <div class="register-login-copy">
        <small>{{ __('register.already_have_account') }}</small>
        <strong>{{ __('register.welcome_back') }}</strong>
        <p>{{ __('register.login_prompt') }}</p>
      </div>
      <a class="register-login-action" href="{{ route('login') }}">
        <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
        <span>{{ __('register.go_to_login') }}</span>
      </a>
    </section>
  </div>

  <footer class="register-choice-footer">
    <div class="register-choice-shell register-footer-grid">
      <div class="register-footer-statement">
        <strong>{{ __('register.footer_better_access') }}</strong>
        <span>{{ __('register.footer_healthier_lives') }}</span>
      </div>

      <div class="register-footer-item">
        <i class="bi bi-shield-check" aria-hidden="true"></i>
        <div>
          <strong>{{ __('register.footer_secure_title') }}</strong>
          <p>{{ __('register.footer_secure_desc') }}</p>
        </div>
      </div>

      <div class="register-footer-item">
        <i class="bi bi-people" aria-hidden="true"></i>
        <div>
          <strong>{{ __('register.footer_trusted_title') }}</strong>
          <p>{{ __('register.footer_trusted_desc') }}</p>
        </div>
      </div>

      <div class="register-footer-item">
        <i class="bi bi-heart" aria-hidden="true"></i>
        <div>
          <strong>{{ __('register.footer_healthier_title') }}</strong>
          <p>{{ __('register.footer_healthier_desc') }}</p>
        </div>
      </div>
    </div>
  </footer>
</section>
@endsection
