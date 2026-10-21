{{--
  Boutons des transitions autorisées pour ce don et ce compte (statut actuel + DonPolicy).
  Paramètres : don, commentaire (bool, affiche un champ commentaire), taille ('sm' ou '')
--}}
@php($transitions = collect($don->statut->allowedTransitions())->filter(fn ($statut) => auth()->user()->can('transition', [$don, $statut])))
@if ($transitions->isEmpty())
  <span class="small text-muted">Aucune action</span>
@else
  <form method="POST" action="{{ route('dons.espace.statut', $don) }}">
    @csrf
    @method('PATCH')
    @if ($commentaire ?? false)
      <label for="commentaire-{{ $don->id }}" class="form-label">Commentaire (visible par le donateur)</label>
      <textarea id="commentaire-{{ $don->id }}" name="commentaire" rows="3" maxlength="1000"
        @class(['form-control mb-3', 'is-invalid' => $errors->has('commentaire')]) placeholder="Ex. : rendez-vous le 12/11 à 10 h à l'entrepôt.">{{ old('commentaire') }}</textarea>
      @error('commentaire') <div class="invalid-feedback d-block mb-3">{{ $message }}</div> @enderror
    @endif
    <div class="d-flex flex-wrap gap-2 {{ ($commentaire ?? false) ? '' : 'justify-content-end' }}">
      @foreach ($transitions as $statut)
        <button type="submit" name="statut" value="{{ $statut->value }}"
          class="btn btn-outline-{{ $statut->color() }} {{ ($taille ?? 'sm') === 'sm' ? 'btn-sm' : '' }}"
          @if (in_array($statut, [\App\Enums\Dons\DonStatut::Refuse, \App\Enums\Dons\DonStatut::Annule], true)) onclick="return confirm(@js('« '.$statut->action().' » pour le don n°'.$don->id.' ?'))" @endif>
          {{ $statut->action() }}
        </button>
      @endforeach
    </div>
  </form>
@endif
