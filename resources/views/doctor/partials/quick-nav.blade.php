{{--
  Blade twin of resources/js/doctor/components/QuickNav.jsx — same 6 links,
  same active-state highlighting, same .doctor-quick-nav CSS. Used on the
  doctor pages that aren't converted to React yet (records, analytics,
  leave) plus the shared inbox pages when viewed by a doctor, so navigating
  away from a React page and back still feels like one consistent section
  instead of a dead end.
--}}
<nav class="doctor-quick-nav" aria-label="Quick access">
  <a href="{{ route('doctor.appointments') }}" class="{{ request()->routeIs('doctor.appointments') ? 'is-active' : '' }}">
    <i class="bi bi-calendar2-check" aria-hidden="true"></i><span>Appointments</span>
  </a>
  <a href="{{ route('doctor.availability') }}" class="{{ request()->routeIs('doctor.availability*') ? 'is-active' : '' }}">
    <i class="bi bi-calendar-week" aria-hidden="true"></i><span>Availability</span>
  </a>
  <a href="{{ route('doctor.records') }}" class="{{ request()->routeIs('doctor.records*') ? 'is-active' : '' }}">
    <i class="bi bi-folder2-open" aria-hidden="true"></i><span>Records</span>
  </a>
  <a href="{{ route('inbox.index') }}" class="{{ request()->routeIs('inbox.*') ? 'is-active' : '' }}">
    <i class="bi bi-chat-dots" aria-hidden="true"></i><span>Inbox</span>
  </a>
  <a href="{{ route('doctor.reviews') }}" class="{{ request()->routeIs('doctor.reviews') ? 'is-active' : '' }}">
    <i class="bi bi-star" aria-hidden="true"></i><span>Reviews</span>
  </a>
  <a href="{{ route('doctor.analytics') }}" class="{{ request()->routeIs('doctor.analytics') ? 'is-active' : '' }}">
    <i class="bi bi-graph-up-arrow" aria-hidden="true"></i><span>Analytics</span>
  </a>
</nav>
