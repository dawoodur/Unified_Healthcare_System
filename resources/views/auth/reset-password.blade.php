@extends('layouts.app')

@section('title', __('reset.title'))

@section('content')
<div class="reset-page">
  <section class="reset-shell reset-layout" aria-labelledby="reset-title">
    <div class="reset-card">
      <a class="reset-back" href="{{ route('login') }}">
        <i class="bi bi-arrow-left" aria-hidden="true"></i>
        <span>{{ __('reset.back') }}</span>
      </a>

      <div class="reset-card-copy">
        <span class="reset-eyebrow">{{ __('reset.eyebrow') }}</span>
        <h1 id="reset-title">{{ __('reset.heading_prefix') }} <span>{{ __('reset.heading_highlight') }}</span></h1>
        <p>{{ __('reset.subtitle') }}</p>
      </div>

      <form method="POST" action="{{ route('password.reset') }}" class="reset-form" novalidate>
        @csrf

        <div class="reset-field">
          <label for="password">{{ __('reset.password') }}</label>
          <div class="reset-control reset-password-control @error('password') is-invalid @enderror">
            <i class="bi bi-lock" aria-hidden="true"></i>
            <input
              type="password"
              id="password"
              name="password"
              autocomplete="new-password"
              minlength="8"
              placeholder="{{ __('reset.password_placeholder') }}"
              aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
              required
              autofocus
            >
            <button
              type="button"
              class="reset-password-toggle"
              data-reset-password-toggle="password"
              aria-controls="password"
              aria-label="{{ __('reset.show_password') }}"
              title="{{ __('reset.show_password') }}"
            >
              <i class="bi bi-eye-slash" aria-hidden="true"></i>
            </button>
          </div>
          @error('password')<p class="reset-error">{{ $message }}</p>@enderror
        </div>

        <div class="reset-requirements" aria-label="{{ __('reset.requirements_label') }}">
          <div><i class="bi bi-check-circle-fill" aria-hidden="true"></i><span>{{ __('reset.requirement_length') }}</span></div>
          <div><i class="bi bi-check-circle-fill" aria-hidden="true"></i><span>{{ __('reset.requirement_unique') }}</span></div>
          <div><i class="bi bi-check-circle-fill" aria-hidden="true"></i><span>{{ __('reset.requirement_private') }}</span></div>
        </div>

        <div class="reset-field">
          <label for="password_confirmation">{{ __('reset.confirm_password') }}</label>
          <div class="reset-control reset-password-control">
            <i class="bi bi-lock" aria-hidden="true"></i>
            <input
              type="password"
              id="password_confirmation"
              name="password_confirmation"
              autocomplete="new-password"
              minlength="8"
              placeholder="{{ __('reset.confirm_placeholder') }}"
              required
            >
            <button
              type="button"
              class="reset-password-toggle"
              data-reset-password-toggle="password_confirmation"
              aria-controls="password_confirmation"
              aria-label="{{ __('reset.show_password') }}"
              title="{{ __('reset.show_password') }}"
            >
              <i class="bi bi-eye-slash" aria-hidden="true"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="reset-submit">
          <span>{{ __('reset.submit') }}</span>
          <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </button>
      </form>
    </div>

    <aside class="reset-aside" aria-label="{{ __('reset.aside_label') }}">
      <span class="reset-aside-eyebrow">{{ __('reset.aside_eyebrow') }}</span>
      <h2>{{ __('reset.aside_title_prefix') }} <span>{{ __('reset.aside_title_highlight') }}</span></h2>
      <p class="reset-aside-lead">{{ __('reset.aside_desc') }}</p>

      <div class="reset-benefits">
        <div class="reset-benefit">
          <span><i class="bi bi-lock" aria-hidden="true"></i></span>
          <div>
            <strong>{{ __('reset.benefit_secure_title') }}</strong>
            <p>{{ __('reset.benefit_secure_desc') }}</p>
          </div>
        </div>
        <div class="reset-benefit">
          <span><i class="bi bi-shield-check" aria-hidden="true"></i></span>
          <div>
            <strong>{{ __('reset.benefit_protected_title') }}</strong>
            <p>{{ __('reset.benefit_protected_desc') }}</p>
          </div>
        </div>
        <div class="reset-benefit">
          <span><i class="bi bi-check2-circle" aria-hidden="true"></i></span>
          <div>
            <strong>{{ __('reset.benefit_verified_title') }}</strong>
            <p>{{ __('reset.benefit_verified_desc') }}</p>
          </div>
        </div>
      </div>

      <div class="reset-aside-signoff">{{ __('reset.aside_signoff') }}</div>
    </aside>
  </section>

  <footer class="reset-footer">
    <div class="reset-shell reset-footer-grid">
      <div class="reset-footer-item">
        <i class="bi bi-shield-check" aria-hidden="true"></i>
        <div><strong>{{ __('reset.footer_secure_title') }}</strong><p>{{ __('reset.footer_secure_desc') }}</p></div>
      </div>
      <div class="reset-footer-item">
        <i class="bi bi-people" aria-hidden="true"></i>
        <div><strong>{{ __('reset.footer_platform_title') }}</strong><p>{{ __('reset.footer_platform_desc') }}</p></div>
      </div>
      <div class="reset-footer-item">
        <i class="bi bi-heart" aria-hidden="true"></i>
        <div><strong>{{ __('reset.footer_better_title') }}</strong><p>{{ __('reset.footer_better_desc') }}</p></div>
      </div>
    </div>
  </footer>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-reset-password-toggle]').forEach(function (toggle) {
    const targetId = toggle.getAttribute('data-reset-password-toggle');
    const input = document.getElementById(targetId);
    if (!input) return;

    toggle.addEventListener('click', function () {
      const showing = input.type === 'text';
      input.type = showing ? 'password' : 'text';
      const icon = toggle.querySelector('i');
      if (icon) {
        icon.classList.toggle('bi-eye', !showing);
        icon.classList.toggle('bi-eye-slash', showing);
      }
      const label = showing ? @json(__('reset.show_password')) : @json(__('reset.hide_password'));
      toggle.setAttribute('aria-label', label);
      toggle.setAttribute('title', label);
    });
  });
});
</script>
@endpush
