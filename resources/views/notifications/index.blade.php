@extends('layouts.app')
@section('title', auth()->user()->role === 'patient' ? __('patient.notifications.page_title') : 'Notifications')
@section('content')
@if (auth()->user()->role === 'patient')
  @php
    $newNotifications = $notifications->where('is_read', false)->values();
    $earlierNotifications = $notifications->where('is_read', true)->values();
    $newCount = $newNotifications->count();
    $todayCount = $notifications->filter(fn ($notification) => $notification->created_at?->isToday())->count();
  @endphp

  <div class="patient-notifications-page">
    <nav class="patient-appointments-workspace-nav" aria-label="{{ __('patient.notifications.patient_navigation') }}">
      <a href="{{ route('patient.doctors') }}"><i class="bi bi-calendar2-check" aria-hidden="true"></i><span>{{ __('dashboard.patient.appointments_title') }}</span></a>
      <a href="{{ route('patient.prescriptions') }}"><i class="bi bi-file-earmark-medical" aria-hidden="true"></i><span>{{ __('dashboard.patient.stat_prescriptions') }}</span></a>
      <a href="{{ route('patient.lab-tests') }}"><i class="bi bi-eyedropper" aria-hidden="true"></i><span>{{ __('dashboard.patient.lab_tests') }}</span></a>
      <a href="{{ route('patient.facilities') }}"><i class="bi bi-hospital" aria-hidden="true"></i><span>{{ __('dashboard.patient.hospital_services') }}</span></a>
      <a href="{{ route('patient.records') }}"><i class="bi bi-clipboard2" aria-hidden="true"></i><span>{{ __('dashboard.patient.records_short') }}</span></a>
    </nav>

    <section class="patient-notifications-hero" aria-labelledby="patient-notifications-title">
      <div class="patient-notifications-breadcrumb">
        <a href="{{ route('patient.dashboard') }}">{{ __('patient.notifications.home') }}</a>
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
        <span>{{ __('patient.notifications.title') }}</span>
      </div>

      <div class="patient-notifications-hero-grid">
        <div>
          <h1 id="patient-notifications-title">{{ __('patient.notifications.hero_title') }}</h1>
          <p>{{ __('patient.notifications.hero_desc') }}</p>
        </div>

        <div class="patient-notifications-hero-note">
          <span><i class="bi bi-bell" aria-hidden="true"></i></span>
          <div>
            <strong>{{ __('patient.notifications.read_title') }}</strong>
            <small>{{ __('patient.notifications.read_desc') }}</small>
          </div>
        </div>
      </div>
    </section>

    <div class="patient-notifications-layout">
      <main class="patient-notifications-main">
        <section class="patient-notifications-summary" aria-label="{{ __('patient.notifications.summary_label') }}">
          <div>
            <span><i class="bi bi-bell-fill" aria-hidden="true"></i></span>
            <div>
              <small>{{ __('patient.notifications.new') }}</small>
              <strong>{{ $newCount }}</strong>
            </div>
          </div>

          <div>
            <span><i class="bi bi-calendar-day" aria-hidden="true"></i></span>
            <div>
              <small>{{ __('patient.notifications.today') }}</small>
              <strong>{{ $todayCount }}</strong>
            </div>
          </div>

          <div>
            <span><i class="bi bi-list-check" aria-hidden="true"></i></span>
            <div>
              <small>{{ __('patient.notifications.total') }}</small>
              <strong>{{ $notifications->count() }}</strong>
            </div>
          </div>
        </section>

        @if ($notifications->isEmpty())
          <section class="patient-notifications-empty">
            <span><i class="bi bi-bell-slash" aria-hidden="true"></i></span>
            <h2>{{ __('patient.notifications.empty_title') }}</h2>
            <p>{{ __('patient.notifications.empty_desc') }}</p>
          </section>
        @else
          @if ($newNotifications->isNotEmpty())
            <section class="patient-notifications-section" aria-labelledby="patient-notifications-new-title">
              <header class="patient-notifications-section-heading">
                <div>
                  <span class="patient-notifications-section-icon"><i class="bi bi-stars" aria-hidden="true"></i></span>
                  <div>
                    <h2 id="patient-notifications-new-title">{{ __('patient.notifications.new_notifications') }}</h2>
                    <p>{{ __('patient.notifications.new_notifications_desc') }}</p>
                  </div>
                </div>
                <strong>{{ $newCount }}</strong>
              </header>

              <div class="patient-notifications-list">
                @foreach ($newNotifications as $notification)
                  @include('notifications.partials.patient-item', ['notification' => $notification, 'isNew' => true])
                @endforeach
              </div>
            </section>
          @endif

          <section class="patient-notifications-section" aria-labelledby="patient-notifications-earlier-title">
            <header class="patient-notifications-section-heading">
              <div>
                <span class="patient-notifications-section-icon"><i class="bi bi-clock-history" aria-hidden="true"></i></span>
                <div>
                  <h2 id="patient-notifications-earlier-title">{{ __('patient.notifications.earlier_notifications') }}</h2>
                  <p>{{ __('patient.notifications.earlier_notifications_desc') }}</p>
                </div>
              </div>
              <strong>{{ $earlierNotifications->count() }}</strong>
            </header>

            @if ($earlierNotifications->isEmpty())
              <div class="patient-notifications-inline-empty">
                <i class="bi bi-check2-circle" aria-hidden="true"></i>
                <span>{{ __('patient.notifications.no_earlier') }}</span>
              </div>
            @else
              <div class="patient-notifications-list">
                @foreach ($earlierNotifications as $notification)
                  @include('notifications.partials.patient-item', ['notification' => $notification, 'isNew' => false])
                @endforeach
              </div>
            @endif
          </section>
        @endif
      </main>

      <aside class="patient-notifications-sidebar">
        <section class="patient-notifications-side-card patient-notifications-side-card-primary">
          <span class="patient-notifications-side-icon"><i class="bi bi-info-circle" aria-hidden="true"></i></span>
          <div>
            <h2>{{ __('patient.notifications.about_title') }}</h2>
            <p>{{ __('patient.notifications.about_desc') }}</p>
          </div>
        </section>

        <section class="patient-notifications-side-card">
          <span class="patient-notifications-side-icon"><i class="bi bi-question-circle" aria-hidden="true"></i></span>
          <div>
            <h2>{{ __('patient.notifications.help_title') }}</h2>
            <p>{{ __('patient.notifications.help_desc') }}</p>
          </div>
          <a href="{{ route('help.index') }}">
            {{ __('patient.notifications.help_action') }}
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
          </a>
        </section>

        <section class="patient-notifications-side-card">
          <span class="patient-notifications-side-icon"><i class="bi bi-exclamation-square" aria-hidden="true"></i></span>
          <div>
            <h2>{{ __('patient.notifications.report_title') }}</h2>
            <p>{{ __('patient.notifications.report_desc') }}</p>
          </div>
          <a href="{{ route('report.index') }}">
            {{ __('patient.notifications.report_action') }}
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
          </a>
        </section>
      </aside>
    </div>
  </div>

  <footer class="patient-dashboard-footer patient-notifications-footer">
    <div class="patient-dashboard-footer-brand">
      <span class="patient-dashboard-footer-dot"></span>
      <div><strong>{{ __('dashboard.patient.platform_name') }}</strong><small>{{ __('home.brand_tagline') }}</small></div>
    </div>
    <p>{{ __('dashboard.patient.footer_tagline') }}</p>
  </footer>
@else
<div class="card">
  <h1>Notifications</h1>
  @if ($notifications->isEmpty())
    <p class="muted">You don't have any notifications yet.</p>
  @else
    <table>
      <thead><tr><th>Message</th><th>When</th></tr></thead>
      <tbody>
        @foreach ($notifications as $n)
          <tr>
            <td @unless($n->is_read) style="font-weight:600;" @endunless>{{ $n->message }}</td>
            <td class="muted">{{ $n->created_at->format('D, M j Y g:i A') }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>

@endif
@endsection
