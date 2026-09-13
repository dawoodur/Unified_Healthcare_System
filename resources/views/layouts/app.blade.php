<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Telemedicine Platform')</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@300;400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  {{-- filemtime() as a "?v=" query string busts the browser cache automatically
       whenever this file's contents change, so style edits show up on a normal
       refresh instead of needing a hard-refresh (Ctrl+F5) to clear a stale copy.
       Loaded AFTER Bootstrap so our overrides win the cascade. --}}
  <link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}">
  {{-- Deliberately not deferred/async — this needs to run and set
       data-bs-theme BEFORE the page paints, or a saved dark-mode choice
       would flash light first. --}}
  <script src="{{ asset('js/apply-theme.js') }}?v={{ filemtime(public_path('js/apply-theme.js')) }}"></script>
</head>
<body>
@auth
  @if (request()->routeIs('patient.dashboard') || request()->routeIs('patient.rewards') || request()->routeIs('patient.reviews') || request()->routeIs('patient.blood-donations') || request()->routeIs('patient.appointments') || request()->routeIs('patient.doctors') || request()->routeIs('patient.doctors.show') || request()->routeIs('patient.facilities') || request()->routeIs('patient.hospitals.show') || request()->routeIs('patient.facilities.book') || request()->routeIs('patient.facility-bookings') || request()->routeIs('patient.operations') || (request()->routeIs('help.*') && auth()->user()->role === 'patient') || (request()->routeIs('report.index') && auth()->user()->role === 'patient') || (request()->routeIs('profile.edit') && auth()->user()->role === 'patient') || (request()->routeIs('notifications.index') && auth()->user()->role === 'patient') || request()->routeIs('patient.lab-tests') || request()->routeIs('patient.prescriptions') || request()->routeIs('patient.medicine') || request()->routeIs('patient.cart') || request()->routeIs('patient.checkout') || request()->routeIs('patient.orders') || (request()->routeIs('prescriptions.show') && auth()->user()->role === 'patient') || request()->routeIs('patient.records') || request()->routeIs('patient.records.create') || request()->routeIs('patient.allergies.create') || request()->routeIs('patient.vitals') || request()->routeIs('patient.symptom-checker*'))
    @php
      $patientDashboardAccount = auth()->user();
      $patientDashboardUnreadCount = $patientDashboardAccount->notifications()->where('is_read', false)->count();
      $patientDashboardPhoto = $patientDashboardAccount->photoUrl();
    @endphp
    <div class="patient-dashboard-shell">
      <header class="patient-dashboard-header">
        <div class="patient-dashboard-header-inner">
          <a class="patient-dashboard-brand" href="{{ route('patient.dashboard') }}" aria-label="Telemedicine dashboard">
            <span class="patient-dashboard-brand-mark"><i class="bi bi-plus-lg" aria-hidden="true"></i></span>
            <span class="patient-dashboard-brand-copy">
              <strong>Telemedicine</strong>
              <small>{{ __('home.brand_tagline') }}</small>
            </span>
          </a>

          <nav class="patient-dashboard-header-nav" aria-label="Patient dashboard navigation">
            <a class="patient-dashboard-header-link {{ request()->routeIs('patient.dashboard', 'patient.rewards', 'patient.reviews', 'patient.blood-donations') ? 'active' : '' }}" href="{{ route('patient.dashboard') }}">{{ __('nav.dashboard') }}</a>
            <a class="patient-dashboard-header-link patient-dashboard-notification-link" href="{{ route('notifications.index') }}">
              <i class="bi bi-bell" aria-hidden="true"></i>
              <span>{{ __('nav.notifications') }}</span>
              @if ($patientDashboardUnreadCount > 0)
                <span class="patient-dashboard-notification-badge">{{ $patientDashboardUnreadCount }}</span>
              @endif
            </a>
            <a class="patient-dashboard-header-link" href="{{ route('report.index') }}">
              <i class="bi bi-exclamation-square" aria-hidden="true"></i>
              <span>{{ __('nav.report_issue') }}</span>
            </a>
            <button type="button" id="theme-toggle" class="header-icon-btn" data-icon-only title="Toggle dark mode">🌙</button>

            <div class="dropdown patient-dashboard-profile-dropdown">
              <button class="patient-dashboard-profile-trigger" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('dashboard.patient.profile') }}">
                <span class="patient-dashboard-profile-copy">
                  <strong>{{ $patientDashboardAccount->displayName() }}</strong>
                  <small>{{ __('dashboard.patient.role_patient') }}</small>
                </span>
                <span class="patient-dashboard-profile-avatar">
                  @if ($patientDashboardPhoto)
                    <img src="{{ $patientDashboardPhoto }}" alt="">
                  @else
                    <i class="bi bi-person-fill" aria-hidden="true"></i>
                  @endif
                </span>
              </button>
              <ul class="dropdown-menu dropdown-menu-end patient-dashboard-profile-menu">
                <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2" aria-hidden="true"></i>{{ __('dashboard.patient.profile') }}</a></li>
                <li>
                  <div class="dropdown-item d-flex align-items-center">
                    <i class="bi bi-globe2 me-2" aria-hidden="true"></i>
                    <span class="d-inline-flex align-items-center gap-1">
                      <a href="{{ route('locale.switch', 'en') }}"
                         class="text-decoration-none {{ app()->getLocale() === 'en' ? 'fw-bold' : 'text-muted' }}"
                         aria-label="English">EN</a>
                      <span class="text-muted">·</span>
                      <a href="{{ route('locale.switch', 'bn') }}"
                         class="text-decoration-none {{ app()->getLocale() === 'bn' ? 'fw-bold' : 'text-muted' }}"
                         aria-label="বাংলা">BN</a>
                    </span>
                  </div>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>{{ __('nav.logout') }}</button>
                  </form>
                </li>
              </ul>
            </div>
          </nav>
        </div>
      </header>

      <main class="patient-dashboard-main">
        @if (session('success'))
          @if (request()->routeIs('patient.appointments') || request()->routeIs('patient.facility-bookings'))
            <div class="alert patient-flash patient-flash-success alert-dismissible fade show" role="status">
              <span class="patient-flash-icon" aria-hidden="true">
                <i class="bi bi-check-lg"></i>
              </span>
              <div class="patient-flash-copy">
                <strong>Booking confirmed</strong>
                <span>{{ session('success') }}</span>
              </div>
              <button type="button" class="btn-close patient-flash-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          @elseif (request()->routeIs('patient.operations'))
            <div class="alert patient-flash patient-flash-success alert-dismissible fade show" role="status">
              <span class="patient-flash-icon" aria-hidden="true">
                <i class="bi bi-check-lg"></i>
              </span>
              <div class="patient-flash-copy">
                <strong>{{ __('patient.operations.flash_title') }}</strong>
                <span>{{ session('success') }}</span>
              </div>
              <button type="button" class="btn-close patient-flash-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          @elseif (request()->routeIs('patient.medicine') || request()->routeIs('patient.cart') || request()->routeIs('patient.orders') || request()->routeIs('patient.reviews') || request()->routeIs('patient.blood-donations') || request()->routeIs('report.index') || (request()->routeIs('profile.edit') && auth()->user()->role === 'patient'))
            <div class="alert patient-flash patient-flash-success alert-dismissible fade show" role="status">
              <span class="patient-flash-icon" aria-hidden="true">
                <i class="bi bi-check-lg"></i>
              </span>
              <div class="patient-flash-copy">
                <strong>{{ session('success') }}</strong>
              </div>
              <button type="button" class="btn-close patient-flash-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          @else
            <div class="alert patient-flash patient-flash-success alert-dismissible fade show" role="status">
              <span class="patient-flash-icon" aria-hidden="true">
                <i class="bi bi-check-lg"></i>
              </span>
              <div class="patient-flash-copy">
                <strong>{{ session('success') }}</strong>
              </div>
              <button type="button" class="btn-close patient-flash-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          @endif
        @endif
        @if (session('error'))
          <div class="alert patient-flash patient-flash-error alert-dismissible fade show" role="alert">
            <span class="patient-flash-icon" aria-hidden="true">
              <i class="bi bi-exclamation-lg"></i>
            </span>
            <div class="patient-flash-copy">
              <strong>{{ session('error') }}</strong>
            </div>
            <button type="button" class="btn-close patient-flash-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif
        @if (session('info'))
          <div class="alert patient-flash patient-flash-info alert-dismissible fade show" role="status">
            <span class="patient-flash-icon" aria-hidden="true">
              <i class="bi bi-info-lg"></i>
            </span>
            <div class="patient-flash-copy">
              <strong>{{ session('info') }}</strong>
            </div>
            <button type="button" class="btn-close patient-flash-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif
        @if ($errors->any())
          <div class="alert patient-flash patient-flash-error alert-dismissible fade show" role="alert">
            <span class="patient-flash-icon" aria-hidden="true">
              <i class="bi bi-exclamation-lg"></i>
            </span>
            <div class="patient-flash-copy">
              <ul class="patient-flash-error-list">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
            <button type="button" class="btn-close patient-flash-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        @yield('content')
      </main>
    </div>
  @else
  {{-- Authenticated shell: fixed left sidebar + top header bar. Every
       dashboard-side page except the route-scoped patient dashboard/doctor-discovery pages uses
       this structure — see partials/sidebar.blade.php for role links. --}}
  <div class="app-shell">
    <aside class="app-sidebar" id="appSidebar">
      @include('partials.sidebar')
    </aside>
    <div class="app-backdrop" id="appBackdrop"></div>

    <div class="app-main">
      <header class="app-header">
        <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu">
          <i class="bi bi-list"></i>
        </button>
        <div class="app-header-spacer"></div>
        <div class="app-header-actions">
          <a href="{{ route('locale.switch', 'en') }}" class="header-locale {{ app()->getLocale() === 'en' ? 'active' : '' }}">EN</a>
          <a href="{{ route('locale.switch', 'bn') }}" class="header-locale {{ app()->getLocale() === 'bn' ? 'active' : '' }}">বাংলা</a>
          <button type="button" id="theme-toggle" class="header-icon-btn" data-icon-only title="Toggle dark mode">🌙</button>
          @php $unreadCount = auth()->user()->notifications()->where('is_read', false)->count(); @endphp
          <a href="{{ route('notifications.index') }}" class="header-icon-btn position-relative" title="{{ __('nav.notifications') }}">
            <i class="bi bi-bell"></i>
            @if ($unreadCount > 0)
              <span class="header-badge">{{ $unreadCount }}</span>
            @endif
          </a>
          <a href="{{ route('profile.edit') }}" class="header-user text-decoration-none">
            @include('partials.avatar', ['account' => auth()->user()])
            <span class="header-user-info d-none d-md-flex">
              <strong>{{ auth()->user()->displayName() }}</strong>
              <span class="muted text-capitalize">{{ auth()->user()->role }}</span>
            </span>
          </a>
        </div>
      </header>

      <main class="app-content">
        @if (session('success'))
          <div class="alert patient-flash patient-flash-success alert-dismissible fade show" role="status">
            <span class="patient-flash-icon" aria-hidden="true">
              <i class="bi bi-check-lg"></i>
            </span>
            <div class="patient-flash-copy">
              <strong>{{ session('success') }}</strong>
            </div>
            <button type="button" class="btn-close patient-flash-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif
        @if (session('error'))
          <div class="alert patient-flash patient-flash-error alert-dismissible fade show" role="alert">
            <span class="patient-flash-icon" aria-hidden="true">
              <i class="bi bi-exclamation-lg"></i>
            </span>
            <div class="patient-flash-copy">
              <strong>{{ session('error') }}</strong>
            </div>
            <button type="button" class="btn-close patient-flash-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif
        @if (session('info'))
          <div class="alert patient-flash patient-flash-info alert-dismissible fade show" role="status">
            <span class="patient-flash-icon" aria-hidden="true">
              <i class="bi bi-info-lg"></i>
            </span>
            <div class="patient-flash-copy">
              <strong>{{ session('info') }}</strong>
            </div>
            <button type="button" class="btn-close patient-flash-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif
        @if ($errors->any())
          <div class="alert patient-flash patient-flash-error alert-dismissible fade show" role="alert">
            <span class="patient-flash-icon" aria-hidden="true">
              <i class="bi bi-exclamation-lg"></i>
            </span>
            <div class="patient-flash-copy">
              <ul class="patient-flash-error-list">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
            <button type="button" class="btn-close patient-flash-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        @yield('content')
      </main>
    </div>
  </div>
  @endif
@else
  @if (request()->routeIs('home') || request()->routeIs('register.choose') || request()->routeIs('register.patient') || request()->routeIs('register.doctor') || request()->routeIs('register.hospital') || request()->routeIs('register.pharmacy') || request()->routeIs('register.delivery') || request()->routeIs('login') || request()->routeIs('otp.show') || request()->routeIs('password.forgot') || request()->routeIs('password.reset.show'))
    {{-- The landing page and approved onboarding/auth screens share the same
         restrained guest header. Other guest screens keep the existing
         guest navbar so route-scoped redesigns cannot leak into them. --}}
    <header class="landing-header">
      <div class="landing-shell landing-header-inner {{ request()->routeIs('register.choose') ? 'register-choice-header-shell' : ((request()->routeIs('register.patient') || request()->routeIs('register.doctor') || request()->routeIs('register.hospital') || request()->routeIs('register.pharmacy') || request()->routeIs('register.delivery')) ? 'patient-register-header-shell' : (request()->routeIs('login') ? 'login-header-shell' : (request()->routeIs('otp.show') ? 'otp-header-shell' : (request()->routeIs('password.forgot') ? 'forgot-header-shell' : (request()->routeIs('password.reset.show') ? 'reset-header-shell' : ''))))) }}">
        <a class="landing-brand" href="{{ route('home') }}" aria-label="Telemedicine home">
          <span class="landing-brand-mark"><i class="bi bi-plus-lg" aria-hidden="true"></i></span>
          <span class="landing-brand-copy">
            <strong>Telemedicine</strong>
            <small>{{ __('home.brand_tagline') }}</small>
          </span>
        </a>

        <div class="landing-header-actions">
          @if (request()->routeIs('login') || request()->routeIs('otp.show') || request()->routeIs('password.forgot') || request()->routeIs('password.reset.show'))
            <button type="button" id="theme-toggle" class="landing-theme-btn" data-icon-only title="{{ __('home.theme_toggle') }}" aria-label="{{ __('home.theme_toggle') }}">🌙</button>
            <div class="dropdown">
              <button class="landing-language-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Language">
                {{ app()->getLocale() === 'bn' ? 'বাংলা' : 'EN' }}
              </button>
              <ul class="dropdown-menu dropdown-menu-end landing-language-menu">
                <li><a class="dropdown-item {{ app()->getLocale() === 'en' ? 'active' : '' }}" href="{{ route('locale.switch', 'en') }}">English</a></li>
                <li><a class="dropdown-item {{ app()->getLocale() === 'bn' ? 'active' : '' }}" href="{{ route('locale.switch', 'bn') }}">বাংলা</a></li>
              </ul>
            </div>
          @else
            <div class="dropdown">
              <button class="landing-language-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Language">
                {{ app()->getLocale() === 'bn' ? 'বাংলা' : 'EN' }}
              </button>
              <ul class="dropdown-menu dropdown-menu-end landing-language-menu">
                <li><a class="dropdown-item {{ app()->getLocale() === 'en' ? 'active' : '' }}" href="{{ route('locale.switch', 'en') }}">English</a></li>
                <li><a class="dropdown-item {{ app()->getLocale() === 'bn' ? 'active' : '' }}" href="{{ route('locale.switch', 'bn') }}">বাংলা</a></li>
              </ul>
            </div>
            <button type="button" id="theme-toggle" class="landing-theme-btn" data-icon-only title="{{ __('home.theme_toggle') }}" aria-label="{{ __('home.theme_toggle') }}">🌙</button>
            @if (request()->routeIs('register.patient') || request()->routeIs('register.doctor') || request()->routeIs('register.hospital') || request()->routeIs('register.pharmacy') || request()->routeIs('register.delivery'))
              <a class="patient-header-login" href="{{ route('login') }}">
                <span>{{ __('register.already_have_account') }}</span>
                <strong>{{ __('nav.login') }}</strong>
                <i class="bi bi-arrow-right" aria-hidden="true"></i>
              </a>
            @else
              <a class="landing-create-account" href="{{ route('register.choose') }}">{{ __('home.create_account') }}</a>
            @endif
          @endif
        </div>
      </div>
    </header>
    <main class="{{ request()->routeIs('home') ? 'landing-main' : (request()->routeIs('register.choose') ? 'register-choice-main' : (request()->routeIs('login') ? 'login-main' : (request()->routeIs('otp.show') ? 'otp-main' : (request()->routeIs('password.forgot') ? 'forgot-main' : (request()->routeIs('password.reset.show') ? 'reset-main' : 'patient-register-main'))))) }}">
  @else
    {{-- Other guest pages keep the existing shared navigation and centered
         content shell; the landing-page redesign must not leak into them. --}}
    <nav class="navbar navbar-expand navbar-dark brand-navbar">
      <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ route('home') }}">
          <i class="bi bi-heart-pulse-fill"></i> Telemedicine
        </a>
        <div class="d-flex align-items-center gap-2 ms-auto">
          <a class="nav-link d-inline" href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right me-1"></i>{{ __('nav.login') }}</a>
          <a class="nav-link d-inline" href="{{ route('register.choose') }}"><i class="bi bi-person-plus me-1"></i>{{ __('nav.register') }}</a>
          <span class="navbar-text text-white-50 px-1">|</span>
          <a class="nav-link d-inline {{ app()->getLocale() === 'en' ? 'fw-bold text-white' : '' }}" href="{{ route('locale.switch', 'en') }}">EN</a>
          <a class="nav-link d-inline {{ app()->getLocale() === 'bn' ? 'fw-bold text-white' : '' }}" href="{{ route('locale.switch', 'bn') }}">বাংলা</a>
          <button type="button" id="theme-toggle" class="btn btn-sm btn-outline-light">🌙 Dark</button>
        </div>
      </div>
    </nav>
    <main class="container my-4 guest-content">
  @endif
    @if (session('success'))
      <div class="alert patient-flash patient-flash-success alert-dismissible fade show" role="status">
        <span class="patient-flash-icon" aria-hidden="true">
          <i class="bi bi-check-lg"></i>
        </span>
        <div class="patient-flash-copy">
          <strong>{{ session('success') }}</strong>
        </div>
        <button type="button" class="btn-close patient-flash-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif
    @if (session('error'))
      <div class="alert patient-flash patient-flash-error alert-dismissible fade show" role="alert">
        <span class="patient-flash-icon" aria-hidden="true">
          <i class="bi bi-exclamation-lg"></i>
        </span>
        <div class="patient-flash-copy">
          <strong>{{ session('error') }}</strong>
        </div>
        <button type="button" class="btn-close patient-flash-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif
    @if (session('info'))
      <div class="alert patient-flash patient-flash-info alert-dismissible fade show" role="status">
        <span class="patient-flash-icon" aria-hidden="true">
          <i class="bi bi-info-lg"></i>
        </span>
        <div class="patient-flash-copy">
          <strong>{{ session('info') }}</strong>
        </div>
        <button type="button" class="btn-close patient-flash-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif
    @if ($errors->any() && !request()->routeIs('register.patient') && !request()->routeIs('register.doctor') && !request()->routeIs('register.hospital') && !request()->routeIs('register.pharmacy') && !request()->routeIs('register.delivery') && !request()->routeIs('login') && !request()->routeIs('otp.show') && !request()->routeIs('password.forgot') && !request()->routeIs('password.reset.show'))
      <div class="alert patient-flash patient-flash-error alert-dismissible fade show" role="alert">
        <span class="patient-flash-icon" aria-hidden="true">
          <i class="bi bi-exclamation-lg"></i>
        </span>
        <div class="patient-flash-copy">
          <ul class="patient-flash-error-list">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
        <button type="button" class="btn-close patient-flash-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @yield('content')
  </main>
@endauth
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/confirm-modal.js') }}?v={{ filemtime(public_path('js/confirm-modal.js')) }}"></script>
<script src="{{ asset('js/theme-toggle.js') }}?v={{ filemtime(public_path('js/theme-toggle.js')) }}"></script>
<script src="{{ asset('js/sidebar-toggle.js') }}?v={{ filemtime(public_path('js/sidebar-toggle.js')) }}"></script>
@stack('scripts')
</body>
</html>
