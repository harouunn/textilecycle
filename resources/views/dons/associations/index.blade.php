@extends('layouts.front')

@section('title', 'Associations partenaires')

@push('styles')
  <link rel="stylesheet" href="{{ asset_v('vendor/dons/leaflet/leaflet.css') }}">
@endpush

@section('content')
<div class="tc-page">
  <x-ui.hero surtitre="Associations & dons" titre="Donnez à une association"
    sous-titre="Choisissez une association partenaire, découvrez ses besoins et proposez-lui vos vêtements en bon état."
    :fil="['Accueil' => route('home'), 'Dons' => null]">
    <a href="{{ route('dons.campagnes.index') }}" class="tc-btn tc-btn--clair">Campagnes en cours</a>
    @auth
      <a href="{{ route('dons.mes-dons') }}" class="tc-btn tc-btn--clair">Mes dons</a>
    @endauth
  </x-ui.hero>
</div>

<section class="py-5 border-bottom">
  <div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
      <h2 class="h4 text-uppercase mb-0">Notre impact ensemble</h2>
      <div class="d-flex gap-3 small">
        <a href="{{ route('dons.classement') }}">Classement des donateurs</a>
        @auth <a href="{{ route('dons.mon-impact') }}">Mon impact</a> @endauth
      </div>
    </div>
    @include('dons.partials.impact', ['impact' => $impact])
  </div>
</section>

<section class="py-5">
  <div class="container">
    @include('dons.partials.flash')

    @if ($positionInvalide)
      <div class="alert alert-warning">La position reçue est invalide : la liste n'a pas été filtrée par distance.</div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
      <form method="GET" action="{{ route('dons.index') }}" class="d-flex flex-wrap align-items-end gap-2">
        <div>
          <label for="ville" class="form-label small text-uppercase mb-1">Filtrer par ville</label>
          <select id="ville" name="ville" class="form-select" onchange="this.form.submit()">
            <option value="">Toutes les villes</option>
            @foreach ($villes as $v)
              <option value="{{ $v }}" @selected($ville === $v)>{{ $v }}</option>
            @endforeach
          </select>
        </div>
        @if ($presDeMoi)
          <input type="hidden" name="lat" value="{{ $position['lat'] }}">
          <input type="hidden" name="lng" value="{{ $position['lng'] }}">
          <input type="hidden" name="rayon" value="{{ $rayon }}">
        @endif
        <noscript><button type="submit" class="btn btn-dark">Filtrer</button></noscript>
        @if ($ville !== '' || $presDeMoi)
          <a href="{{ route('dons.index') }}" class="btn btn-link">Réinitialiser</a>
        @endif
      </form>

      <div class="d-flex flex-wrap align-items-end gap-2">
        <div>
          <label for="rayon" class="form-label small text-uppercase mb-1">Rayon</label>
          <select id="rayon" class="form-select">
            @foreach ([5, 10, 25, 50] as $km)
              <option value="{{ $km }}" @selected($rayon === $km)>{{ $km }} km</option>
            @endforeach
          </select>
        </div>
        <button type="button" class="btn btn-outline-dark" id="pres-de-moi">📍 Près de moi</button>
      </div>
    </div>
    <p id="pres-de-moi-erreur" class="small text-danger" role="status" hidden></p>
    @if ($presDeMoi)
      <p class="small text-muted">Associations à moins de {{ $rayon }} km de votre position, de la plus proche à la plus lointaine.</p>
    @endif

    <div id="carte-associations" class="rounded border mb-5" style="height: 380px" aria-label="Carte des associations partenaires"></div>

    <div class="row g-4">
      @forelse ($associations as $association)
        <div class="col-md-6 col-lg-4">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body d-flex flex-column">
              <div class="d-flex align-items-center gap-3 mb-3">
                @if ($association->logo_url)
                  <img src="{{ $association->logo_url }}" alt="" width="56" height="56" class="rounded object-fit-cover">
                @else
                  <span class="d-inline-flex align-items-center justify-content-center rounded bg-light fs-4" style="width:56px;height:56px">{{ mb_strtoupper(mb_substr($association->nom, 0, 1)) }}</span>
                @endif
                <div>
                  <h5 class="card-title mb-0">{{ $association->nom }}</h5>
                  <small class="text-muted">
                    {{ $association->ville }}
                    @isset($association->distance_km) · à {{ number_format((float) $association->distance_km, 1, ',', ' ') }} km @endisset
                  </small>
                </div>
              </div>
              <p class="card-text">{{ \Illuminate\Support\Str::limit($association->description, 140) }}</p>
              <p class="small mb-4"><strong>Besoins :</strong> {{ \Illuminate\Support\Str::limit(str_replace("\n", ' · ', $association->besoins), 110) }}</p>
              <div class="mt-auto d-flex gap-2">
                <a href="{{ route('dons.associations.show', $association) }}" class="btn btn-outline-dark btn-sm">Voir les besoins</a>
                <a href="{{ route('dons.create', $association) }}" class="btn btn-dark btn-sm">Faire un don</a>
              </div>
            </div>
          </div>
        </div>
      @empty
        <div class="col-12">
          <p class="text-center text-muted py-5 mb-0">
            @if ($presDeMoi)
              Aucune association active à moins de {{ $rayon }} km. Essayez un rayon plus grand.
            @else
              Aucune association active {{ $ville !== '' ? 'à '.$ville : 'pour le moment' }}.
            @endif
          </p>
        </div>
      @endforelse
    </div>

    <div class="d-flex justify-content-center mt-5">
      {{ $associations->links('dons.partials.pagination') }}
    </div>
  </div>
</section>
@include('dons.partials.assistant')
@endsection

@push('scripts')
<script src="{{ asset_v('vendor/dons/leaflet/leaflet.js') }}"></script>
<script>
  (function () {
    const marqueurs = @js($marqueurs);
    const position = @js($position);
    const carte = L.map('carte-associations', { scrollWheelZoom: false });
    L.Icon.Default.imagePath = @js(asset('vendor/dons/leaflet/images').'/');
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">contributeurs OpenStreetMap</a>',
    }).addTo(carte);

    const points = [];
    marqueurs.forEach(function (m) {
      // Contenu construit en DOM (textContent) : un nom d'association ne peut pas injecter de HTML.
      const popup = document.createElement('div');
      const titre = document.createElement('strong');
      titre.textContent = m.nom;
      const ville = document.createElement('div');
      ville.textContent = m.ville;
      const lien = document.createElement('a');
      lien.href = m.url;
      lien.textContent = 'Voir les besoins →';
      popup.append(titre, ville, lien);
      L.marker([m.lat, m.lng]).addTo(carte).bindPopup(popup);
      points.push([m.lat, m.lng]);
    });

    if (position) {
      L.circleMarker([position.lat, position.lng], { radius: 8, color: '#0d6efd' }).addTo(carte).bindPopup('Vous êtes ici');
      points.push([position.lat, position.lng]);
    }
    if (points.length) {
      carte.fitBounds(points, { padding: [30, 30], maxZoom: 14 });
    } else {
      carte.setView([36.8065, 10.1815], 11); // Tunis
    }

    const bouton = document.getElementById('pres-de-moi');
    const erreur = document.getElementById('pres-de-moi-erreur');
    bouton.addEventListener('click', function () {
      if (!('geolocation' in navigator)) {
        erreur.textContent = 'Votre navigateur ne permet pas la géolocalisation.';
        erreur.hidden = false;
        return;
      }
      bouton.disabled = true;
      navigator.geolocation.getCurrentPosition(function (p) {
        const url = new URL(@js(route('dons.index')));
        url.searchParams.set('lat', p.coords.latitude.toFixed(5));
        url.searchParams.set('lng', p.coords.longitude.toFixed(5));
        url.searchParams.set('rayon', document.getElementById('rayon').value);
        window.location = url.toString();
      }, function () {
        bouton.disabled = false;
        erreur.textContent = 'Position indisponible : autorisez la localisation dans votre navigateur, puis réessayez.';
        erreur.hidden = false;
      }, { enableHighAccuracy: false, timeout: 10000 });
    });
  })();
</script>
@endpush
