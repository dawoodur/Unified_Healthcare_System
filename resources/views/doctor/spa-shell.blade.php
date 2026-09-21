@extends('layouts.app')
@section('title', 'Doctor Dashboard')
@section('content')
  <div id="doctor-app"></div>
@endsection

@push('scripts')
  <script>
    window.DOCTOR_APP_BASE = "{{ parse_url(url('/doctor'), PHP_URL_PATH) }}";
    window.DOCTOR_API_BASE = "{{ url('/api/doctor') }}";
    window.SANCTUM_CSRF_URL = "{{ url('/sanctum/csrf-cookie') }}";
    window.ASSET_BASE = "{{ rtrim(url('/'), '/') }}";
  </script>
  <script type="module" src="{{ asset('build-doctor/main.js') }}?v={{ file_exists(public_path('build-doctor/main.js')) ? filemtime(public_path('build-doctor/main.js')) : time() }}"></script>
@endpush
