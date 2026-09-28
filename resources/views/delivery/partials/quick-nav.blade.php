{{--
  Blade twin of resources/js/delivery/components/QuickNav.jsx — same four
  links, same active-state highlighting, same .delivery-workspace-nav CSS.
  Used by the shared notifications / report / inbox pages when a delivery
  agent is logged in, so those never look like a different app.
--}}
<nav class="delivery-workspace-nav" aria-label="Delivery workspace navigation">
  <a href="{{ route('delivery.dashboard') }}" class="{{ request()->routeIs('delivery.dashboard') ? 'is-active' : '' }}">
    <i class="bi bi-house-door" aria-hidden="true"></i><span>Dashboard</span>
  </a>
  <a href="{{ route('delivery.available') }}" class="{{ request()->routeIs('delivery.available') ? 'is-active' : '' }}">
    <i class="bi bi-truck" aria-hidden="true"></i><span>Available</span>
  </a>
  <a href="{{ route('delivery.my-deliveries') }}" class="{{ request()->routeIs('delivery.my-deliveries') ? 'is-active' : '' }}">
    <i class="bi bi-box-seam" aria-hidden="true"></i><span>My deliveries</span>
  </a>
  <a href="{{ route('delivery.reviews') }}" class="{{ request()->routeIs('delivery.reviews') ? 'is-active' : '' }}">
    <i class="bi bi-star" aria-hidden="true"></i><span>Reviews</span>
  </a>
</nav>
