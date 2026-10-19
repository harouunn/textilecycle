{{--
  Gabarit des pages d'authentification : visuel de marque à gauche, formulaire à droite.
  Paramètres : surtitre, titre, sousTitre, image (dans public/front/images), accroche, atouts (liste de textes)
--}}
@props([
    'surtitre' => 'TexTileCycle',
    'titre',
    'sousTitre' => null,
    'image' => 'banner-image-2.jpg',
    'accroche' => 'Donnez une seconde vie à vos vêtements.',
    'atouts' => [
        'Déposez, réservez et suivez vos vêtements en quelques clics',
        'Réparation, upcycling et dons aux associations',
        'Compte protégé par double authentification (QR code)',
    ],
])
<div class="tc-page tc-auth">
  <aside class="tc-auth__visuel" style="background-image: url('{{ asset('front/images/'.$image) }}');">
    <div>
      <div class="tc-surtitre" style="color: #9fd3ae;">Économie circulaire du textile</div>
      <h2>{{ $accroche }}</h2>
      <ul class="tc-auth__atouts">
        @foreach ($atouts as $atout)
          <li><x-ui.icone nom="valide" /> {{ $atout }}</li>
        @endforeach
      </ul>
    </div>
  </aside>

  <div class="tc-auth__panneau">
    <div class="tc-auth__carte">
      <div class="tc-surtitre">{{ $surtitre }}</div>
      <h1>{{ $titre }}</h1>
      @if ($sousTitre)
        <p>{{ $sousTitre }}</p>
      @endif

      @if (session('status'))
        <div class="tc-alerte tc-alerte--succes" role="status"><x-ui.icone nom="valide" /><div>{{ session('status') }}</div></div>
      @endif

      {{ $slot }}
    </div>
  </div>
</div>

@once
  @push('scripts')
    <script>
      // Afficher / masquer un mot de passe, et force du mot de passe à l'inscription.
      document.querySelectorAll('[data-oeil]').forEach(function (bouton) {
        bouton.addEventListener('click', function () {
          var champ = document.getElementById(bouton.dataset.oeil);
          var visible = champ.type === 'text';
          champ.type = visible ? 'password' : 'text';
          bouton.setAttribute('aria-pressed', String(!visible));
          bouton.setAttribute('aria-label', visible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
          bouton.querySelector('.tc-oeil-voir').hidden = !visible;
          bouton.querySelector('.tc-oeil-cacher').hidden = visible;
        });
      });
      document.querySelectorAll('[data-force]').forEach(function (champ) {
        var jauge = document.getElementById(champ.dataset.force);
        var regles = document.querySelectorAll('[data-regle]');
        var tests = {
          longueur: function (v) { return v.length >= 8; },
          lettre: function (v) { return /[a-zA-Z]/.test(v); },
          chiffre: function (v) { return /\d/.test(v); },
          majuscule: function (v) { return /[A-Z]/.test(v) && /[a-z]/.test(v); },
        };
        champ.addEventListener('input', function () {
          var niveau = 0;
          regles.forEach(function (regle) {
            var ok = tests[regle.dataset.regle](champ.value);
            regle.classList.toggle('ok', ok);
            if (ok) { niveau++; }
          });
          jauge.dataset.niveau = champ.value ? niveau : 0;
        });
      });
    </script>
  @endpush
@endonce
