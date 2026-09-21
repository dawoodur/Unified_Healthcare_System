{{--
  Blade twin of resources/js/doctor/components/PageHeader.jsx (no slot
  support needed here — none of the doctor-only pages that use this need
  header action buttons, they put those in the card body below instead).
  Expects $icon (a bootstrap-icons class name, e.g. "bi-folder2-open"),
  $title, and optional $subtitle.
--}}
<div class="doctor-page-header">
  <div class="doctor-page-header-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></div>
  <div class="doctor-page-header-copy">
    <h1>{{ $title }}</h1>
    @isset($subtitle)
      <p>{{ $subtitle }}</p>
    @endisset
  </div>
</div>
