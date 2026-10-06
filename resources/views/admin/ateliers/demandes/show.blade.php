@extends('layouts.back')

@section('title', $demande->titre)

@section('content')
  @include('admin.depot.partials.styles')
  @include('admin.depot.partials.flash')

  @php
    $details = [
        'Atelier' => $demande->atelier->nom,
        'Client' => $demande->user->name.' ('.$demande->user->email.')',
        'Type de vêtement' => $demande->type_vetement->label(),
        'Réparation(s)' => $demande->reparations->map->label()->join(', '),
        'Coût estimé' => \App\Models\DemandeReparation::formaterMontant((float) $demande->cout_estime),
        'Délai estimé' => $demande->delai_estime_jours.' jour(s)',
        'Reçue le' => $demande->created_at->format('d/m/Y à H:i'),
    ];
  @endphp

  <div class="v-row">
    <div class="v-col-md-7 v-col-12">
      <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
        <div class="v-card-item">
          <div class="v-card-item__content">
            <div class="v-card-title">{{ $demande->titre }}</div>
            <div class="v-card-subtitle">Demande n° {{ $demande->id }}</div>
          </div>
          <div class="v-card-item__append">
            @include('admin.depot.partials.chip', ['label' => $demande->statut->label(), 'color' => $demande->statut->color()])
          </div>
        </div>
        <div class="v-card-text">
          @if ($demande->description)
            <p class="mb-6" style="white-space: pre-line;">{{ $demande->description }}</p>
          @endif

          @if ($demande->photo)
            <img src="{{ $demande->photo_url }}" alt="Photo de {{ $demande->titre }}" class="tc-photo mb-6">
          @endif

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

          <div class="text-overline mb-1">Diagnostic automatique</div>
          <p class="text-body-2 mb-6" style="white-space: pre-line;">{{ $demande->diagnostic }}</p>

          <div class="d-flex flex-wrap gap-4">
            @include('admin.depot.partials.btn', ['label' => 'Modifier', 'icon' => 'bx-edit-alt', 'href' => route('admin.ateliers.demandes.edit', $demande)])
            <form method="POST" action="{{ route('admin.ateliers.demandes.destroy', $demande) }}" class="d-inline" onsubmit="return confirm(@js("Supprimer la demande « {$demande->titre} » ?"))">
              @csrf
              @method('DELETE')
              @include('admin.depot.partials.btn', ['label' => 'Supprimer', 'icon' => 'bx-trash', 'variant' => 'tonal', 'color' => 'error'])
            </form>
            @include('admin.depot.partials.btn', ['label' => 'Retour à la liste', 'icon' => 'bx-left-arrow-alt', 'variant' => 'text', 'color' => 'secondary', 'href' => route('admin.ateliers.demandes.index')])
          </div>
        </div>
        <span class="v-card__underlay"></span>
      </div>
    </div>

    {{-- Réponse de l'atelier --}}
    <div class="v-col-md-5 v-col-12">
      <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
        <div class="v-card-item">
          <div class="v-card-item__content">
            <div class="v-card-title">Traiter la demande</div>
            <div class="v-card-subtitle">Visible par le client dans « Mes demandes »</div>
          </div>
        </div>
        <div class="v-card-text">
          <form method="POST" action="{{ route('admin.ateliers.demandes.traiter', $demande) }}">
            @csrf
            @method('PATCH')

            <div class="v-row">
              <div class="v-col-12">
                <label for="statut" class="tc-label">Statut *</label>
                <select id="statut" name="statut" required @class(['tc-control', 'is-invalid' => $errors->has('statut')])>
                  @foreach (\App\Enums\Ateliers\StatutDemande::options() as $value => $label)
                    <option value="{{ $value }}" @selected(old('statut', $demande->statut->value) === $value)>{{ $label }}</option>
                  @endforeach
                </select>
                @error('statut')
                  <div class="tc-error">{{ $message }}</div>
                @enderror
              </div>

              <div class="v-col-sm-6 v-col-12">
                <label for="cout_final" class="tc-label">Coût final ({{ \App\Models\DemandeReparation::DEVISE }})</label>
                <input id="cout_final" type="number" name="cout_final" step="0.01" min="0" max="9999" value="{{ old('cout_final', $demande->cout_final) }}"
                  placeholder="{{ $demande->cout_estime }}" @class(['tc-control', 'is-invalid' => $errors->has('cout_final')])>
                @error('cout_final')
                  <div class="tc-error">{{ $message }}</div>
                @enderror
              </div>

              <div class="v-col-sm-6 v-col-12">
                <label for="date_prevue" class="tc-label">Prêt le</label>
                <input id="date_prevue" type="date" name="date_prevue" value="{{ old('date_prevue', $demande->date_prevue?->format('Y-m-d')) }}"
                  @class(['tc-control', 'is-invalid' => $errors->has('date_prevue')])>
                @error('date_prevue')
                  <div class="tc-error">{{ $message }}</div>
                @enderror
              </div>

              <div class="v-col-12">
                <label for="reponse_atelier" class="tc-label">Message au client</label>
                <textarea id="reponse_atelier" name="reponse_atelier" maxlength="2000" placeholder="Obligatoire en cas de refus."
                  @class(['tc-control', 'is-invalid' => $errors->has('reponse_atelier')])>{{ old('reponse_atelier', $demande->reponse_atelier) }}</textarea>
                @error('reponse_atelier')
                  <div class="tc-error">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="mt-6">
              @include('admin.depot.partials.btn', ['label' => 'Enregistrer la réponse', 'icon' => 'bx-send'])
            </div>
          </form>
        </div>
        <span class="v-card__underlay"></span>
      </div>
    </div>
  </div>
@endsection
