{{--
  Blade twin of resources/js/pharmacy/components/QuickNav.jsx — same four
  links, same active-state highlighting, same .pharmacy-workspace-nav CSS.
  Used on the pharmacy pages that are still Blade forms (add a batch, add a
  medicine) so stepping out of the React section and back still feels like
  one workspace, the same way doctor/ and hospital/ do it.
--}}
<nav class="pharmacy-workspace-nav" aria-label="Pharmacy workspace navigation">
  <a href="{{ route('pharmacy.dashboard') }}" class="{{ request()->routeIs('pharmacy.dashboard') ? 'is-active' : '' }}">
    <i class="bi bi-house-door" aria-hidden="true"></i><span>Dashboard</span>
  </a>
  <a href="{{ route('pharmacy.inventory') }}" class="{{ request()->routeIs('pharmacy.inventory*') ? 'is-active' : '' }}">
    <i class="bi bi-box-seam" aria-hidden="true"></i><span>Inventory</span>
  </a>
  <a href="{{ route('pharmacy.orders') }}" class="{{ request()->routeIs('pharmacy.orders') ? 'is-active' : '' }}">
    <i class="bi bi-bag-check" aria-hidden="true"></i><span>Orders</span>
  </a>
  <a href="{{ route('pharmacy.reviews') }}" class="{{ request()->routeIs('pharmacy.reviews') ? 'is-active' : '' }}">
    <i class="bi bi-star" aria-hidden="true"></i><span>Reviews</span>
  </a>
</nav>
