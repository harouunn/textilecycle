@extends('layouts.front')

@section('title', 'Catalogue')

@section('content')

  @php
    $categorieCourante = $categories->firstWhere('id', $categorieActive);
    // Lien qui garde les filtres en cours et en change un seul.
    $avec = fn (array $changement) => route('depot.catalogue.index', array_filter([...$filters, ...$changement, 'page' => null], fn ($v) => filled($v)));
    $libellesActifs = array_filter([
        'search' => filled($filters['search'] ?? null) ? '« '.$filters['search'].' »' : null,
        'categorie' => $categorieCourante?->nom,
        'genre' => isset($filters['genre']) ? \App\Enums\Depot\Genre::from($filters['genre'])->label() : null,
        'taille' => isset($filters['taille']) ? 'Taille '.$filters['taille'] : null,
        'etat' => isset($filters['etat']) ? \App\Enums\Depot\Etat::from($filters['etat'])->label() : null,
    ]);
  @endphp

  <div class="tc-page">
    <x-ui.hero
      surtitre="Catalogue solidaire"
      :titre="$categorieCourante?->nom ?? 'Tous les vêtements'"
      :sous-titre="$categorieCourante?->description ?? 'Des vêtements déposés et vérifiés par notre communauté, prêts pour une seconde vie. Réservez-les gratuitement et venez les retirer.'"
      :fil="['Accueil' => route('home'), 'Catalogue' => null]">
      @auth
        <a href="{{ route('depot.mes-depots.create') }}" class="tc-btn"><x-ui.icone nom="plus" /> Déposer un vêtement</a>
        <a href="{{ route('depot.reservations.index') }}" class="tc-btn tc-btn--clair">Mes réservations</a>
      @else
        <a href="{{ route('login') }}" class="tc-btn tc-btn--clair">Se connecter pour réserver</a>
      @endauth
    </x-ui.hero>

    @if ($impactGlobal['vetements'] > 0)
      <div class="tc-bandeau-impact">
        <div class="container d-flex flex-wrap justify-content-center gap-4 gap-md-5">
          <span><x-ui.icone nom="cintre" /> <strong>{{ $impactGlobal['vetements'] }}</strong> vêtement(s) ont trouvé une seconde vie</span>
          <span><x-ui.icone nom="feuille" /> <strong>{{ \App\Services\Depot\ImpactEcologique::formaterEau($impactGlobal['eau']) }}</strong> d'eau économisés</span>
          <span><x-ui.icone nom="etincelle" /> <strong>{{ number_format($impactGlobal['co2'], 0, ',', ' ') }} kg</strong> de CO₂ évités</span>
        </div>
      </div>
    @endif

    <section class="py-5">
      <div class="container">
        @include('depot.partials.flash')

        <div class="row g-5">
          {{-- Filtres --}}
          <aside class="col-lg-3" aria-label="Filtres du catalogue">
            <form method="GET" action="{{ route('depot.catalogue.index') }}" class="tc-form">
              @if ($categorieActive)
                <input type="hidden" name="categorie" value="{{ $categorieActive }}">
              @endif
              @if (filled($filters['tri'] ?? null))
                <input type="hidden" name="tri" value="{{ $filters['tri'] }}">
              @endif

              <div class="tc-filtres">
                <h3><label for="search">Rechercher</label></h3>
                <div class="input-group">
                  <input id="search" type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Chemise, jean, laine…">
                  <button class="tc-btn tc-btn--petit" type="submit" aria-label="Rechercher"><x-ui.icone nom="recherche" /></button>
                </div>
              </div>

              <div class="tc-filtres">
                <h3>Catégories</h3>
                <nav class="tc-liste-cat">
                  <a href="{{ $avec(['categorie' => null]) }}" @class(['active' => ! $categorieActive])>
                    <span>Toutes les catégories</span><small>{{ $categories->sum('vetements_count') }}</small>
                  </a>
                  @foreach ($categories as $categorie)
                    <a href="{{ $avec(['categorie' => $categorie->id]) }}" @class(['active' => $categorieActive === $categorie->id])>
                      <span class="tc-pastille" style="text-transform: none; letter-spacing: 0; font-size: .95rem; color: inherit;">
                        <i style="background: {{ $categorie->couleur }}"></i>{{ $categorie->nom }}
                      </span>
                      <small>{{ $categorie->vetements_count }}</small>
                    </a>
                  @endforeach
                </nav>
              </div>

              <div class="tc-filtres">
                <h3><label for="genre">Pour qui</label></h3>
                <select id="genre" name="genre" class="form-select">
                  <option value="">Tout le monde</option>
                  @foreach (\App\Enums\Depot\Genre::options() as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['genre'] ?? null) === $value)>{{ $label }}</option>
                  @endforeach
                </select>
              </div>

              <fieldset class="tc-filtres">
                <legend>Taille</legend>
                <div class="tc-tailles">
                  <input type="radio" id="taille-toutes" name="taille" value="" @checked(empty($filters['taille']))>
                  <label for="taille-toutes">Toutes</label>
                  @foreach (\App\Enums\Depot\Taille::options() as $value => $label)
                    <input type="radio" id="taille-{{ $value }}" name="taille" value="{{ $value }}" @checked(($filters['taille'] ?? null) === $value)>
                    <label for="taille-{{ $value }}">{{ $label }}</label>
                  @endforeach
                </div>
              </fieldset>

              <div class="tc-filtres">
                <h3><label for="etat">État</label></h3>
                <select id="etat" name="etat" class="form-select">
                  <option value="">Tous les états</option>
                  @foreach (\App\Enums\Depot\Etat::options() as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['etat'] ?? null) === $value)>{{ $label }}</option>
                  @endforeach
                </select>
              </div>

              <div class="tc-filtres d-grid gap-2">
                <button type="submit" class="tc-btn"><x-ui.icone nom="filtre" /> Appliquer les filtres</button>
                @if ($libellesActifs)
                  <a href="{{ route('depot.catalogue.index') }}" class="tc-btn tc-btn--clair">Tout effacer</a>
                @endif
              </div>
            </form>
          </aside>

          {{-- Résultats --}}
          <div class="col-lg-9">
            <div class="tc-barre">
              <div>
                <strong class="text-dark">{{ $vetements->total() }}</strong> vêtement(s) disponible(s)
                @if ($libellesActifs)
                  <div class="d-flex flex-wrap gap-2 mt-2">
                    @foreach ($libellesActifs as $cle => $libelle)
                      <a href="{{ $avec([$cle => null]) }}" class="tc-puce-filtre" title="Retirer ce filtre">{{ $libelle }} <x-ui.icone nom="fermer" style="width: 12px; height: 12px;" /></a>
                    @endforeach
                  </div>
                @endif
              </div>
              <nav class="tc-tri" aria-label="Trier">
                <span class="small text-uppercase" style="letter-spacing: .08em;">Trier :</span>
                @foreach (['recents' => 'Plus récents', 'anciens' => 'Plus anciens', 'titre' => 'De A à Z'] as $tri => $libelle)
                  <a href="{{ $avec(['tri' => $tri === 'recents' ? null : $tri]) }}" @class(['active' => ($filters['tri'] ?? 'recents') === $tri])>{{ $libelle }}</a>
                @endforeach
              </nav>
            </div>

            @if ($vetements->isEmpty())
              <x-ui.vide icone="recherche" titre="Aucun vêtement ne correspond">
                <p>Essayez d'élargir votre recherche ou de retirer un filtre.</p>
                <a href="{{ route('depot.catalogue.index') }}" class="tc-btn tc-btn--clair mt-2">Voir tout le catalogue</a>
              </x-ui.vide>
            @else
              <div class="row g-4">
                @foreach ($vetements as $vetement)
                  <div class="col-6 col-md-4" data-revele>
                    <x-depot.carte-vetement :vetement="$vetement" />
                  </div>
                @endforeach
              </div>

              <div class="mt-5">
                {{ $vetements->links('pagination::bootstrap-5') }}
              </div>
            @endif
          </div>
        </div>
      </div>
    </section>
  </div>
@endsection
