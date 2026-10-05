@extends('layouts.back')

@section('title', 'Détails de l\'atelier')

@section('content')
<div class="v-row">
  <div class="v-col-12">
    <nav aria-label="breadcrumb" class="d-flex align-center mb-4">
      <ol class="v-breadcrumbs v-theme--light v-breadcrumbs--density-default">
        <li class="v-breadcrumbs-item">
          <a href="{{ route('admin.ateliers.index') }}" class="v-breadcrumbs-item--link">Ateliers</a>
        </li>
        <li aria-hidden="true" class="v-breadcrumbs-divider">/</li>
        <li class="v-breadcrumbs-item v-breadcrumbs-item--disabled">
          <span class="v-breadcrumbs-item--link">{{ $atelier->nom }}</span>
        </li>
      </ol>
    </nav>

    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
      <div class="v-card-item">
        <div class="v-card-item__content">
          <div class="v-card-title d-flex align-center justify-space-between flex-wrap gap-3">
            <div class="d-flex align-center gap-3">
              <span>{{ $atelier->nom }}</span>
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
            </div>
            <div class="d-flex gap-2">
              <a href="{{ route('admin.ateliers.edit', $atelier) }}" class="v-btn v-theme--light bg-primary v-btn--density-default v-btn--size-default v-btn--variant-elevated">
                <span class="v-btn__overlay"></span>
                <span class="v-btn__underlay"></span>
                <span class="v-btn__content">
                  <i class="bx-edit v-icon notranslate v-theme--light me-1" aria-hidden="true"></i>
                  Modifier
                </span>
              </a>
              <form action="{{ route('admin.ateliers.destroy', $atelier) }}" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet atelier ?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="v-btn v-theme--light bg-error v-btn--density-default v-btn--size-default v-btn--variant-elevated">
                  <span class="v-btn__overlay"></span>
                  <span class="v-btn__underlay"></span>
                  <span class="v-btn__content">
                    <i class="bx-trash v-icon notranslate v-theme--light me-1" aria-hidden="true"></i>
                    Supprimer
                  </span>
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>

      <div class="v-card-text">
        <div class="v-row">
          {{-- Informations générales --}}
          <div class="v-col-md-6 v-col-12">
            <div class="v-card v-theme--light v-card--density-default v-card--variant-outlined">
              <div class="v-card-item">
                <div class="v-card-item__content">
                  <div class="v-card-title">
                    <i class="bx-info-circle v-icon notranslate v-theme--light me-2" aria-hidden="true"></i>
                    Informations générales
                  </div>
                </div>
              </div>
              <div class="v-card-text">
                <div class="v-list v-theme--light v-list--density-default v-list--one-line">
                  <div class="v-list-item">
                    <div class="v-list-item__content">
                      <div class="v-list-item-title text-body-2 text-disabled">Nom</div>
                      <div class="v-list-item-subtitle text-h6 mt-1">{{ $atelier->nom }}</div>
                    </div>
                  </div>
                  @if($atelier->description)
                    <div class="v-list-item">
                      <div class="v-list-item__content">
                        <div class="v-list-item-title text-body-2 text-disabled">Description</div>
                        <div class="v-list-item-subtitle mt-1">{{ $atelier->description }}</div>
                      </div>
                    </div>
                  @endif
                  <div class="v-list-item">
                    <div class="v-list-item__content">
                      <div class="v-list-item-title text-body-2 text-disabled">Statut</div>
                      <div class="v-list-item-subtitle mt-1">
                        @if($atelier->actif)
                          <span class="text-success font-weight-medium">Actif</span>
                        @else
                          <span class="text-secondary font-weight-medium">Inactif</span>
                        @endif
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <span class="v-card__underlay"></span>
            </div>
          </div>

          {{-- Localisation --}}
          <div class="v-col-md-6 v-col-12">
            <div class="v-card v-theme--light v-card--density-default v-card--variant-outlined">
              <div class="v-card-item">
                <div class="v-card-item__content">
                  <div class="v-card-title">
                    <i class="bx-map v-icon notranslate v-theme--light me-2" aria-hidden="true"></i>
                    Localisation
                  </div>
                </div>
              </div>
              <div class="v-card-text">
                <div class="v-list v-theme--light v-list--density-default v-list--one-line">
                  <div class="v-list-item">
                    <div class="v-list-item__content">
                      <div class="v-list-item-title text-body-2 text-disabled">Adresse</div>
                      <div class="v-list-item-subtitle mt-1">{{ $atelier->adresse }}</div>
                    </div>
                  </div>
                  <div class="v-list-item">
                    <div class="v-list-item__content">
                      <div class="v-list-item-title text-body-2 text-disabled">Ville</div>
                      <div class="v-list-item-subtitle mt-1">{{ $atelier->ville }}</div>
                    </div>
                  </div>
                  <div class="v-list-item">
                    <div class="v-list-item__content">
                      <div class="v-list-item-title text-body-2 text-disabled">Code postal</div>
                      <div class="v-list-item-subtitle mt-1">{{ $atelier->code_postal }}</div>
                    </div>
                  </div>
                </div>
              </div>
              <span class="v-card__underlay"></span>
            </div>
          </div>

          {{-- Contact --}}
          <div class="v-col-12">
            <div class="v-card v-theme--light v-card--density-default v-card--variant-outlined">
              <div class="v-card-item">
                <div class="v-card-item__content">
                  <div class="v-card-title">
                    <i class="bx-phone v-icon notranslate v-theme--light me-2" aria-hidden="true"></i>
                    Contact
                  </div>
                </div>
              </div>
              <div class="v-card-text">
                <div class="v-list v-theme--light v-list--density-default v-list--one-line">
                  <div class="v-list-item">
                    <div class="v-list-item__content">
                      <div class="v-list-item-title text-body-2 text-disabled">Téléphone</div>
                      <div class="v-list-item-subtitle mt-1">
                        @if($atelier->telephone)
                          <a href="tel:{{ $atelier->telephone }}" class="text-primary">
                            <i class="bx-phone me-1"></i>{{ $atelier->telephone }}
                          </a>
                        @else
                          <span class="text-disabled">Non renseigné</span>
                        @endif
                      </div>
                    </div>
                  </div>
                  <div class="v-list-item">
                    <div class="v-list-item__content">
                      <div class="v-list-item-title text-body-2 text-disabled">Email</div>
                      <div class="v-list-item-subtitle mt-1">
                        @if($atelier->email)
                          <a href="mailto:{{ $atelier->email }}" class="text-primary">
                            <i class="bx-envelope me-1"></i>{{ $atelier->email }}
                          </a>
                        @else
                          <span class="text-disabled">Non renseigné</span>
                        @endif
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <span class="v-card__underlay"></span>
            </div>
          </div>

          {{-- Dates --}}
          <div class="v-col-12">
            <div class="v-card v-theme--light v-card--density-default v-card--variant-outlined">
              <div class="v-card-item">
                <div class="v-card-item__content">
                  <div class="v-card-title">
                    <i class="bx-time v-icon notranslate v-theme--light me-2" aria-hidden="true"></i>
                    Historique
                  </div>
                </div>
              </div>
              <div class="v-card-text">
                <div class="v-list v-theme--light v-list--density-default v-list--one-line">
                  <div class="v-list-item">
                    <div class="v-list-item__content">
                      <div class="v-list-item-title text-body-2 text-disabled">Créé le</div>
                      <div class="v-list-item-subtitle mt-1">{{ $atelier->created_at->format('d/m/Y à H:i') }}</div>
                    </div>
                  </div>
                  <div class="v-list-item">
                    <div class="v-list-item__content">
                      <div class="v-list-item-title text-body-2 text-disabled">Dernière modification</div>
                      <div class="v-list-item-subtitle mt-1">{{ $atelier->updated_at->format('d/m/Y à H:i') }}</div>
                    </div>
                  </div>
                </div>
              </div>
              <span class="v-card__underlay"></span>
            </div>
          </div>
        </div>
      </div>

      <span class="v-card__underlay"></span>
    </div>
  </div>
</div>
@endsection
