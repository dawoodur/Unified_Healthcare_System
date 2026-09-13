{{-- Role-specific left navigation — one block per role, since each role's
     menu is genuinely different (a patient doesn't see "Doctor Verifications").
     route()==request()->route()->getName() drives the "active" highlight. --}}
@php
  $role = auth()->user()->role;
  $current = request()->route()?->getName();
@endphp

<a class="sidebar-brand" href="{{ route('home') }}">
  <i class="bi bi-heart-pulse-fill"></i> <span>Telemedicine</span>
</a>

<nav class="sidebar-nav">
  @if ($role === 'patient')
    <a class="sidebar-link {{ $current === 'patient.dashboard' ? 'active' : '' }}" href="{{ route('patient.dashboard') }}"><i class="bi bi-speedometer2"></i><span>{{ __('nav.dashboard') }}</span></a>
    <a class="sidebar-link {{ in_array($current, ['patient.doctors','patient.doctors.show','patient.appointments','patient.symptom-checker','patient.favorites']) ? 'active' : '' }}" href="{{ route('patient.doctors') }}"><i class="bi bi-calendar2-heart"></i><span>{{ __('dashboard.patient.appointments_title') }}</span></a>
    <a class="sidebar-link {{ in_array($current, ['patient.facilities','patient.facility-bookings']) ? 'active' : '' }}" href="{{ route('patient.facilities') }}"><i class="bi bi-building-check"></i><span>{{ __('dashboard.patient.facilities_title') }}</span></a>
    <a class="sidebar-link {{ $current === 'patient.operations' ? 'active' : '' }}" href="{{ route('patient.operations') }}"><i class="bi bi-heart-pulse"></i><span>{{ __('dashboard.patient.operations_title') }}</span></a>
    <a class="sidebar-link {{ in_array($current, ['patient.medicine','patient.cart','patient.orders','patient.prescriptions']) ? 'active' : '' }}" href="{{ route('patient.prescriptions') }}"><i class="bi bi-capsule"></i><span>{{ __('dashboard.patient.medicine_title') }}</span></a>
    <a class="sidebar-link {{ in_array($current, ['patient.records','patient.vitals']) ? 'active' : '' }}" href="{{ route('patient.records') }}"><i class="bi bi-folder2-open"></i><span>{{ __('dashboard.patient.records_title') }}</span></a>
    <a class="sidebar-link {{ $current === 'patient.reviews' ? 'active' : '' }}" href="{{ route('patient.reviews') }}"><i class="bi bi-star"></i><span>{{ __('dashboard.patient.reviews_title') }}</span></a>
    <a class="sidebar-link {{ $current === 'patient.blood-donations' ? 'active' : '' }}" href="{{ route('patient.blood-donations') }}"><i class="bi bi-droplet-fill"></i><span>{{ __('dashboard.patient.blood_title') }}</span></a>
    <a class="sidebar-link" href="{{ route('inbox.index') }}"><i class="bi bi-chat-dots"></i><span>{{ __('dashboard.patient.inbox_title') }}</span></a>
  @elseif ($role === 'doctor')
    <a class="sidebar-link {{ $current === 'doctor.dashboard' ? 'active' : '' }}" href="{{ route('doctor.dashboard') }}"><i class="bi bi-speedometer2"></i><span>{{ __('nav.dashboard') }}</span></a>
    <a class="sidebar-link {{ $current === 'doctor.availability' ? 'active' : '' }}" href="{{ route('doctor.availability') }}"><i class="bi bi-calendar-week"></i><span>{{ __('dashboard.doctor.availability_title') }}</span></a>
    <a class="sidebar-link {{ $current === 'doctor.appointments' ? 'active' : '' }}" href="{{ route('doctor.appointments') }}"><i class="bi bi-calendar2-check"></i><span>{{ __('dashboard.doctor.appointments_title') }}</span></a>
    <a class="sidebar-link {{ $current === 'doctor.records' ? 'active' : '' }}" href="{{ route('doctor.records') }}"><i class="bi bi-folder2-open"></i><span>{{ __('dashboard.doctor.records_title') }}</span></a>
    <a class="sidebar-link {{ $current === 'doctor.analytics' ? 'active' : '' }}" href="{{ route('doctor.analytics') }}"><i class="bi bi-graph-up-arrow"></i><span>{{ __('dashboard.doctor.analytics_title') }}</span></a>
    <a class="sidebar-link {{ $current === 'doctor.reviews' ? 'active' : '' }}" href="{{ route('doctor.reviews') }}"><i class="bi bi-star"></i><span>{{ __('dashboard.doctor.reviews_title') }}</span></a>
    <a class="sidebar-link" href="{{ route('inbox.index') }}"><i class="bi bi-chat-dots"></i><span>{{ __('dashboard.doctor.inbox_title') }}</span></a>
  @elseif ($role === 'hospital')
    <a class="sidebar-link {{ $current === 'hospital.dashboard' ? 'active' : '' }}" href="{{ route('hospital.dashboard') }}"><i class="bi bi-speedometer2"></i><span>{{ __('nav.dashboard') }}</span></a>
    <a class="sidebar-link {{ $current === 'hospital.doctors' ? 'active' : '' }}" href="{{ route('hospital.doctors') }}"><i class="bi bi-person-badge"></i><span>{{ __('dashboard.hospital.doctor_assignments_title') }}</span></a>
    <a class="sidebar-link {{ in_array($current, ['hospital.facilities','hospital.facility-bookings']) ? 'active' : '' }}" href="{{ route('hospital.facilities') }}"><i class="bi bi-building"></i><span>{{ __('dashboard.hospital.facilities_title') }}</span></a>
    <a class="sidebar-link {{ $current === 'hospital.operations' ? 'active' : '' }}" href="{{ route('hospital.operations') }}"><i class="bi bi-heart-pulse"></i><span>{{ __('dashboard.hospital.operations_title') }}</span></a>
    <a class="sidebar-link {{ $current === 'hospital.payment-methods' ? 'active' : '' }}" href="{{ route('hospital.payment-methods') }}"><i class="bi bi-credit-card"></i><span>{{ __('dashboard.hospital.payment_methods_title') }}</span></a>
    <a class="sidebar-link {{ $current === 'hospital.appointment-stats' ? 'active' : '' }}" href="{{ route('hospital.appointment-stats') }}"><i class="bi bi-graph-up-arrow"></i><span>{{ __('dashboard.hospital.stats_title') }}</span></a>
    <a class="sidebar-link {{ $current === 'hospital.blood-requests' ? 'active' : '' }}" href="{{ route('hospital.blood-requests') }}"><i class="bi bi-droplet-fill"></i><span>{{ __('dashboard.hospital.blood_title') }}</span></a>
    <a class="sidebar-link {{ $current === 'hospital.reviews' ? 'active' : '' }}" href="{{ route('hospital.reviews') }}"><i class="bi bi-star"></i><span>{{ __('dashboard.hospital.reviews_title') }}</span></a>
    <a class="sidebar-link" href="{{ route('inbox.index') }}"><i class="bi bi-chat-dots"></i><span>{{ __('dashboard.hospital.inbox_title') }}</span></a>
  @elseif ($role === 'pharmacy')
    <a class="sidebar-link {{ $current === 'pharmacy.dashboard' ? 'active' : '' }}" href="{{ route('pharmacy.dashboard') }}"><i class="bi bi-speedometer2"></i><span>{{ __('nav.dashboard') }}</span></a>
    <a class="sidebar-link {{ $current === 'pharmacy.inventory' ? 'active' : '' }}" href="{{ route('pharmacy.inventory') }}"><i class="bi bi-boxes"></i><span>{{ __('dashboard.pharmacy.inventory_title') }}</span></a>
    <a class="sidebar-link {{ $current === 'pharmacy.orders' ? 'active' : '' }}" href="{{ route('pharmacy.orders') }}"><i class="bi bi-bag-check"></i><span>{{ __('dashboard.pharmacy.orders_title') }}</span></a>
    <a class="sidebar-link {{ $current === 'pharmacy.reviews' ? 'active' : '' }}" href="{{ route('pharmacy.reviews') }}"><i class="bi bi-star"></i><span>{{ __('dashboard.pharmacy.reviews_title') }}</span></a>
    <a class="sidebar-link" href="{{ route('inbox.index') }}"><i class="bi bi-chat-dots"></i><span>{{ __('dashboard.pharmacy.inbox_title') }}</span></a>
  @elseif ($role === 'delivery')
    <a class="sidebar-link {{ $current === 'delivery.dashboard' ? 'active' : '' }}" href="{{ route('delivery.dashboard') }}"><i class="bi bi-speedometer2"></i><span>{{ __('nav.dashboard') }}</span></a>
    <a class="sidebar-link {{ $current === 'delivery.available' ? 'active' : '' }}" href="{{ route('delivery.available') }}"><i class="bi bi-truck"></i><span>{{ __('dashboard.delivery.available_title') }}</span></a>
    <a class="sidebar-link {{ $current === 'delivery.my-deliveries' ? 'active' : '' }}" href="{{ route('delivery.my-deliveries') }}"><i class="bi bi-box-seam"></i><span>{{ __('dashboard.delivery.deliveries_title') }}</span></a>
    <a class="sidebar-link {{ $current === 'delivery.reviews' ? 'active' : '' }}" href="{{ route('delivery.reviews') }}"><i class="bi bi-star"></i><span>{{ __('dashboard.delivery.reviews_title') }}</span></a>
    <a class="sidebar-link" href="{{ route('inbox.index') }}"><i class="bi bi-chat-dots"></i><span>{{ __('dashboard.delivery.inbox_title') }}</span></a>
  @elseif ($role === 'admin')
    <a class="sidebar-link {{ $current === 'admin.dashboard' ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2"></i><span>{{ __('nav.dashboard') }}</span></a>
    <a class="sidebar-link {{ $current === 'admin.users' ? 'active' : '' }}" href="{{ route('admin.users') }}"><i class="bi bi-search"></i><span>{{ __('dashboard.admin.search_users_title') }}</span></a>
    <a class="sidebar-link {{ $current === 'admin.transactions' ? 'active' : '' }}" href="{{ route('admin.transactions') }}"><i class="bi bi-cash-stack"></i><span>{{ __('dashboard.admin.transactions_title') }}</span></a>
    <a class="sidebar-link {{ $current === 'admin.doctor-verifications' ? 'active' : '' }}" href="{{ route('admin.doctor-verifications') }}"><i class="bi bi-patch-check"></i><span>{{ __('dashboard.admin.verifications_title') }}</span></a>
    <a class="sidebar-link {{ $current === 'admin.reports' ? 'active' : '' }}" href="{{ route('admin.reports') }}"><i class="bi bi-flag"></i><span>{{ __('dashboard.admin.reports_title') }}</span></a>
    <a class="sidebar-link {{ $current === 'admin.analytics' ? 'active' : '' }}" href="{{ route('admin.analytics') }}"><i class="bi bi-graph-up-arrow"></i><span>{{ __('dashboard.admin.analytics_title') }}</span></a>
  @endif

  <div class="sidebar-divider"></div>
  @if ($role !== 'admin')
    <a class="sidebar-link {{ $current === 'report.index' ? 'active' : '' }}" href="{{ route('report.index') }}"><i class="bi bi-exclamation-octagon"></i><span>{{ __('nav.report_issue') }}</span></a>
  @endif
  <a class="sidebar-link {{ $current === 'help.index' ? 'active' : '' }}" href="{{ route('help.index') }}"><i class="bi bi-question-circle"></i><span>{{ __('nav.help') }}</span></a>
</nav>

<div class="sidebar-footer">
  <form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit" class="sidebar-link w-100 border-0 bg-transparent text-start"><i class="bi bi-box-arrow-right"></i><span>{{ __('nav.logout') }}</span></button>
  </form>
</div>
