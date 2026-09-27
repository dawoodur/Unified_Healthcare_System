{{--
  Blade twin of resources/js/hospital/components/PageHeader.jsx. Expects
  $icon (a bootstrap-icons class name, e.g. "bi-building"), $title, and an
  optional $subtitle.
--}}
<div class="hospital-page-header">
  <div class="hospital-page-header-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></div>
  <div class="hospital-page-header-copy">
    <h1>{{ $title }}</h1>
    @isset($subtitle)
      <p>{{ $subtitle }}</p>
    @endisset
  </div>
</div>
