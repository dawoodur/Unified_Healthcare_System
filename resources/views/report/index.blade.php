@extends('layouts.app')
@section('title', __('patient.report.page_title'))
@section('content')
@php
  $openCount = $reports->where('status', 'open')->count();
  $reviewCount = $reports->where('status', 'in_review')->count();
  $resolvedCount = $reports->where('status', 'resolved')->count();
  $dismissedCount = $reports->where('status', 'dismissed')->count();
@endphp

<div class="patient-report-page">
  <nav class="patient-appointments-workspace-nav" aria-label="{{ __('patient.report.patient_navigation') }}">
    <a href="{{ route('patient.doctors') }}"><i class="bi bi-calendar2-check" aria-hidden="true"></i><span>{{ __('dashboard.patient.appointments_title') }}</span></a>
    <a href="{{ route('patient.prescriptions') }}"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span>{{ __('dashboard.patient.stat_prescriptions') }}</span></a>
    <a href="{{ route('patient.lab-tests') }}"><i class="bi bi-eyedropper" aria-hidden="true"></i><span>{{ __('dashboard.patient.lab_tests') }}</span></a>
    <a href="{{ route('patient.facilities') }}"><i class="bi bi-hospital" aria-hidden="true"></i><span>{{ __('dashboard.patient.hospital_services') }}</span></a>
    <a href="{{ route('patient.records') }}"><i class="bi bi-clipboard2" aria-hidden="true"></i><span>{{ __('dashboard.patient.records_short') }}</span></a>
  </nav>

  <section class="patient-report-hero" aria-labelledby="patient-report-title">
    <div class="patient-report-breadcrumb">
      <a href="{{ route('patient.dashboard') }}">{{ __('patient.report.home') }}</a>
      <i class="bi bi-chevron-right" aria-hidden="true"></i>
      <span>{{ __('patient.report.title') }}</span>
    </div>

    <div class="patient-report-hero-grid">
      <div>
        <h1 id="patient-report-title">{{ __('patient.report.hero_title') }}</h1>
        <p>{{ __('patient.report.hero_desc') }}</p>
      </div>

      <div class="patient-report-hero-note">
        <span><i class="bi bi-shield-check" aria-hidden="true"></i></span>
        <div>
          <strong>{{ __('patient.report.admin_review_title') }}</strong>
          <small>{{ __('patient.report.admin_review_desc') }}</small>
        </div>
      </div>
    </div>
  </section>

  <div class="patient-report-layout">
    <main class="patient-report-main">
      <section class="patient-report-form-card">
        <header class="patient-report-section-heading">
          <span><i class="bi bi-exclamation-square" aria-hidden="true"></i></span>
          <div>
            <h2>{{ __('patient.report.submit_title') }}</h2>
            <p>{{ __('patient.report.submit_desc') }}</p>
          </div>
        </header>

        <form method="POST" action="{{ route('report.store') }}" class="patient-report-form" data-confirm="{{ __('patient.report.submit_confirm') }}">
          @csrf

          <div class="patient-report-field">
            <label for="subject">
              <span><i class="bi bi-type" aria-hidden="true"></i>{{ __('patient.report.subject') }}</span>
              <em>{{ __('patient.report.subject_limit') }}</em>
            </label>
            <input type="text" id="subject" name="subject" maxlength="190" value="{{ old('subject') }}" placeholder="{{ __('patient.report.subject_placeholder') }}" required>
          </div>

          <div class="patient-report-field">
            <label for="description">
              <span><i class="bi bi-card-text" aria-hidden="true"></i>{{ __('patient.report.description') }}</span>
              <em>{{ __('patient.report.description_limit') }}</em>
            </label>
            <textarea id="description" name="description" rows="7" maxlength="2000" placeholder="{{ __('patient.report.description_placeholder') }}" required>{{ old('description') }}</textarea>
            <small>{{ __('patient.report.description_hint') }}</small>
          </div>

          <div class="patient-report-form-actions">
            <a href="{{ route('help.index') }}" class="patient-report-secondary-action">
              <i class="bi bi-question-circle" aria-hidden="true"></i>
              {{ __('patient.report.check_help') }}
            </a>

            <button type="submit" class="patient-report-submit-action">
              <i class="bi bi-send" aria-hidden="true"></i>
              {{ __('patient.report.submit_action') }}
              <i class="bi bi-arrow-right" aria-hidden="true"></i>
            </button>
          </div>
        </form>
      </section>

      <section class="patient-report-history-card" aria-labelledby="patient-report-history-title">
        <header class="patient-report-history-heading">
          <div>
            <h2 id="patient-report-history-title">{{ __('patient.report.history_title') }}</h2>
            <p>{{ __('patient.report.history_desc') }}</p>
          </div>
          <span>{{ trans_choice('patient.report.report_count', $reports->count(), ['count' => $reports->count()]) }}</span>
        </header>

        <div class="patient-report-status-summary" aria-label="{{ __('patient.report.status_summary') }}">
          <div><span class="patient-report-status-dot patient-report-status-dot-open"></span><small>{{ __('patient.report.status.open') }}</small><strong>{{ $openCount }}</strong></div>
          <div><span class="patient-report-status-dot patient-report-status-dot-review"></span><small>{{ __('patient.report.status.in_review') }}</small><strong>{{ $reviewCount }}</strong></div>
          <div><span class="patient-report-status-dot patient-report-status-dot-resolved"></span><small>{{ __('patient.report.status.resolved') }}</small><strong>{{ $resolvedCount }}</strong></div>
          <div><span class="patient-report-status-dot patient-report-status-dot-dismissed"></span><small>{{ __('patient.report.status.dismissed') }}</small><strong>{{ $dismissedCount }}</strong></div>
        </div>

        @if ($reports->isEmpty())
          <div class="patient-report-empty">
            <span><i class="bi bi-clipboard2-check" aria-hidden="true"></i></span>
            <div>
              <strong>{{ __('patient.report.empty_title') }}</strong>
              <p>{{ __('patient.report.empty_desc') }}</p>
            </div>
          </div>
        @else
          <div class="patient-report-list">
            @foreach ($reports as $report)
              <article class="patient-report-item">
                <header>
                  <div class="patient-report-item-title">
                    <span class="patient-report-item-icon"><i class="bi bi-exclamation-square" aria-hidden="true"></i></span>
                    <div>
                      <strong>{{ $report->subject }}</strong>
                      <small>{{ __('patient.report.submitted_on', ['date' => $report->created_at->format('D, M j Y · g:i A')]) }}</small>
                    </div>
                  </div>

                  <span class="patient-report-status patient-report-status-{{ $report->status }}">
                    @if ($report->status === 'resolved')
                      <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                    @elseif ($report->status === 'dismissed')
                      <i class="bi bi-x-circle-fill" aria-hidden="true"></i>
                    @elseif ($report->status === 'in_review')
                      <i class="bi bi-search" aria-hidden="true"></i>
                    @else
                      <i class="bi bi-clock-fill" aria-hidden="true"></i>
                    @endif
                    {{ __('patient.report.status.' . $report->status) }}
                  </span>
                </header>

                <div class="patient-report-item-body">
                  <div>
                    <span>{{ __('patient.report.your_report') }}</span>
                    <p>{{ $report->description }}</p>
                  </div>

                  @if ($report->admin_response)
                    <div class="patient-report-admin-response">
                      <span><i class="bi bi-reply" aria-hidden="true"></i>{{ __('patient.report.admin_response') }}</span>
                      <p>{{ $report->admin_response }}</p>
                    </div>
                  @endif
                </div>

                @if ($report->resolved_at)
                  <footer>
                    <i class="bi bi-check2-circle" aria-hidden="true"></i>
                    {{ __('patient.report.resolved_on', ['date' => $report->resolved_at->format('D, M j Y · g:i A')]) }}
                  </footer>
                @endif
              </article>
            @endforeach
          </div>
        @endif
      </section>
    </main>

    <aside class="patient-report-sidebar">
      <section class="patient-report-side-card patient-report-side-card-primary">
        <span class="patient-report-side-icon"><i class="bi bi-lightbulb" aria-hidden="true"></i></span>
        <div>
          <h2>{{ __('patient.report.before_reporting_title') }}</h2>
          <p>{{ __('patient.report.before_reporting_desc') }}</p>
        </div>
        <a href="{{ route('help.index') }}">
          {{ __('patient.report.visit_help') }}
          <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
      </section>

      <section class="patient-report-side-card">
        <span class="patient-report-side-icon"><i class="bi bi-chat-square-text" aria-hidden="true"></i></span>
        <div>
          <h2>{{ __('patient.report.inbox_title') }}</h2>
          <p>{{ __('patient.report.inbox_desc') }}</p>
        </div>
        <a href="{{ route('inbox.index') }}">
          {{ __('patient.report.open_inbox') }}
          <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
      </section>

      <section class="patient-report-info-card">
        <div class="patient-report-info-heading">
          <span><i class="bi bi-info-circle-fill" aria-hidden="true"></i></span>
          <h2>{{ __('patient.report.what_to_include_title') }}</h2>
        </div>
        <ul>
          <li>{{ __('patient.report.what_to_include_1') }}</li>
          <li>{{ __('patient.report.what_to_include_2') }}</li>
          <li>{{ __('patient.report.what_to_include_3') }}</li>
          <li>{{ __('patient.report.what_to_include_4') }}</li>
        </ul>
      </section>
    </aside>
  </div>
</div>

<footer class="patient-dashboard-footer patient-report-footer">
  <div class="patient-dashboard-footer-brand">
    <span class="patient-dashboard-footer-dot"></span>
    <div><strong>{{ __('dashboard.patient.platform_name') }}</strong><small>{{ __('home.brand_tagline') }}</small></div>
  </div>
  <p>{{ __('dashboard.patient.footer_tagline') }}</p>
</footer>
@endsection
