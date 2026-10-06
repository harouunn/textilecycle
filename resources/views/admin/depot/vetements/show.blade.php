@extends('layouts.back')

@section('title', $vetement->titre)

@section('content')
  @include('admin.depot.partials.styles')
  @include('admin.depot.partials.flash')

  @php
    $details = [
        'Catégorie' => $vetement->categorie->nom,
        'Taille' => $vetement->taille->label(),
        'Genre' => $vetement->genre->label(),
        'Matière' => $vetement->matiere,
        'État' => $vetement->etat->label(),
        'Déposant' => $vetement->user->name,
        'Date de dépôt' => $vetement->date_depot->format('d/m/Y'),
    ];
  @endphp

  <div class="v-row">
    <div class="v-col-md-5 v-col-12">
      <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
        <div class="v-card-text">
          <img src="{{ $vetement->photo_url }}" alt="{{ $vetement->titre }}" class="tc-photo">
          @unless ($vetement->photo)
            <div class="text-body-2 text-disabled mt-2">Aucune photo : image d'illustration.</div>
          @endunless
        </div>
        <span class="v-card__underlay"></span>
      </div>

      {{-- Validation du dépôt par l'admin --}}
      <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated mt-6">
        <div class="v-card-item">
          <div class="v-card-item__content">
            <div class="v-card-title">Validation du dépôt</div>
            <div class="v-card-subtitle">Seuls les vêtements approuvés apparaissent dans le catalogue</div>
          </div>
          <div class="v-card-item__append">
            @include('admin.depot.partials.chip', ['label' => $vetement->moderation->label(), 'color' => $vetement->moderation->color()])
          </div>
        </div>
        <div class="v-card-text">
          @if ($vetement->motif_refus)
            <p class="text-body-2 mb-4"><strong>Motif du refus :</strong> {{ $vetement->motif_refus }}</p>
          @endif

          @unless ($vetement->moderation === \App\Enums\Depot\Moderation::Approuve)
            <form method="POST" action="{{ route('admin.depot.vetements.approuver', $vetement) }}" class="mb-4">
              @csrf
              @method('PATCH')
              @include('admin.depot.partials.btn', ['label' => 'Approuver et publier', 'icon' => 'bx-check-circle', 'color' => 'success'])
            </form>
          @endunless

          <form method="POST" action="{{ route('admin.depot.vetements.refuser', $vetement) }}">
            @csrf
            @method('PATCH')
            <label for="motif_refus" class="tc-label">Motif du refus *</label>
            <textarea id="motif_refus" name="motif_refus" required maxlength="500" placeholder="ex. La photo est floue, merci d'en envoyer une autre."
              @class(['tc-control mb-3', 'is-invalid' => $errors->has('motif_refus')])>{{ old('motif_refus') }}</textarea>
            @error('motif_refus')
              <div class="tc-error mb-3">{{ $message }}</div>
            @enderror
            @include('admin.depot.partials.btn', ['label' => $vetement->moderation === \App\Enums\Depot\Moderation::Refuse ? 'Modifier le motif' : 'Refuser', 'icon' => 'bx-x-circle', 'variant' => 'tonal', 'color' => 'error'])
          </form>
        </div>
        <span class="v-card__underlay"></span>
      </div>
    </div>

    <div class="v-col-md-7 v-col-12">
      <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
        <div class="v-card-item">
          <div class="v-card-item__content">
            <div class="v-card-title">{{ $vetement->titre }}</div>
            <div class="v-card-subtitle">Ajouté le {{ $vetement->created_at->format('d/m/Y à H:i') }}</div>
          </div>
          <div class="v-card-item__append">
            @include('admin.depot.partials.chip', ['label' => $vetement->statut->label(), 'color' => $vetement->statut->color()])
          </div>
        </div>
        <div class="v-card-text">
          <p class="mb-6" style="white-space: pre-line;">{{ $vetement->description }}</p>

          <div class="v-table v-theme--light v-table--density-compact mb-6">
            <div class="v-table__wrapper">
              <table>
                <tbody>
                  @foreach ($details as $label => $value)
                    <tr>
                      <th class="text-start" style="width: 40%;">{{ $label }}</th>
                      <td>{{ $value }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>

          <div class="d-flex flex-wrap gap-4">
            @include('admin.depot.partials.btn', ['label' => 'Modifier', 'icon' => 'bx-edit-alt', 'href' => route('admin.depot.vetements.edit', $vetement)])
            <form method="POST" action="{{ route('admin.depot.vetements.destroy', $vetement) }}" class="d-inline" onsubmit="return confirm(@js("Supprimer « {$vetement->titre} » ?"))">
              @csrf
              @method('DELETE')
              @include('admin.depot.partials.btn', ['label' => 'Supprimer', 'icon' => 'bx-trash', 'variant' => 'tonal', 'color' => 'error'])
            </form>
            @include('admin.depot.partials.btn', ['label' => 'Retour à la liste', 'icon' => 'bx-left-arrow-alt', 'variant' => 'text', 'color' => 'secondary', 'href' => route('admin.depot.vetements.index')])
          </div>
        </div>
        <span class="v-card__underlay"></span>
      </div>
    </div>
  </div>
@endsection
