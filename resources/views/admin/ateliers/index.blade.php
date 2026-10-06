@extends('layouts.back')

@section('title', 'Gestion des ateliers')

@section('content')
<div class="v-row">
  <div class="v-col-12">
    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
      <div class="v-card-item">
        <div class="v-card-item__content">
          <div class="v-card-title d-flex align-center justify-space-between flex-wrap gap-3">
            <span>Liste des ateliers</span>
            <a href="{{ route('admin.ateliers.create') }}" class="v-btn v-theme--light bg-primary v-btn--density-default v-btn--size-default v-btn--variant-elevated">
              <span class="v-btn__overlay"></span>
              <span class="v-btn__underlay"></span>
              <span class="v-btn__content">
                <i class="bx-plus v-icon notranslate v-theme--light me-1" aria-hidden="true" style="font-size: 20px;"></i>
                Nouvel atelier
              </span>
            </a>
          </div>
        </div>
      </div>

      @if(session('success'))
        <div class="mx-5 mb-3">
          <div class="v-alert v-theme--light bg-success v-alert--density-default v-alert--variant-tonal" role="alert">
            <span class="v-alert__underlay"></span>
            <div class="v-alert__prepend">
              <i class="bx-check-circle v-icon notranslate v-theme--light" aria-hidden="true" style="font-size: 24px;"></i>
            </div>
            <div class="v-alert__content">{{ session('success') }}</div>
          </div>
        </div>
      @endif

      <div class="v-card-text">
        @if($ateliers->count() > 0)
          <div class="v-table v-theme--light v-table--density-default">
            <div class="v-table__wrapper">
              <table>
                <thead>
                  <tr>
                    <th class="text-start">Nom</th>
                    <th class="text-start">Adresse</th>
                    <th class="text-start">Ville</th>
                    <th class="text-start">Contact</th>
                    <th class="text-center">Statut</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($ateliers as $atelier)
                    <tr>
                      <td class="text-start">
                        <strong>{{ $atelier->nom }}</strong>
                      </td>
                      <td class="text-start">{{ $atelier->adresse }}</td>
                      <td class="text-start">
                        {{ $atelier->ville }}<br>
                        <small class="text-disabled">{{ $atelier->code_postal }}</small>
                      </td>
                      <td class="text-start">
                        @if($atelier->telephone)
                          <div><i class="bx-phone me-1"></i>{{ $atelier->telephone }}</div>
                        @endif
                        @if($atelier->email)
                          <div><i class="bx-envelope me-1"></i>{{ $atelier->email }}</div>
                        @endif
                      </td>
                      <td class="text-center">
                        @if($atelier->actif)
                          <span class="v-chip v-theme--light bg-success v-chip--density-default v-chip--size-small v-chip--variant-tonal">
                            <span class="v-chip__underlay"></span>
                            <span class="v-chip__content">Actif</span>
                          </span>
                        @else
                          <span class="v-chip v-theme--light bg-secondary v-chip--density-default v-chip--size-small v-chip--variant-tonal">
                            <span class="v-chip__underlay"></span>
                            <span class="v-chip__content">Inactif</span>
                          </span>
                        @endif
                      </td>
                      <td class="text-end">
                        <div class="d-flex gap-2 justify-end">
                          <a href="{{ route('admin.ateliers.show', $atelier) }}" class="v-btn v-theme--light v-btn--density-default v-btn--size-small v-btn--variant-text" title="Voir">
                            <span class="v-btn__overlay"></span>
                            <span class="v-btn__underlay"></span>
                            <span class="v-btn__content">
                              <i class="bx-show v-icon notranslate v-theme--light" aria-hidden="true"></i>
                            </span>
                          </a>
                          <a href="{{ route('admin.ateliers.edit', $atelier) }}" class="v-btn v-theme--light v-btn--density-default v-btn--size-small v-btn--variant-text" title="Modifier">
                            <span class="v-btn__overlay"></span>
                            <span class="v-btn__underlay"></span>
                            <span class="v-btn__content">
                              <i class="bx-edit v-icon notranslate v-theme--light" aria-hidden="true"></i>
                            </span>
                          </a>
                          <form action="{{ route('admin.ateliers.destroy', $atelier) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet atelier ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="v-btn v-theme--light text-error v-btn--density-default v-btn--size-small v-btn--variant-text" title="Supprimer">
                              <span class="v-btn__overlay"></span>
                              <span class="v-btn__underlay"></span>
                              <span class="v-btn__content">
                                <i class="bx-trash v-icon notranslate v-theme--light" aria-hidden="true"></i>
                              </span>
                            </button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>

          <div class="mt-4">
            {{ $ateliers->links() }}
          </div>
        @else
          <div class="text-center py-8">
            <i class="bx-store-alt v-icon notranslate v-theme--light text-disabled" aria-hidden="true" style="font-size: 64px;"></i>
            <h6 class="text-h6 mt-3 mb-2">Aucun atelier</h6>
            <p class="text-body-2 text-disabled mb-4">Commencez par ajouter votre premier atelier partenaire</p>
            <a href="{{ route('admin.ateliers.create') }}" class="v-btn v-theme--light bg-primary v-btn--density-default v-btn--size-default v-btn--variant-elevated">
              <span class="v-btn__overlay"></span>
              <span class="v-btn__underlay"></span>
              <span class="v-btn__content">Ajouter un atelier</span>
            </a>
          </div>
        @endif
      </div>

      <span class="v-card__underlay"></span>
    </div>
  </div>
</div>
@endsection
