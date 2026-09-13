// Mobile sidebar: hidden off-canvas by default below the lg breakpoint
// (see the .app-sidebar CSS), slid in via the .open class when the
// hamburger button is tapped. The backdrop click (and Escape) closes it
// again, same pattern as a standard off-canvas menu.
document.addEventListener('DOMContentLoaded', function () {
  var toggle = document.getElementById('sidebarToggle');
  var sidebar = document.getElementById('appSidebar');
  var backdrop = document.getElementById('appBackdrop');
  if (!toggle || !sidebar || !backdrop) return;

  function open() {
    sidebar.classList.add('open');
    backdrop.classList.add('open');
  }

  function close() {
    sidebar.classList.remove('open');
    backdrop.classList.remove('open');
  }

  toggle.addEventListener('click', function () {
    sidebar.classList.contains('open') ? close() : open();
  });

  backdrop.addEventListener('click', close);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') close();
  });
});
