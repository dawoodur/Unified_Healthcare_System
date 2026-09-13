@extends('layouts.app')

@section('title', __('otp.title'))

@section('content')
<div class="otp-page">
  <div class="otp-shell">
    <a class="otp-back" href="{{ route('login') }}">
      <i class="bi bi-arrow-left" aria-hidden="true"></i>
      <span>{{ __('otp.back_to_login') }}</span>
    </a>

    <section class="otp-panel" aria-labelledby="otp-heading">
      <div class="otp-form-side">
        <span class="otp-eyebrow">{{ __('otp.eyebrow') }}</span>
        <h1 id="otp-heading" class="otp-title">
          {{ __('otp.heading_prefix') }} <span>{{ __('otp.heading_highlight') }}</span>
        </h1>
        <p class="otp-desc">{{ __('otp.subtitle') }}</p>

        @if (session('resend_message'))
          <div class="otp-status otp-status-success" role="status">
            <span class="otp-status-icon"><i class="bi bi-check2" aria-hidden="true"></i></span>
            <div>
              <strong>{{ __('otp.sent_title') }}</strong>
              <p>{{ session('resend_message') }}</p>
            </div>
          </div>
        @else
          <div class="otp-status">
            <span class="otp-status-icon"><i class="bi bi-envelope" aria-hidden="true"></i></span>
            <div>
              <strong>{{ __('otp.sent_title') }}</strong>
              <p>{{ __('otp.sent_desc') }}</p>
            </div>
          </div>
        @endif

        <form method="POST" action="{{ route('otp.verify') }}" novalidate class="otp-form">
          @csrf
          <div class="otp-code-label">
            <label for="otp_code">{{ __('otp.code_label') }}</label>
            <small>{{ __('otp.numbers_only') }}</small>
          </div>
          <input
            id="otp_code"
            type="text"
            name="otp_code"
            class="otp-code-input {{ $errors->has('otp_code') ? 'is-invalid' : '' }}"
            maxlength="6"
            pattern="\d{6}"
            inputmode="numeric"
            autocomplete="one-time-code"
            placeholder="{{ __('otp.code_placeholder') }}"
            aria-describedby="{{ $errors->has('otp_code') ? 'otp-code-error' : '' }}"
            autofocus
            required
          >
          @error('otp_code')
            <p id="otp-code-error" class="otp-error" role="alert">{{ $message }}</p>
          @enderror

          <button type="submit" class="otp-primary">
            <span>{{ __('otp.verify') }}</span>
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
          </button>
        </form>

        <div class="otp-resend-row">
          <div class="otp-resend-copy">
            <strong>{{ __('otp.resend_title') }}</strong>
            <span>{{ __('otp.resend_desc') }}</span>
          </div>
          <form method="POST" action="{{ route('otp.resend') }}">
            @csrf
            <button type="submit" class="otp-secondary">{{ __('otp.resend') }}</button>
          </form>
        </div>
      </div>

      <aside class="otp-aside" aria-label="{{ __('otp.aside_eyebrow') }}">
        <span class="otp-aside-kicker">{{ __('otp.aside_eyebrow') }}</span>
        <h2>{{ __('otp.aside_title') }}</h2>
        <p class="otp-aside-desc">{{ __('otp.aside_desc') }}</p>

        <div class="otp-flow">
          <div class="otp-step">
            <span class="otp-step-num">01</span>
            <div>
              <strong>{{ __('otp.step_one_title') }}</strong>
              <span>{{ __('otp.step_one_desc') }}</span>
            </div>
          </div>
          <div class="otp-step">
            <span class="otp-step-num">02</span>
            <div>
              <strong>{{ __('otp.step_two_title') }}</strong>
              <span>{{ __('otp.step_two_desc') }}</span>
            </div>
          </div>
          <div class="otp-step">
            <span class="otp-step-num">03</span>
            <div>
              <strong>{{ __('otp.step_three_title') }}</strong>
              <span>{{ __('otp.step_three_desc') }}</span>
            </div>
          </div>
        </div>

        <p class="otp-aside-note">
          <strong>{{ __('otp.aside_note_label') }}</strong>
          {{ __('otp.aside_note') }}
        </p>
      </aside>
    </section>

    <div class="otp-facts" aria-label="Verification protections">
      <div class="otp-fact">
        <strong>{{ __('otp.footer_email_title') }}</strong>
        <span>{{ __('otp.footer_email_desc') }}</span>
      </div>
      <div class="otp-fact">
        <strong>{{ __('otp.footer_expiry_title') }}</strong>
        <span>{{ __('otp.footer_expiry_desc') }}</span>
      </div>
      <div class="otp-fact">
        <strong>{{ __('otp.footer_retry_title') }}</strong>
        <span>{{ __('otp.footer_retry_desc') }}</span>
      </div>
    </div>
  </div>
</div>
@endsection
