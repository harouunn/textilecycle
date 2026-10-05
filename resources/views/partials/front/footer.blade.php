<footer id="footer" class="mt-5">
  <div class="container">
    <div class="row d-flex flex-wrap justify-content-between py-5">
      <div class="col-md-3 col-sm-6">
        <div class="footer-menu footer-menu-001">
          <div class="footer-intro mb-4">
            <a href="{{ route('home') }}" class="tc-brand">TexTile<span>Cycle</span></a>
          </div>
          <p>TexTileCycle donne une seconde vie à vos vêtements : dépôt, réparation, upcycling et don aux associations
            partenaires. Moins de déchets, plus de solidarité.</p>
          <div class="social-links">
            <ul class="list-unstyled d-flex flex-wrap gap-3">
              <li>
                <a href="#" class="text-secondary" aria-label="Facebook">
                  <svg width="24" height="24" viewBox="0 0 24 24"><use xlink:href="#facebook"></use></svg>
                </a>
              </li>
              <li>
                <a href="#" class="text-secondary" aria-label="Instagram">
                  <svg width="24" height="24" viewBox="0 0 24 24"><use xlink:href="#instagram"></use></svg>
                </a>
              </li>
              <li>
                <a href="#" class="text-secondary" aria-label="YouTube">
                  <svg width="24" height="24" viewBox="0 0 24 24"><use xlink:href="#youtube"></use></svg>
                </a>
              </li>
              <li>
                <a href="#" class="text-secondary" aria-label="Pinterest">
                  <svg width="24" height="24" viewBox="0 0 24 24"><use xlink:href="#pinterest"></use></svg>
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="footer-menu footer-menu-002">
          <h5 class="widget-title text-uppercase mb-4">Liens rapides</h5>
          <ul class="menu-list list-unstyled text-uppercase border-animation-left fs-6">
            <li class="menu-item"><a href="{{ route('home') }}" class="item-anchor">Accueil</a></li>
            <li class="menu-item"><a href="{{ route('home') }}#concept" class="item-anchor">Le concept</a></li>
            <li class="menu-item"><a href="{{ route('home') }}#services" class="item-anchor">Nos services</a></li>
            <li class="menu-item"><a href="{{ route('home') }}#associations" class="item-anchor">Associations</a></li>
            @guest
              <li class="menu-item"><a href="{{ route('register') }}" class="item-anchor">Créer un compte</a></li>
            @endguest
          </ul>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="footer-menu footer-menu-003">
          <h5 class="widget-title text-uppercase mb-4">Aide & infos</h5>
          <ul class="menu-list list-unstyled text-uppercase border-animation-left fs-6">
            <li class="menu-item"><a href="#" class="item-anchor">Comment déposer ?</a></li>
            <li class="menu-item"><a href="#" class="item-anchor">Vêtements acceptés</a></li>
            <li class="menu-item"><a href="#" class="item-anchor">Points de collecte</a></li>
            <li class="menu-item"><a href="#" class="item-anchor">Devenir association partenaire</a></li>
            <li class="menu-item"><a href="#" class="item-anchor">FAQ</a></li>
          </ul>
        </div>
      </div>
      <div class="col-md-3 col-sm-6">
        <div class="footer-menu footer-menu-004 border-animation-left">
          <h5 class="widget-title text-uppercase mb-4">Contact</h5>
          <p>Une question ou une suggestion ? <a href="mailto:contact@textilecycle.fr"
              class="item-anchor">contact@textilecycle.fr</a></p>
          <p>Besoin d'aide ? Appelez-nous au <a href="tel:+33100000000" class="item-anchor">01 00 00 00 00</a></p>
        </div>
      </div>
    </div>
  </div>
  <div class="border-top py-4">
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <p class="mb-md-0">Ensemble, prolongeons la vie de nos vêtements ♻️</p>
        </div>
        <div class="col-md-6 text-md-end">
          <p class="mb-0">© {{ date('Y') }} TexTileCycle. Tous droits réservés. Design par <a href="https://templatesjungle.com"
              target="_blank" rel="noopener">TemplatesJungle</a></p>
        </div>
      </div>
    </div>
  </div>
</footer>
