/*
 * TexTileCycle — back office interactions.
 * Replaces the Vue behaviour of the Sneat template: overlay nav, nav scroll shadow, user menu.
 */
document.addEventListener('DOMContentLoaded', function () {
  var nav = document.querySelector('[data-nav]');
  var overlay = document.querySelector('.layout-overlay');
  var navItems = document.querySelector('[data-nav-items]');

  function setNavVisible(isVisible) {
    if (!nav) return;
    nav.classList.toggle('visible', isVisible);
    overlay && overlay.classList.toggle('visible', isVisible);
  }

  document.querySelectorAll('[data-nav-open]').forEach(function (button) {
    button.addEventListener('click', function () { setNavVisible(true); });
  });

  document.querySelectorAll('[data-nav-close]').forEach(function (element) {
    element.addEventListener('click', function () { setNavVisible(false); });
  });

  // Mobile nav is an overlay below 1280px (Vuetify "lg" breakpoint)
  window.matchMedia('(min-width: 1280px)').addEventListener('change', function (event) {
    if (event.matches) setNavVisible(false);
  });

  // Shadow under the nav header once the items are scrolled
  if (navItems && nav) {
    navItems.addEventListener('scroll', function () {
      nav.classList.toggle('scrolled', navItems.scrollTop > 0);
    });
  }

  // User dropdown menu
  var menuToggle = document.querySelector('[data-user-menu-toggle]');
  var menu = document.querySelector('[data-user-menu]');

  function setMenuOpen(isOpen) {
    menu.hidden = !isOpen;
    menuToggle.setAttribute('aria-expanded', String(isOpen));
  }

  if (menuToggle && menu) {
    menuToggle.addEventListener('click', function (event) {
      event.stopPropagation();
      setMenuOpen(menu.hidden);
    });

    document.addEventListener('click', function (event) {
      if (!menu.hidden && !menu.contains(event.target)) setMenuOpen(false);
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') setMenuOpen(false);
    });
  }
});
