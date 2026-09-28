@extends('layouts.app')
@section('title', 'Admin Console')
@section('content')
  <div id="admin-app"></div>
@endsection

@push('scripts')
  <script>
    window.ADMIN_APP_BASE = "{{ parse_url(url('/admin'), PHP_URL_PATH) }}";
    window.ADMIN_API_BASE = "{{ url('/api/admin') }}";
    window.SANCTUM_CSRF_URL = "{{ url('/sanctum/csrf-cookie') }}";
    window.ASSET_BASE = "{{ rtrim(url('/'), '/') }}";
    // Approving a certificate, answering a report, labelling a chat sample
    // and approving a medicine draft all post plain forms to the existing web
    // routes, which own those side effects. Those posts need the session CSRF
    // token, and this layout has no <meta name="csrf-token">.
    window.CSRF_TOKEN = "{{ csrf_token() }}";
    // Those same plain-form posts redirect back here on both success and
    // validation failure. Without this the message and any errors were
    // silently lost — the page just reloaded with no visible result, most
    // noticeable on a form with real validation (see ChatTraining.jsx's new
    // "New label" form) rather than a one-click approve/reject.
    window.ADMIN_FLASH = {
      success: @json(session('success')),
      errors: @json($errors->any() ? $errors->all() : null),
      old: @json($errors->any() ? old() : null),
    };
  </script>
  <script type="module" src="{{ asset('build-admin/main.js') }}?v={{ file_exists(public_path('build-admin/main.js')) ? filemtime(public_path('build-admin/main.js')) : time() }}"></script>
@endpush
