// Applied via a blocking <script> in <head> (not deferred) — see
// layouts/app.blade.php — so a saved dark-mode preference takes effect
// before the page paints, instead of flashing light-then-dark on load.
// Sets Bootstrap 5.3's own data-bs-theme attribute (not a custom one) so
// Bootstrap's built-in components (navbar, cards, forms, tables, ...)
// switch automatically along with the rest of the site's custom CSS.
//
// Unlike a plain CSS "@media (prefers-color-scheme: dark)" block,
// Bootstrap only goes dark when data-bs-theme="dark" is actually present
// on <html> — it doesn't watch the OS preference itself. So an explicit
// saved choice always wins, but with nothing saved yet, this falls back
// to the OS/browser preference explicitly here instead of silently
// defaulting to light.
(function () {
  var saved = localStorage.getItem('theme');
  if (saved === 'dark' || saved === 'light') {
    document.documentElement.setAttribute('data-bs-theme', saved);
  } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
    document.documentElement.setAttribute('data-bs-theme', 'dark');
  }
})();
