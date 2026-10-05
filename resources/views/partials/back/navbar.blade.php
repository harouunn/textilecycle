@php($initials = collect(explode(' ', Auth::user()->name))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode(''))

<header class="layout-navbar navbar-blur">
  <div class="navbar-content-container">
    <div data-v-fba8a720 class="d-flex h-100 align-center">
      {{-- Ouverture du menu latéral (mobile / tablette) --}}
      <button data-v-fba8a720 type="button" class="v-btn v-btn--icon v-theme--light text-default v-btn--density-default v-btn--size-default v-btn--variant-text ms-n3 d-lg-none" data-nav-open aria-label="Ouvrir le menu">
        <span class="v-btn__overlay"></span><span class="v-btn__underlay"></span>
        <span class="v-btn__content"><i class="bx-menu v-icon notranslate v-theme--light v-icon--size-default" aria-hidden="true"></i></span>
      </button>

      {{-- Recherche --}}
      <div data-v-fba8a720 class="d-flex align-center ms-lg-n3">
        <button data-v-fba8a720 type="button" class="v-btn v-btn--icon v-theme--light text-default v-btn--density-default v-btn--size-default v-btn--variant-text" aria-label="Rechercher">
          <span class="v-btn__overlay"></span><span class="v-btn__underlay"></span>
          <span class="v-btn__content"><i class="bx-search v-icon notranslate v-theme--light v-icon--size-default" aria-hidden="true"></i></span>
        </button>
        <span data-v-fba8a720 class="d-none d-md-flex align-center text-disabled ms-2">Rechercher un dépôt, une association…</span>
      </div>

      <div data-v-fba8a720 class="v-spacer"></div>

      <a data-v-fba8a720 href="{{ route('home') }}" class="v-btn v-btn--icon v-theme--light text-default v-btn--density-default v-btn--size-default v-btn--variant-text" title="Voir le site">
        <span class="v-btn__overlay"></span><span class="v-btn__underlay"></span>
        <span class="v-btn__content"><i class="bx-store-alt v-icon notranslate v-theme--light v-icon--size-default" aria-hidden="true"></i></span>
      </a>

      <button data-v-fba8a720 type="button" class="v-btn v-btn--icon v-theme--light text-default v-btn--density-default v-btn--size-default v-btn--variant-text me-1" title="Notifications">
        <span class="v-btn__overlay"></span><span class="v-btn__underlay"></span>
        <span class="v-btn__content"><i class="bx-bell v-icon notranslate v-theme--light v-icon--size-default" aria-hidden="true"></i></span>
      </button>

      {{-- Menu utilisateur --}}
      <div class="position-relative">
        <div data-v-fba8a720 class="v-badge v-badge--bordered v-badge--dot">
          <div class="v-badge__wrapper">
            <button type="button" class="v-avatar v-theme--light text-primary v-avatar--density-default v-avatar--size-default v-avatar--variant-tonal cursor-pointer" data-user-menu-toggle aria-haspopup="menu" aria-expanded="false">
              <span class="text-body-1 font-weight-medium">{{ $initials }}</span>
              <span class="v-avatar__underlay"></span>
            </button>
            <span class="v-badge__badge v-theme--light bg-success" style="top: calc(100% - 11px); left: calc(100% - 11px);"></span>
          </div>
        </div>

        <div class="tc-user-menu" data-user-menu hidden>
          <div class="v-list v-theme--light v-list--density-default v-list--one-line" role="menu">
            <div class="v-list-item v-theme--light v-list-item--density-default v-list-item--one-line">
              <div class="v-list-item__prepend">
                <div class="v-avatar v-theme--light text-primary v-avatar--density-default v-avatar--size-default v-avatar--variant-tonal me-3">
                  <span class="text-body-1 font-weight-medium">{{ $initials }}</span>
                  <span class="v-avatar__underlay"></span>
                </div>
              </div>
              <div class="v-list-item__content">
                <div class="v-list-item-title font-weight-semibold">{{ Auth::user()->name }}</div>
                <div class="v-list-item-subtitle">{{ Auth::user()->email }}</div>
              </div>
            </div>

            <hr class="v-divider v-theme--light my-2" aria-orientation="horizontal" role="separator">

            <a href="{{ route('profile.edit') }}" class="v-list-item v-list-item--link v-theme--light v-list-item--density-default v-list-item--one-line v-list-item--variant-text" role="menuitem">
              <span class="v-list-item__overlay"></span><span class="v-list-item__underlay"></span>
              <div class="v-list-item__prepend"><i class="bx-user v-icon notranslate v-theme--light me-2" aria-hidden="true" style="font-size: 22px; height: 22px; width: 22px;"></i></div>
              <div class="v-list-item__content"><div class="v-list-item-title">Mon profil</div></div>
            </a>

            <a href="{{ route('home') }}" class="v-list-item v-list-item--link v-theme--light v-list-item--density-default v-list-item--one-line v-list-item--variant-text" role="menuitem">
              <span class="v-list-item__overlay"></span><span class="v-list-item__underlay"></span>
              <div class="v-list-item__prepend"><i class="bx-home v-icon notranslate v-theme--light me-2" aria-hidden="true" style="font-size: 22px; height: 22px; width: 22px;"></i></div>
              <div class="v-list-item__content"><div class="v-list-item-title">Retour au site</div></div>
            </a>

            <hr class="v-divider v-theme--light my-2" aria-orientation="horizontal" role="separator">

            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit" class="v-list-item v-list-item--link v-theme--light v-list-item--density-default v-list-item--one-line v-list-item--variant-text w-100" role="menuitem">
                <span class="v-list-item__overlay"></span><span class="v-list-item__underlay"></span>
                <div class="v-list-item__prepend"><i class="bx-log-out v-icon notranslate v-theme--light me-2" aria-hidden="true" style="font-size: 22px; height: 22px; width: 22px;"></i></div>
                <div class="v-list-item__content"><div class="v-list-item-title">Déconnexion</div></div>
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</header>
