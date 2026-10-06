<nav class="navbar navbar-expand-lg bg-light text-uppercase fs-6 p-3 border-bottom align-items-center">
  <div class="container-fluid">
    <div class="row justify-content-between align-items-center w-100">

      <div class="col-auto">
        <a class="navbar-brand tc-brand" href="{{ route('home') }}">
          TexTile<span>Cycle</span>
        </a>
      </div>

      <div class="col-auto">
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar"
          aria-controls="offcanvasNavbar" aria-label="Ouvrir le menu">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar"
          aria-labelledby="offcanvasNavbarLabel">
          <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasNavbarLabel">Menu</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"
              aria-label="Fermer"></button>
          </div>

          <div class="offcanvas-body">
            <ul class="navbar-nav justify-content-end align-items-lg-center flex-grow-1 gap-1 gap-md-5 pe-3">
              <li class="nav-item">
                <a @class(['nav-link', 'active' => request()->routeIs('home')]) href="{{ route('home') }}">Accueil</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="{{ route('home') }}#concept">Le concept</a>
              </li>
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="dropdownServices" data-bs-toggle="dropdown"
                  aria-haspopup="true" aria-expanded="false">Nos services</a>
                <ul class="dropdown-menu list-unstyled" aria-labelledby="dropdownServices">
                  {{-- Une entrée par module : chaque équipe ne modifie que son fichier dans partials/front/modules/ --}}
                  @include('partials.front.modules.depot')
                  @include('partials.front.modules.ateliers')
                  @include('partials.front.modules.upcycling')
                  @include('partials.front.modules.dons')
                </ul>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="{{ route('home') }}#associations">Associations</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="#footer">Contact</a>
              </li>

              @auth
                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle" href="#" id="dropdownAccount" data-bs-toggle="dropdown"
                    aria-haspopup="true" aria-expanded="false">
                    <svg width="18" height="18" viewBox="0 0 24 24" class="me-1"><use xlink:href="#user"></use></svg>
                    {{ Auth::user()->name }}
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end list-unstyled" aria-labelledby="dropdownAccount">
                    <li><a href="{{ route('admin.dashboard') }}" class="dropdown-item item-anchor">Tableau de bord</a></li>
                    <li><a href="{{ route('profile.edit') }}" class="dropdown-item item-anchor">Mon profil</a></li>
                    <li>
                      <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item item-anchor">Déconnexion</button>
                      </form>
                    </li>
                  </ul>
                </li>
              @else
                <li class="nav-item">
                  <a @class(['nav-link', 'active' => request()->routeIs('login')]) href="{{ route('login') }}">Connexion</a>
                </li>
                @if (Route::has('register'))
                  <li class="nav-item">
                    <a class="btn btn-outline-dark rounded-pill" href="{{ route('register') }}">Inscription</a>
                  </li>
                @endif
              @endauth
            </ul>
          </div>
        </div>
      </div>

    </div>
  </div>
</nav>
