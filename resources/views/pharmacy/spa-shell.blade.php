@extends('layouts.app')
@section('title', 'Pharmacy Dashboard')
@section('content')
  <div id="pharmacy-app"></div>
@endsection

@push('scripts')
  <script>
    window.PHARMACY_APP_BASE = "{{ parse_url(url('/pharmacy'), PHP_URL_PATH) }}";
    window.PHARMACY_API_BASE = "{{ url('/api/pharmacy') }}";
    window.SANCTUM_CSRF_URL = "{{ url('/sanctum/csrf-cookie') }}";
    window.ASSET_BASE = "{{ rtrim(url('/'), '/') }}";
    // Accepting/cancelling an order and removing a batch post plain forms to
    // the existing web routes, which already own those side effects. Those
    // posts need the session CSRF token, and this layout has no
    // <meta name="csrf-token">.
    window.CSRF_TOKEN = "{{ csrf_token() }}";
  </script>
  <script type="module" src="{{ asset('build-pharmacy/main.js') }}?v={{ file_exists(public_path('build-pharmacy/main.js')) ? filemtime(public_path('build-pharmacy/main.js')) : time() }}"></script>
@endpush
