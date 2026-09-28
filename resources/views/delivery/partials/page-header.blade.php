{{--
  Blade twin of the PageHeader in resources/js/delivery/components/PageShell.jsx.
  Expects $icon (a bootstrap-icons class name), $title, optional $subtitle.
--}}
<div class="delivery-page-header">
  <div class="delivery-page-header-icon"><i class="bi {{ $icon }}" aria-hidden="true"></i></div>
  <div class="delivery-page-header-copy">
    <h1>{{ $title }}</h1>
    @isset($subtitle)
      <p>{{ $subtitle }}</p>
    @endisset
  </div>
</div>
