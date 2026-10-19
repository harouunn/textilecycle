{{-- Résultat du diagnostic automatique (et réponse de l'atelier s'il y en a une). Paramètre : $demande --}}
<div class="tc-diagnostic">
  <div class="d-flex flex-wrap gap-2 mb-3">
    @foreach ($demande->reparations as $reparation)
      <span class="badge text-bg-light border">{{ $reparation->label() }}</span>
    @endforeach
  </div>

  <div class="row g-3 mb-3">
    <div class="col-6">
      <div class="small text-uppercase">{{ $demande->cout_final !== null ? 'Coût final' : 'Coût estimé' }}</div>
      <div class="tc-diagnostic-chiffre">{{ $demande->cout_affiche }}</div>
    </div>
    <div class="col-6">
      @if ($demande->date_prevue)
        <div class="small text-uppercase">Prêt le</div>
        <div class="tc-diagnostic-chiffre">{{ $demande->date_prevue->format('d/m/Y') }}</div>
      @else
        <div class="small text-uppercase">Délai estimé</div>
        <div class="tc-diagnostic-chiffre">{{ $demande->delai_estime_jours }} jour(s)</div>
      @endif
    </div>
  </div>

  <p class="small mb-0" style="white-space: pre-line;">{{ $demande->diagnostic }}</p>

  @if ($demande->reponse_atelier)
    <hr>
    <div class="small text-uppercase mb-1">Réponse de l'atelier</div>
    <p class="mb-0" style="white-space: pre-line;">{{ $demande->reponse_atelier }}</p>
  @endif
</div>
