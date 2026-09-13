@extends('layouts.app')
@section('title', __('home.page_title'))
@section('content')
<div class="landing-page">
  <section class="landing-hero" aria-labelledby="landing-heading">
    <div class="landing-shell landing-hero-grid">
      <div class="landing-hero-copy">
        <div class="landing-eyebrow">
          <span class="landing-eyebrow-dot" aria-hidden="true"></span>
          {{ __('home.eyebrow') }}
        </div>

        <h1 id="landing-heading">
          {{ __('home.hero_title_prefix') }}
          <span>{{ __('home.hero_title_accent') }}</span>
        </h1>

        <p class="landing-lead">{{ __('home.tagline') }}</p>

        <a href="{{ route('register.choose') }}" class="landing-primary-action">
          {{ __('home.get_started') }}
          <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>

        <div class="landing-hero-assurances" aria-label="{{ __('home.assurances_label') }}">
          <div class="landing-assurance-item">
            <span class="landing-assurance-icon"><i class="bi bi-shield-check" aria-hidden="true"></i></span>
            <div>
              <strong>{{ __('home.secure_access_title') }}</strong>
              <small>{{ __('home.secure_access_desc') }}</small>
            </div>
          </div>
          <div class="landing-assurance-item">
            <span class="landing-assurance-icon"><i class="bi bi-activity" aria-hidden="true"></i></span>
            <div>
              <strong>{{ __('home.care_continuity_title') }}</strong>
              <small>{{ __('home.care_continuity_desc') }}</small>
            </div>
          </div>
          <div class="landing-assurance-item">
            <span class="landing-assurance-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
            <div>
              <strong>{{ __('home.trusted_platform_title') }}</strong>
              <small>{{ __('home.trusted_platform_desc') }}</small>
            </div>
          </div>
        </div>
      </div>

      <div class="landing-care-stage" aria-label="{{ __('home.care_hub_label') }}">
        <div class="landing-care-aura" aria-hidden="true"></div>

        <div class="landing-care-card">
          <div class="landing-care-heading">
            <div>
              <span class="landing-section-kicker">{{ __('home.care_hub_label') }}</span>
              <h2>{{ __('home.care_hub_title') }}</h2>
            </div>
            <span class="landing-connected-badge"><span aria-hidden="true"></span>{{ __('home.connected') }}</span>
          </div>

          <div class="landing-care-grid">
            <div class="landing-care-tile landing-care-tile-primary">
              <span class="landing-care-icon"><i class="bi bi-plus-lg" aria-hidden="true"></i></span>
              <span class="landing-care-arrow"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></span>
              <strong>{{ __('home.care_doctors_title') }}</strong>
              <small>{{ __('home.care_doctors_desc') }}</small>
            </div>
            <div class="landing-care-tile">
              <span class="landing-care-icon"><i class="bi bi-file-medical" aria-hidden="true"></i></span>
              <strong>{{ __('home.care_records_title') }}</strong>
              <small>{{ __('home.care_records_desc') }}</small>
            </div>
            <div class="landing-care-tile">
              <span class="landing-care-icon"><i class="bi bi-capsule-pill" aria-hidden="true"></i></span>
              <strong>{{ __('home.care_medicine_title') }}</strong>
              <small>{{ __('home.care_medicine_desc') }}</small>
            </div>
            <div class="landing-care-tile">
              <span class="landing-care-icon"><i class="bi bi-hospital" aria-hidden="true"></i></span>
              <strong>{{ __('home.care_hospital_title') }}</strong>
              <small>{{ __('home.care_hospital_desc') }}</small>
            </div>
          </div>
        </div>

        <div class="landing-verified-card">
          <span class="landing-mini-check"><i class="bi bi-check-lg" aria-hidden="true"></i></span>
          <div>
            <strong>{{ __('home.verified_access_title') }}</strong>
            <small>{{ __('home.verified_access_desc') }}</small>
          </div>
        </div>

        <div class="landing-journey-card">
          <span class="landing-journey-icon"><i class="bi bi-truck" aria-hidden="true"></i></span>
          <div>
            <strong>{{ __('home.journey_title') }}</strong>
            <small>{{ __('home.journey_desc') }}</small>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="landing-metrics" aria-label="{{ __('home.platform_highlights') }}">
    <div class="landing-shell landing-metrics-grid">
      <div class="landing-metric">
        <span class="landing-metric-icon"><i class="bi bi-person-vcard" aria-hidden="true"></i></span>
        <div><strong>{{ __('home.metric_roles_value') }}</strong><small>{{ __('home.metric_roles_label') }}</small></div>
      </div>
      <div class="landing-metric">
        <span class="landing-metric-icon"><i class="bi bi-clock-history" aria-hidden="true"></i></span>
        <div><strong>{{ __('home.metric_access_value') }}</strong><small>{{ __('home.metric_access_label') }}</small></div>
      </div>
      <div class="landing-metric">
        <span class="landing-metric-icon"><i class="bi bi-buildings" aria-hidden="true"></i></span>
        <div><strong>{{ __('home.metric_workflows_value') }}</strong><small>{{ __('home.metric_workflows_label') }}</small></div>
      </div>
      <div class="landing-metric">
        <span class="landing-metric-icon"><i class="bi bi-shield-lock" aria-hidden="true"></i></span>
        <div><strong>{{ __('home.metric_control_value') }}</strong><small>{{ __('home.metric_control_label') }}</small></div>
      </div>
    </div>
  </section>

  <section class="landing-ecosystem" aria-label="{{ __('home.ecosystem_label') }}">
    <div class="landing-shell landing-ecosystem-grid">
      <div class="landing-network-block">
        <span class="landing-strip-label">{{ __('home.ecosystem_label') }}</span>
        <div class="landing-wordmarks" aria-label="{{ __('home.ecosystem_members_label') }}">
          <span><i class="bi bi-person-heart" aria-hidden="true"></i>{{ __('home.network_patients') }}</span>
          <span><i class="bi bi-person-badge" aria-hidden="true"></i>{{ __('home.network_doctors') }}</span>
          <span><i class="bi bi-hospital" aria-hidden="true"></i>{{ __('home.network_hospitals') }}</span>
          <span><i class="bi bi-capsule" aria-hidden="true"></i>{{ __('home.network_pharmacies') }}</span>
          <span><i class="bi bi-truck" aria-hidden="true"></i>{{ __('home.network_delivery') }}</span>
        </div>
      </div>

      <div class="landing-payment-block">
        <span class="landing-strip-label">{{ __('home.payment_label') }}</span>
        <div class="landing-payment-methods">
          <span class="landing-payment-method landing-payment-active"><i class="bi bi-cash-stack" aria-hidden="true"></i>{{ __('home.payment_cash') }}</span>
          <span class="landing-payment-method">{{ __('home.payment_bkash') }}</span>
          <span class="landing-payment-method"><i class="bi bi-credit-card" aria-hidden="true"></i>{{ __('home.payment_card') }}</span>
          <span class="landing-payment-method"><i class="bi bi-bank" aria-hidden="true"></i>{{ __('home.payment_bank') }}</span>
        </div>
      </div>
    </div>
  </section>

  <section class="landing-workflows" aria-labelledby="landing-workflows-heading">
    <div class="landing-shell">
      <div class="landing-workflow-intro">
        <div>
          <span class="landing-section-kicker">{{ __('home.workflows_kicker') }}</span>
          <h2 id="landing-workflows-heading">{{ __('home.workflows_title') }}</h2>
        </div>
        <p>{{ __('home.workflows_intro') }}</p>
      </div>

      <div class="landing-workflow-grid">
        <div class="landing-workflow-item">
          <span class="landing-workflow-number">01</span>
          <i class="bi bi-search" aria-hidden="true"></i>
          <strong>{{ __('home.workflow_consult_title') }}</strong>
          <p>{{ __('home.workflow_consult_desc') }}</p>
        </div>
        <div class="landing-workflow-item">
          <span class="landing-workflow-number">02</span>
          <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
          <strong>{{ __('home.workflow_compare_title') }}</strong>
          <p>{{ __('home.workflow_compare_desc') }}</p>
        </div>
        <div class="landing-workflow-item">
          <span class="landing-workflow-number">03</span>
          <i class="bi bi-cart3" aria-hidden="true"></i>
          <strong>{{ __('home.workflow_order_title') }}</strong>
          <p>{{ __('home.workflow_order_desc') }}</p>
        </div>
        <div class="landing-workflow-item">
          <span class="landing-workflow-number">04</span>
          <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
          <strong>{{ __('home.workflow_manage_title') }}</strong>
          <p>{{ __('home.workflow_manage_desc') }}</p>
        </div>
      </div>

      <div class="landing-footer-line">
        <span>{{ __('home.footer_message') }}</span>
        <span>Telemedicine <span aria-hidden="true">•</span> {{ __('home.brand_tagline') }}</span>
      </div>
    </div>
  </section>
</div>
@endsection
