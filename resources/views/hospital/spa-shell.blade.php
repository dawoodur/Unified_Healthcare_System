@extends('layouts.app')
@section('title', 'Hospital Dashboard')
@section('content')
  <div id="hospital-app"></div>
@endsection

@push('scripts')
  <script>
    window.HOSPITAL_APP_BASE = "{{ parse_url(url('/hospital'), PHP_URL_PATH) }}";
    window.HOSPITAL_API_BASE = "{{ url('/api/hospital') }}";
    window.SANCTUM_CSRF_URL = "{{ url('/sanctum/csrf-cookie') }}";
    window.ASSET_BASE = "{{ rtrim(url('/'), '/') }}";
    // A few actions (discharging a booking, confirming a donation) post a
    // plain form to the existing web routes, because those already own the
    // reward-point and bed-release side effects. Those posts need the
    // session CSRF token, and this layout has no <meta name="csrf-token">.
    window.CSRF_TOKEN = "{{ csrf_token() }}";
  </script>
  <script type="module" src="{{ asset('build-hospital/main.js') }}?v={{ file_exists(public_path('build-hospital/main.js')) ? filemtime(public_path('build-hospital/main.js')) : time() }}"></script>
@endpush
