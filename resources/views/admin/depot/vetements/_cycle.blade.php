{{-- Carte « Cycle du vêtement » : ses liens avec les autres gestions et les actions pour l'y envoyer --}}
@php
  $etapes = collect()
      ->concat($vetement->demandesReparation->map(fn ($d) => ['bx-wrench', 'warning', 'Réparation chez '.$d->atelier->nom, $d->statut->label(), $d->created_at, route('admin.ateliers.demandes.show', $d)]))
      ->concat($vetement->projetsUpcycling->map(fn ($p) => ['bx-palette', 'info', 'Upcycling : '.$p->titre, $p->statut->label(), $p->created_at, route('admin.upcycling.projets.show', $p)]))
      ->concat($vetement->dons->map(fn ($d) => ['bx-donate-heart', 'success', 'Don à '.$d->association->nom, $d->statutLabel(), $d->created_at, route('admin.dons.dons.show', $d)]))
      ->sortByDesc(fn ($etape) => $etape[4]);
  $disponible = ! $vetement->reservations->contains(fn ($r) => $r->statut->estActive());
@endphp

<div class="v-card v-theme--light v-card--density-default v-card--variant-elevated mt-6">
  <div class="v-card-item">
    <div class="v-card-item__content">
      <div class="v-card-title">Cycle du vêtement</div>
      <div class="v-card-subtitle">Sa vie dans les autres gestions : réparation, upcycling, don</div>
    </div>
  </div>
  <div class="v-card-text">
    @if ($etapes->isEmpty())
      <p class="text-disabled mb-6">Ce vêtement n'a pas encore été réparé, transformé ni donné.</p>
    @else
      <ul class="list-unstyled mb-6 d-flex flex-column gap-3">
        @foreach ($etapes as [$icone, $couleur, $libelle, $statut, $date, $url])
          <li class="d-flex align-center gap-3">
            <div class="v-avatar v-theme--light text-{{ $couleur }} v-avatar--density-default v-avatar--variant-tonal rounded" style="width: 34px; height: 34px;">
              <i class="{{ $icone }} v-icon notranslate v-theme--light" aria-hidden="true" style="font-size: 18px; height: 18px; width: 18px;"></i>
              <span class="v-avatar__underlay"></span>
            </div>
            <div class="flex-grow-1">
              <a href="{{ $url }}" class="font-weight-medium text-high-emphasis">{{ $libelle }}</a>
              <div class="text-body-2 text-disabled">{{ $statut }} · {{ $date->format('d/m/Y') }}</div>
            </div>
          </li>
        @endforeach
      </ul>
    @endif

    @error('vetement')
      <div class="tc-alert tc-alert--error mb-4">{{ $message }}</div>
    @enderror

    @if (! $disponible)
      <p class="text-body-2 text-medium-emphasis mb-0">Ce vêtement est réservé par un client : annulez la réservation pour pouvoir le réparer, le transformer ou le donner.</p>
    @else
      <div class="v-row">
        <div class="v-col-md-4 v-col-12">
          <form method="POST" action="{{ route('admin.depot.vetements.reparer', $vetement) }}">
            @csrf
            <label for="atelier_id" class="tc-label">Envoyer en réparation</label>
            <select id="atelier_id" name="atelier_id" @class(['tc-control mb-2', 'is-invalid' => $errors->has('atelier_id')]) required>
              <option value="">— Choisir un atelier —</option>
              @foreach ($ateliers as $id => $nom)
                <option value="{{ $id }}">{{ $nom }}</option>
              @endforeach
            </select>
            @error('atelier_id') <div class="tc-error mb-2">{{ $message }}</div> @enderror
            @include('admin.depot.partials.btn', ['label' => 'Diagnostiquer', 'icon' => 'bx-wrench', 'variant' => 'tonal', 'color' => 'warning'])
          </form>
        </div>
        <div class="v-col-md-4 v-col-12">
          <form method="POST" action="{{ route('admin.depot.vetements.donner', $vetement) }}">
            @csrf
            <label for="association_id" class="tc-label">Donner à une association</label>
            <select id="association_id" name="association_id" @class(['tc-control mb-2', 'is-invalid' => $errors->has('association_id')]) required>
              <option value="">— Choisir une association —</option>
              @foreach ($associations as $id => $nom)
                <option value="{{ $id }}">{{ $nom }}</option>
              @endforeach
            </select>
            @error('association_id') <div class="tc-error mb-2">{{ $message }}</div> @enderror
            @include('admin.depot.partials.btn', ['label' => 'Donner', 'icon' => 'bx-donate-heart', 'variant' => 'tonal', 'color' => 'success'])
          </form>
        </div>
        <div class="v-col-md-4 v-col-12">
          <form method="POST" action="{{ route('admin.depot.vetements.transformer', $vetement) }}" onsubmit="return confirm(@js("Transformer « {$vetement->titre} » en projet d'upcycling ? Le vêtement sortira du catalogue."))">
            @csrf
            <span class="tc-label">Transformer (upcycling)</span>
            <p class="text-body-2 text-medium-emphasis mb-2">Crée un projet brouillon avec la photo du vêtement.</p>
            @include('admin.depot.partials.btn', ['label' => 'Transformer', 'icon' => 'bx-palette', 'variant' => 'tonal', 'color' => 'info'])
          </form>
        </div>
      </div>
    @endif
  </div>
  <span class="v-card__underlay"></span>
</div>
