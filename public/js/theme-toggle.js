// Wires up the light/dark toggle button in the site header. See
// apply-theme.js for how a saved choice gets applied on the NEXT page
// load before paint — this file only handles the click itself.
document.addEventListener('DOMContentLoaded', function () {
  var btn = document.getElementById('theme-toggle');
  if (!btn) return;

  function isDarkRightNow() {
    var explicit = document.documentElement.getAttribute('data-bs-theme');
    if (explicit === 'dark') return true;
    if (explicit === 'light') return false;
    // No explicit choice saved yet — falling back to whatever the OS/browser prefers.
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
  }

  // The compact header version (icon-only circular button) sets
  // data-icon-only so it shows just the emoji, not the "Light"/"Dark" word
  // — there's no room for text in a small round button next to the bell.
  function updateLabel() {
    var dark = isDarkRightNow();
    if (btn.hasAttribute('data-icon-only')) {
      btn.textContent = dark ? '☀️' : '🌙';
    } else {
      btn.textContent = dark ? '☀️ Light' : '🌙 Dark';
    }
  }

  btn.addEventListener('click', function () {
    var next = isDarkRightNow() ? 'light' : 'dark';
    document.documentElement.setAttribute('data-bs-theme', next);
    localStorage.setItem('theme', next);
    updateLabel();
  });

  updateLabel();
});
