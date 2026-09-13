@extends('layouts.app')
@section('title', __('login.title'))
@section('content')
<div class="login-page">
  <section class="login-shell login-layout" aria-labelledby="login-title">
    <div class="login-card">
      <a class="login-back" href="{{ route('home') }}">
        <i class="bi bi-arrow-left" aria-hidden="true"></i>
        <span>{{ __('login.back') }}</span>
      </a>

      <div class="login-card-copy">
        <span class="login-eyebrow">{{ __('login.eyebrow') }}</span>
        <h1 id="login-title">{{ __('login.heading_prefix') }} <span>{{ __('login.heading_highlight') }}</span></h1>
        <p>{{ __('login.subtitle') }}</p>
      </div>

      <form method="POST" action="{{ route('login') }}" class="login-form" novalidate>
        @csrf

        <div class="login-field">
          <label for="identifier">{{ __('login.identifier') }}</label>
          <div class="login-control @error('identifier') is-invalid @enderror">
            <i class="bi bi-person" aria-hidden="true"></i>
            <input
              type="text"
              id="identifier"
              name="identifier"
              value="{{ old('identifier') }}"
              autocomplete="username"
              placeholder="{{ __('login.identifier_placeholder') }}"
              aria-invalid="{{ $errors->has('identifier') ? 'true' : 'false' }}"
              required
              autofocus
            >
          </div>
          @error('identifier')<p class="login-error">{{ $message }}</p>@enderror
        </div>

        <div class="login-field">
          <label for="password">{{ __('login.password') }}</label>
          <div class="login-control login-password-control @error('password') is-invalid @enderror">
            <i class="bi bi-lock" aria-hidden="true"></i>
            <input
              type="password"
              id="password"
              name="password"
              autocomplete="current-password"
              placeholder="{{ __('login.password_placeholder') }}"
              aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
              required
            >
            <button
              type="button"
              class="login-password-toggle"
              data-login-password-toggle
              aria-controls="password"
              aria-label="{{ __('login.show_password') }}"
              title="{{ __('login.show_password') }}"
            >
              <i class="bi bi-eye-slash" aria-hidden="true"></i>
            </button>
          </div>
          @error('password')<p class="login-error">{{ $message }}</p>@enderror
        </div>

        <div class="login-forgot-row">
          <a href="{{ route('password.forgot') }}">{{ __('login.forgot_password') }}</a>
        </div>

        <button type="submit" class="login-submit">
          <span>{{ __('login.submit') }}</span>
          <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </button>
      </form>

      <div class="login-register-divider" aria-hidden="true">
        <span></span><em>{{ __('login.new_here') }}</em><span></span>
      </div>

      <a class="login-create-account" href="{{ route('register.choose') }}">
        {{ __('login.create_account') }}
      </a>
    </div>

    <aside class="login-aside" aria-label="{{ __('login.aside_label') }}">
      <span class="login-aside-eyebrow">{{ __('login.aside_eyebrow') }}</span>
      <h2>{{ __('login.aside_title_prefix') }} <span>{{ __('login.aside_title_highlight') }}</span></h2>
      <p class="login-aside-lead">{{ __('login.aside_desc') }}</p>

      <div class="login-benefits">
        <div class="login-benefit">
          <span><i class="bi bi-shield-check" aria-hidden="true"></i></span>
          <div>
            <strong>{{ __('login.benefit_protected_title') }}</strong>
            <p>{{ __('login.benefit_protected_desc') }}</p>
          </div>
        </div>
        <div class="login-benefit">
          <span><i class="bi bi-envelope-check" aria-hidden="true"></i></span>
          <div>
            <strong>{{ __('login.benefit_otp_title') }}</strong>
            <p>{{ __('login.benefit_otp_desc') }}</p>
          </div>
        </div>
        <div class="login-benefit">
          <span><i class="bi bi-grid-1x2" aria-hidden="true"></i></span>
          <div>
            <strong>{{ __('login.benefit_workspace_title') }}</strong>
            <p>{{ __('login.benefit_workspace_desc') }}</p>
          </div>
        </div>
      </div>

      <div class="login-aside-signoff">{{ __('login.aside_signoff') }}</div>
    </aside>
  </section>

  <footer class="login-footer">
    <div class="login-shell login-footer-grid">
      <div class="login-footer-item">
        <i class="bi bi-shield-check" aria-hidden="true"></i>
        <div><strong>{{ __('login.footer_secure_title') }}</strong><p>{{ __('login.footer_secure_desc') }}</p></div>
      </div>
      <div class="login-footer-item">
        <i class="bi bi-people" aria-hidden="true"></i>
        <div><strong>{{ __('login.footer_platform_title') }}</strong><p>{{ __('login.footer_platform_desc') }}</p></div>
      </div>
      <div class="login-footer-item">
        <i class="bi bi-heart" aria-hidden="true"></i>
        <div><strong>{{ __('login.footer_better_title') }}</strong><p>{{ __('login.footer_better_desc') }}</p></div>
      </div>
    </div>
  </footer>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const toggle = document.querySelector('[data-login-password-toggle]');
  const password = document.getElementById('password');

  if (!toggle || !password) return;

  toggle.addEventListener('click', function () {
    const showing = password.type === 'text';
    password.type = showing ? 'password' : 'text';
    const icon = toggle.querySelector('i');
    if (icon) {
      icon.classList.toggle('bi-eye', !showing);
      icon.classList.toggle('bi-eye-slash', showing);
    }
    toggle.setAttribute('aria-label', showing ? @json(__('login.show_password')) : @json(__('login.hide_password')));
    toggle.setAttribute('title', showing ? @json(__('login.show_password')) : @json(__('login.hide_password')));
  });
});
</script>
@endpush
