@extends('layouts.front')

@section('title', 'Mes dépôts')

@section('content')

  @php
    $indicateurs = [
        ['Dépôts', $resume['total'], 'colis'],
        ['En attente de validation', $resume['en_attente'], 'horloge'],
        ['Publiés', $resume['approuve'], 'valide'],
        ['Refusés', $resume['refuse'], 'alerte'],
    ];
  @endphp

  <div class="tc-page">
    <x-ui.hero
      surtitre="Mon espace"
      titre="Mes dépôts"
      sous-titre="Suivez les vêtements que vous avez confiés à TexTileCycle, de la validation jusqu'à leur seconde vie."
      :fil="['Catalogue' => route('depot.catalogue.index'), 'Mes dépôts' => null]">
      <a href="{{ route('depot.mes-depots.create') }}" class="tc-btn"><x-ui.icone nom="plus" /> Déposer un vêtement</a>
    </x-ui.hero>

    <section class="py-5">
      <div class="container">
        @include('depot.partials.flash')

        <div class="row g-3 mb-5">
          @foreach ($indicateurs as [$libelle, $valeur, $icone])
            <div class="col-6 col-lg-3">
              <div class="tc-carte tc-stat h-100">
                <div class="tc-stat__icone"><x-ui.icone :nom="$icone" /></div>
                <div>
                  <div class="tc-stat__valeur">{{ $valeur }}</div>
                  <div class="tc-stat__label">{{ $libelle }}</div>
                </div>
              </div>
            </div>
          @endforeach
        </div>

        @if ($vetements->isEmpty())
          <x-ui.vide icone="cintre" titre="Vous n'avez encore rien déposé">
            <p>Un vêtement que vous ne portez plus peut faire le bonheur de quelqu'un d'autre.</p>
            <a href="{{ route('depot.mes-depots.create') }}" class="tc-btn mt-2">Déposer mon premier vêtement</a>
          </x-ui.vide>
        @else
          <div class="tc-carte">
            <div class="table-responsive">
              <table class="table tc-table">
                <thead>
                  <tr>
                    <th scope="col">Vêtement</th>
                    <th scope="col">Catégorie</th>
                    <th scope="col">Statut</th>
                    <th scope="col">Validation</th>
                    <th scope="col">Déposé le</th>
                    <th scope="col" class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($vetements as $vetement)
                    <tr>
                      <td>
                        <div class="d-flex align-items-center gap-3">
                          <img src="{{ $vetement->miniature_url }}" alt="" class="tc-vignette" loading="lazy">
                          <div>
                            <a href="{{ route('depot.catalogue.show', $vetement) }}" class="text-decoration-none fw-medium" style="color: var(--tc-encre);">{{ $vetement->titre }}</a>
                            <div class="small">Taille {{ $vetement->taille->label() }} · {{ $vetement->etat->label() }}</div>
                          </div>
                        </div>
                      </td>
                      <td><span class="tc-pastille"><i style="background: {{ $vetement->categorie->couleur }}"></i>{{ $vetement->categorie->nom }}</span></td>
                      <td><x-ui.badge :couleur="$vetement->statut->color()">{{ $vetement->statut->label() }}</x-ui.badge></td>
                      <td>
                        <x-ui.badge :couleur="$vetement->moderation->color()">{{ $vetement->moderation->label() }}</x-ui.badge>
                        @if ($vetement->motif_refus)
                          <div class="small mt-2" style="color: var(--tc-erreur); max-width: 260px; white-space: normal;">{{ $vetement->motif_refus }}</div>
                          <div class="small" style="white-space: normal;">Modifiez votre dépôt pour le soumettre à nouveau.</div>
                        @endif
                      </td>
                      <td class="text-nowrap">{{ $vetement->date_depot->format('d/m/Y') }}</td>
                      <td class="text-end text-nowrap">
                        @can('update', $vetement)
                          <a href="{{ route('depot.mes-depots.edit', $vetement) }}" class="tc-btn tc-btn--clair tc-btn--petit">Modifier</a>
                          <form method="POST" action="{{ route('depot.mes-depots.destroy', $vetement) }}" class="d-inline" onsubmit="return confirm(@js("Supprimer « {$vetement->titre} » ?"))">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="tc-btn tc-btn--danger tc-btn--petit">Supprimer</button>
                          </form>
                        @else
                          <span class="small d-inline-flex align-items-center gap-1" title="Un vêtement réservé ou donné ne peut plus être modifié.">
                            <x-ui.icone nom="cadenas" style="width: 15px; height: 15px;" /> Verrouillé
                          </span>
                        @endcan
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>

          <div class="mt-4">
            {{ $vetements->links('pagination::bootstrap-5') }}
          </div>
        @endif
      </div>
    </section>
  </div>
@endsection
