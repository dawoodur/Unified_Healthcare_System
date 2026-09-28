{{--
  Blade twin of the PageHeader in resources/js/pharmacy/components/PageShell.jsx.
  Expects $icon (a bootstrap-icons class name), $title, optional $subtitle.
--}}
<div class="pharmacy-page-header">
  <div class="pharmacy-page-header-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></div>
  <div class="pharmacy-page-header-copy">
    <h1>{{ $title }}</h1>
    @isset($subtitle)
      <p>{{ $subtitle }}</p>
    @endisset
  </div>
</div>
