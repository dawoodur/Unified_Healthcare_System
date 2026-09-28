{{--
  Blade twin of resources/js/admin/components/QuickNav.jsx — same eight
  links, same active-state highlighting, same .admin-workspace-nav CSS.
  Used by the shared notifications / report / inbox pages when an admin is
  logged in, so those never look like a different app.
--}}
<nav class="admin-workspace-nav" aria-label="Admin console navigation">
  <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">
    <i class="bi bi-speedometer2" aria-hidden="true"></i><span>Dashboard</span>
  </a>
  <a href="{{ route('admin.users') }}" class="{{ request()->routeIs('admin.users') ? 'is-active' : '' }}">
    <i class="bi bi-search" aria-hidden="true"></i><span>Users</span>
  </a>
  <a href="{{ route('admin.transactions') }}" class="{{ request()->routeIs('admin.transactions') ? 'is-active' : '' }}">
    <i class="bi bi-cash-stack" aria-hidden="true"></i><span>Transactions</span>
  </a>
  <a href="{{ route('admin.doctor-verifications') }}" class="{{ request()->routeIs('admin.doctor-verifications') ? 'is-active' : '' }}">
    <i class="bi bi-patch-check" aria-hidden="true"></i><span>Verifications</span>
  </a>
  <a href="{{ route('admin.reports') }}" class="{{ request()->routeIs('admin.reports') ? 'is-active' : '' }}">
    <i class="bi bi-flag" aria-hidden="true"></i><span>Reports</span>
  </a>
  <a href="{{ route('admin.analytics') }}" class="{{ request()->routeIs('admin.analytics') ? 'is-active' : '' }}">
    <i class="bi bi-graph-up-arrow" aria-hidden="true"></i><span>Analytics</span>
  </a>
  <a href="{{ route('admin.chat-training') }}" class="{{ request()->routeIs('admin.chat-training') ? 'is-active' : '' }}">
    <i class="bi bi-robot" aria-hidden="true"></i><span>Chat training</span>
  </a>
  <a href="{{ route('admin.medicine-drafts') }}" class="{{ request()->routeIs('admin.medicine-drafts') ? 'is-active' : '' }}">
    <i class="bi bi-capsule" aria-hidden="true"></i><span>Drafts</span>
  </a>
</nav>
