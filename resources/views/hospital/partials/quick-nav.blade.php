{{--
  Blade twin of resources/js/hospital/components/QuickNav.jsx — same eight
  links, same active-state highlighting, same .hospital-workspace-nav CSS.
  Used on the hospital pages that are still Blade forms (add a facility,
  make an operation offer, send a blood request) so stepping out of the
  React section and back still feels like one workspace instead of a dead
  end, the same way doctor/partials/quick-nav.blade.php does.
--}}
<nav class="hospital-workspace-nav" aria-label="Hospital workspace navigation">
  <a href="{{ route('hospital.dashboard') }}" class="{{ request()->routeIs('hospital.dashboard') ? 'is-active' : '' }}">
    <i class="bi bi-house-door" aria-hidden="true"></i><span>Dashboard</span>
  </a>
  <a href="{{ route('hospital.doctors') }}" class="{{ request()->routeIs('hospital.doctors*') ? 'is-active' : '' }}">
    <i class="bi bi-person-badge" aria-hidden="true"></i><span>Doctors</span>
  </a>
  <a href="{{ route('hospital.facilities') }}" class="{{ request()->routeIs('hospital.facilities*') ? 'is-active' : '' }}">
    <i class="bi bi-building" aria-hidden="true"></i><span>Facilities</span>
  </a>
  <a href="{{ route('hospital.facility-bookings') }}" class="{{ request()->routeIs('hospital.facility-bookings') ? 'is-active' : '' }}">
    <i class="bi bi-calendar2-check" aria-hidden="true"></i><span>Bookings</span>
  </a>
  <a href="{{ route('hospital.operations') }}" class="{{ request()->routeIs('hospital.operations*') ? 'is-active' : '' }}">
    <i class="bi bi-heart-pulse" aria-hidden="true"></i><span>Operations</span>
  </a>
  <a href="{{ route('hospital.blood-requests') }}" class="{{ request()->routeIs('hospital.blood-requests*') || request()->routeIs('hospital.blood-donations') ? 'is-active' : '' }}">
    <i class="bi bi-droplet-fill" aria-hidden="true"></i><span>Blood</span>
  </a>
  <a href="{{ route('hospital.appointment-stats') }}" class="{{ request()->routeIs('hospital.appointment-stats') ? 'is-active' : '' }}">
    <i class="bi bi-graph-up-arrow" aria-hidden="true"></i><span>Stats</span>
  </a>
  <a href="{{ route('hospital.reviews') }}" class="{{ request()->routeIs('hospital.reviews') ? 'is-active' : '' }}">
    <i class="bi bi-star" aria-hidden="true"></i><span>Reviews</span>
  </a>
</nav>
