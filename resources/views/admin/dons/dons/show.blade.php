@extends('layouts.back')

@section('title', 'Don n°'.$don->id)

@include('admin.dons.partials.styles')

@section('content')
<div class="mb-6">
  <a href="{{ route('admin.dons.dons.index') }}" class="text-body-2">‹ Retour aux dons</a>
  <div class="d-flex flex-wrap align-center justify-space-between gap-4 mt-2">
    <div class="d-flex align-center gap-3">
      <h4 class="text-h4 mb-0">Don n°{{ $don->id }}</h4>
      @include('admin.dons.partials.statut', ['don' => $don])
      @if ($don->estARisque()) @include('admin.dons.partials.badge-risque', ['don' => $don]) @endif
    </div>
    <div class="d-flex gap-2">
      @include('admin.dons.partials.btn', ['href' => route('admin.dons.dons.edit', $don), 'label' => 'Modifier', 'icon' => 'bx-edit'])
      <form method="POST" action="{{ route('admin.dons.dons.destroy', $don) }}" class="tcd-inline">
        @csrf
        @method('DELETE')
        @include('admin.dons.partials.btn', ['label' => 'Supprimer', 'icon' => 'bx-trash', 'color' => 'error', 'variant' => 'tonal', 'confirm' => "Supprimer le don n°{$don->id} ?"])
      </form>
    </div>
  </div>
</div>

@include('admin.dons.partials.flash')

<div class="v-row">
  <div class="v-col-md-8 v-col-12">
    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
      <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Détail</div></div></div>
      <div class="v-card-text">
        <dl class="tcd-dl">
          <dt>Association</dt>
          <dd>
            @if ($don->association->trashed())
              {{ $don->association->nom }} ({{ $don->association->ville }}) — <span class="text-medium-emphasis">archivée</span>
            @else
              <a href="{{ route('admin.dons.associations.show', $don->association) }}">{{ $don->association->nom }}</a> ({{ $don->association->ville }})
            @endif
          </dd>
          @if ($don->vetement)
            <dt>Vêtement du catalogue</dt>
            <dd><a href="{{ route('admin.depot.vetements.show', $don->vetement) }}">{{ $don->vetement->titre }}</a></dd>
          @endif
          @if ($don->campagne)
            <dt>Campagne</dt><dd><a href="{{ route('admin.dons.campagnes.show', $don->campagne) }}">{{ $don->campagne->titre }}</a></dd>
          @endif
          @if ($don->reference)
            <dt>Reçu</dt><dd><a href="{{ route('dons.mes-dons.recu', $don) }}">{{ $don->reference }} (PDF)</a></dd>
          @endif
          <dt>Donateur</dt>
          <dd>{{ $don->user->name }} — <a href="mailto:{{ $don->user->email }}">{{ $don->user->email }}</a></dd>
          <dt>Type d'article</dt><dd>{{ $don->typeLabel() }}</dd>
          <dt>Quantité</dt><dd>{{ $don->quantite }} article(s)</dd>
          <dt>Poids estimé</dt><dd>{{ $don->poids_kg ? number_format($don->poids_kg, 2, ',', ' ').' kg' : '—' }}</dd>
          <dt>État général</dt><dd>{{ $don->etatLabel() }}</dd>
          <dt>Mode de remise</dt><dd>{{ $don->modeLabel() }}</dd>
          @if ($don->adresse_collecte)
            <dt>Adresse de collecte</dt><dd>{{ $don->adresse_collecte }}</dd>
          @endif
          <dt>Date de remise</dt><dd>{{ $don->date_remise->format('d/m/Y') }}</dd>
          <dt>Message</dt><dd class="tcd-pre">{{ $don->message ?: '—' }}</dd>
          <dt>Proposé le</dt><dd>{{ $don->created_at->format('d/m/Y à H:i') }}</dd>
        </dl>
      </div>
      <span class="v-card__underlay"></span>
    </div>
  </div>

  <div class="v-col-md-4 v-col-12">
    @php($transitions = collect($don->statut->allowedTransitions())->filter(fn ($statut) => auth()->user()->can('transition', [$don, $statut])))
    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated mb-6">
      <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Changer le statut</div></div></div>
      <div class="v-card-text">
        @if ($transitions->isEmpty())
          <p class="mb-0 text-medium-emphasis">« {{ $don->statutLabel() }} » est un statut final : ce don ne peut plus changer de statut.</p>
        @else
          <form method="POST" action="{{ route('admin.dons.dons.statut', $don) }}">
            @csrf
            @method('PATCH')
            <div class="tcd-field">
              <label for="statut" class="tcd-label">Nouveau statut</label>
              <select id="statut" name="statut" @class(['tcd-input', 'is-invalid' => $errors->has('statut')])>
                @foreach ($transitions as $statut)
                  <option value="{{ $statut->value }}" @selected(old('statut') === $statut->value)>{{ $statut->label() }}</option>
                @endforeach
              </select>
              @error('statut') <div class="tcd-error">{{ $message }}</div> @enderror
            </div>
            <div class="tcd-field">
              <label for="commentaire" class="tcd-label">Commentaire</label>
              <textarea id="commentaire" name="commentaire" rows="3" maxlength="1000" @class(['tcd-input', 'is-invalid' => $errors->has('commentaire')]) placeholder="Visible par le donateur dans l'historique.">{{ old('commentaire') }}</textarea>
              @error('commentaire') <div class="tcd-error">{{ $message }}</div> @enderror
            </div>
            @include('admin.dons.partials.btn', ['label' => 'Mettre à jour', 'icon' => 'bx-check'])
          </form>
        @endif
      </div>
      <span class="v-card__underlay"></span>
    </div>

    @php($risque = data_get($don->ia_analyse, 'risque'))
    @php($photo = data_get($don->ia_analyse, 'photo'))
    @if ($risque || $photo)
      <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated mb-6">
        <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Analyse automatique</div></div></div>
        <div class="v-card-text">
          @if ($risque)
            <div class="d-flex align-center gap-2 mb-2">
              <strong>Risque : {{ $risque['score'] }}/100</strong>
              @if ($don->estARisque()) @include('admin.dons.partials.badge-risque', ['don' => $don]) @endif
            </div>
            @if (! empty($risque['explication']))
              <p class="mb-2">{{ $risque['explication'] }}</p>
            @endif
            @if (! empty($risque['raisons']))
              <ul class="text-body-2 mb-2 ps-4">
                @foreach ($risque['raisons'] as $raison) <li>{{ $raison }}</li> @endforeach
              </ul>
            @endif
            <p class="tcd-hint">{{ ($risque['source'] ?? '') === 'ia' ? 'Explication rédigée par l\'IA à partir des règles déclenchées.' : 'Résultat des règles automatiques.' }}</p>
          @endif
          @if ($photo)
            <p class="mb-1"><strong>Photo analysée par l'IA</strong> (confiance {{ round(($photo['confiance'] ?? 0) * 100) }} %)</p>
            <p class="text-body-2 mb-0">
              @if ($photo['est_textile'] ?? false)
                {{ \App\Enums\Dons\TypeArticle::tryFrom($photo['type_article'] ?? '')?->label() }} ·
                {{ \App\Enums\Dons\EtatGeneral::tryFrom($photo['etat_general'] ?? '')?->label() }} ·
                ≈ {{ $photo['quantite_estimee'] ?? '?' }} article(s), {{ $photo['poids_estime_kg'] ?? '?' }} kg
                @if (! empty($photo['description_courte'])) <br>« {{ $photo['description_courte'] }} » @endif
              @else
                Pas de textile reconnu.
              @endif
            </p>
          @endif
        </div>
        <span class="v-card__underlay"></span>
      </div>
    @endif

    <div class="v-card v-theme--light v-card--density-default v-card--variant-elevated">
      <div class="v-card-item"><div class="v-card-item__content"><div class="v-card-title">Historique</div></div></div>
      <div class="v-card-text">
        @include('admin.dons.partials.historique', ['don' => $don])
      </div>
      <span class="v-card__underlay"></span>
    </div>
  </div>
</div>
@endsection
