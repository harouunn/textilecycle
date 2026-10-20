@if ($projet->statut === \App\Enums\Upcycling\StatutProjet::Soumis)
  @can('moderate', $projet)
    <div class="tcu-decisions" aria-label="Décision administrative">
      <form method="POST" action="{{ route('admin.upcycling.projets.publier', $projet) }}" class="tcu-inline-form" data-upcycling-publish="{{ $projet->id }}"
        onsubmit="return confirm('Accepter et publier ce projet dans la galerie publique ?')">
        @csrf
        <button type="submit" class="v-btn v-theme--light bg-success v-btn--density-default v-btn--size-small v-btn--variant-elevated">Accepter et publier</button>
      </form>
      @php($refusActif = (string) old('moderation_projet_id') === (string) $projet->id)
      <details class="tcu-rejection" data-upcycling-reject="{{ $projet->id }}" @if ($refusActif && $errors->has('commentaire_refus')) open @endif>
        <summary class="v-btn v-theme--light text-error v-btn--density-default v-btn--size-small v-btn--variant-outlined">Refuser le projet</summary>
        <form method="POST" action="{{ route('admin.upcycling.projets.rejeter', $projet) }}" class="tcu-rejection-form">
          @csrf
          <input type="hidden" name="moderation_projet_id" value="{{ $projet->id }}">
          <label class="tcu-label" for="commentaire-refus-{{ $projet->id }}">Commentaire de refus (obligatoire)</label>
          <p class="tcu-help" id="aide-refus-{{ $projet->id }}">Expliquez les corrections attendues. Ce commentaire sera visible par l’auteur.</p>
          <textarea id="commentaire-refus-{{ $projet->id }}" name="commentaire_refus" required maxlength="2000" class="tcu-input" rows="3"
            aria-describedby="aide-refus-{{ $projet->id }}" aria-invalid="{{ $refusActif && $errors->has('commentaire_refus') ? 'true' : 'false' }}">{{ $refusActif ? old('commentaire_refus') : '' }}</textarea>
          @if ($refusActif)
            @error('commentaire_refus') <div class="tcu-error" role="alert">{{ $message }}</div> @enderror
          @endif
          <button type="submit" class="v-btn v-theme--light text-error v-btn--density-default v-btn--size-small v-btn--variant-outlined mt-2">Confirmer le refus</button>
        </form>
      </details>
    </div>
  @endcan
@endif
