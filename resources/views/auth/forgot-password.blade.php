@extends('layouts.app')
@section('title', __('forgot.title'))
@section('content')
<div class="forgot-page">
  <section class="forgot-shell forgot-layout" aria-labelledby="forgot-title">
    <div class="forgot-card">
      <a class="forgot-back" href="{{ route('login') }}">
        <i class="bi bi-arrow-left" aria-hidden="true"></i>
        <span>{{ __('forgot.back_to_login') }}</span>
      </a>

      <div class="forgot-card-copy">
        <span class="forgot-eyebrow">{{ __('forgot.eyebrow') }}</span>
        <h1 id="forgot-title">{{ __('forgot.heading_prefix') }} <span>{{ __('forgot.heading_highlight') }}</span></h1>
        <p>{{ __('forgot.subtitle') }}</p>
      </div>

      <form method="POST" action="{{ route('password.forgot.send') }}" class="forgot-form" novalidate>
        @csrf
        <div class="forgot-field">
          <label for="email">{{ __('forgot.email') }}</label>
          <div class="forgot-control @error('email') is-invalid @enderror">
            <i class="bi bi-envelope" aria-hidden="true"></i>
            <input
              type="email"
              id="email"
              name="email"
              value="{{ old('email') }}"
              autocomplete="email"
              placeholder="{{ __('forgot.email_placeholder') }}"
              aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
              required
              autofocus
            >
          </div>
          @error('email')<p class="forgot-error">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="forgot-submit">
          <span>{{ __('forgot.send_code') }}</span>
          <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </button>
      </form>

      <div class="forgot-login-divider" aria-hidden="true">
        <span></span><em>{{ __('forgot.remember_password') }}</em><span></span>
      </div>

      <a class="forgot-login-button" href="{{ route('login') }}">
        {{ __('forgot.back_to_login') }}
      </a>
    </div>

    <aside class="forgot-aside" aria-label="{{ __('forgot.aside_label') }}">
      <span class="forgot-aside-eyebrow">{{ __('forgot.aside_eyebrow') }}</span>
      <h2>{{ __('forgot.aside_title_prefix') }} <span>{{ __('forgot.aside_title_highlight') }}</span></h2>
      <p class="forgot-aside-lead">{{ __('forgot.aside_desc') }}</p>

      <div class="forgot-benefits">
        <div class="forgot-benefit">
          <span><i class="bi bi-envelope-check" aria-hidden="true"></i></span>
          <div>
            <strong>{{ __('forgot.step_verify_title') }}</strong>
            <p>{{ __('forgot.step_verify_desc') }}</p>
          </div>
        </div>
        <div class="forgot-benefit">
          <span><i class="bi bi-shield-lock" aria-hidden="true"></i></span>
          <div>
            <strong>{{ __('forgot.step_password_title') }}</strong>
            <p>{{ __('forgot.step_password_desc') }}</p>
          </div>
        </div>
        <div class="forgot-benefit">
          <span><i class="bi bi-shield-check" aria-hidden="true"></i></span>
          <div>
            <strong>{{ __('forgot.step_protected_title') }}</strong>
            <p>{{ __('forgot.step_protected_desc') }}</p>
          </div>
        </div>
      </div>

      <div class="forgot-aside-signoff">{{ __('forgot.aside_signoff') }}</div>
    </aside>
  </section>

  <footer class="forgot-footer">
    <div class="forgot-shell forgot-footer-grid">
      <div class="forgot-footer-item">
        <i class="bi bi-shield-check" aria-hidden="true"></i>
        <div><strong>{{ __('forgot.footer_secure_title') }}</strong><p>{{ __('forgot.footer_secure_desc') }}</p></div>
      </div>
      <div class="forgot-footer-item">
        <i class="bi bi-people" aria-hidden="true"></i>
        <div><strong>{{ __('forgot.footer_platform_title') }}</strong><p>{{ __('forgot.footer_platform_desc') }}</p></div>
      </div>
      <div class="forgot-footer-item">
        <i class="bi bi-heart" aria-hidden="true"></i>
        <div><strong>{{ __('forgot.footer_better_title') }}</strong><p>{{ __('forgot.footer_better_desc') }}</p></div>
      </div>
    </div>
  </footer>
</div>
@endsection
