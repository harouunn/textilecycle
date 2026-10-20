/* TexTileCycle — design en mouvement (JavaScript pur, sans bibliothèque).
   - [data-revele]     : apparition quand l'élément entre à l'écran (fondu ou dévoilement d'image)
   - [data-compteur]   : nombre qui compte jusqu'à sa valeur quand il devient visible
   - [data-parallaxe]  : image qui se déplace plus lentement que la page (ex. data-parallaxe="0.15")
   - .tc-progression   : barre de progression de lecture
   Rien ne bouge si le système demande de réduire les animations. */
(function () {
  'use strict';

  var reduit = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  document.documentElement.classList.add('tc-js');

  // Apparitions au défilement, avec un léger décalage entre éléments voisins
  var aReveler = document.querySelectorAll('[data-revele]');
  if (reduit || !('IntersectionObserver' in window)) {
    aReveler.forEach(function (el) { el.classList.add('visible'); });
  } else {
    var observateur = new IntersectionObserver(function (entrees) {
      entrees.forEach(function (entree) {
        if (entree.isIntersecting) {
          entree.target.classList.add('visible');
          observateur.unobserve(entree.target);
        }
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 });
    aReveler.forEach(function (el) {
      var freres = el.parentElement ? Array.prototype.filter.call(el.parentElement.children, function (e) { return e.hasAttribute('data-revele'); }) : [];
      el.style.setProperty('--rang', Math.max(0, freres.indexOf(el)) % 6);
      observateur.observe(el);
    });
  }

  // Compteurs animés (format français : 7 500)
  var formater = new Intl.NumberFormat('fr-FR');
  function compter(el) {
    var cible = parseFloat(el.getAttribute('data-compteur')) || 0;
    if (reduit) { el.textContent = formater.format(cible); return; }
    var debut = null, duree = 1600;
    function etape(t) {
      if (!debut) { debut = t; }
      var p = Math.min((t - debut) / duree, 1);
      el.textContent = formater.format(Math.round(cible * (1 - Math.pow(1 - p, 3))));
      if (p < 1) { requestAnimationFrame(etape); }
    }
    requestAnimationFrame(etape);
  }
  var compteurs = document.querySelectorAll('[data-compteur]');
  if ('IntersectionObserver' in window) {
    var obsCompteurs = new IntersectionObserver(function (entrees) {
      entrees.forEach(function (entree) {
        if (entree.isIntersecting) { compter(entree.target); obsCompteurs.unobserve(entree.target); }
      });
    }, { threshold: 0.5 });
    compteurs.forEach(function (el) { obsCompteurs.observe(el); });
  } else {
    compteurs.forEach(compter);
  }

  // Barre de navigation qui se resserre dès que l'on fait défiler la page
  var nav = document.querySelector('nav.navbar');
  function etatNav() { if (nav) { nav.classList.toggle('tc-nav-reduite', window.scrollY > 40); } }
  window.addEventListener('scroll', etatNav, { passive: true });
  etatNav();

  if (reduit) { return; }

  // Barre de progression + parallaxe, calculées une seule fois par image affichée
  var barre = document.querySelector('.tc-progression');
  var parallaxes = document.querySelectorAll('[data-parallaxe]');
  var enAttente = false;
  function surDefilement() {
    if (enAttente) { return; }
    enAttente = true;
    requestAnimationFrame(function () {
      var haut = window.scrollY, total = document.documentElement.scrollHeight - window.innerHeight;
      if (barre) { barre.style.transform = 'scaleX(' + (total > 0 ? haut / total : 0) + ')'; }
      parallaxes.forEach(function (el) {
        var r = el.getBoundingClientRect();
        if (r.bottom > 0 && r.top < window.innerHeight) {
          var vitesse = parseFloat(el.getAttribute('data-parallaxe')) || 0.15;
          el.style.transform = 'translate3d(0,' + ((r.top + r.height / 2 - window.innerHeight / 2) * -vitesse).toFixed(1) + 'px,0)';
        }
      });
      enAttente = false;
    });
  }
  window.addEventListener('scroll', surDefilement, { passive: true });
  surDefilement();

})();
