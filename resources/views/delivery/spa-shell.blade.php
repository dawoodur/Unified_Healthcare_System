@extends('layouts.app')
@section('title', 'Delivery Dashboard')
@section('content')
  <div id="delivery-app"></div>
@endsection

@push('scripts')
  <script>
    window.DELIVERY_APP_BASE = "{{ parse_url(url('/delivery'), PHP_URL_PATH) }}";
    window.DELIVERY_API_BASE = "{{ url('/api/delivery') }}";
    window.SANCTUM_CSRF_URL = "{{ url('/sanctum/csrf-cookie') }}";
    window.ASSET_BASE = "{{ rtrim(url('/'), '/') }}";
    // Claiming an order, requesting the patient's code and confirming a
    // delivery all post plain forms to the existing web routes, which own
    // those side effects. Those posts need the session CSRF token, and this
    // layout has no <meta name="csrf-token">.
    window.CSRF_TOKEN = "{{ csrf_token() }}";
    // Set only on the redirect that follows claiming an order (a one-request
    // flash), so that run's card starts simulating its route on arrival
    // instead of waiting for the agent to press Simulate. The share script
    // clears it the moment it starts, so navigating back here in-app cannot
    // restart a route the agent had stopped.
    window.DELIVERY_AUTO_SIMULATE = {{ session('simulate_order') ? (int) session('simulate_order') : 'null' }};
  </script>
  {{-- Loaded before the SPA so window.initDeliveryShare exists by the time
       the React cards mount and call it. --}}
  <script src="{{ asset('js/delivery-share-location.js') }}?v={{ filemtime(public_path('js/delivery-share-location.js')) }}"></script>
  <script type="module" src="{{ asset('build-delivery/main.js') }}?v={{ file_exists(public_path('build-delivery/main.js')) ? filemtime(public_path('build-delivery/main.js')) : time() }}"></script>
@endpush
