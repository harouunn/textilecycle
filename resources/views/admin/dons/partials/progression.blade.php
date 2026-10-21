{{-- Jauge d'une campagne (back office). Paramètre : campagne (kg_collectes chargé ou calculé) --}}
@php($pourcentage = $campagne->progression())
<div class="d-flex justify-space-between text-body-2 mb-1">
  <span>{{ number_format($campagne->kgCollectes(), 1, ',', ' ') }} / {{ number_format($campagne->objectif_kg, 0, ',', ' ') }} kg</span>
  <span>{{ number_format($pourcentage, 0, ',', ' ') }} %</span>
</div>
<div class="tcd-progress" role="progressbar" aria-valuenow="{{ min(100, $pourcentage) }}" aria-valuemin="0" aria-valuemax="100" aria-label="Progression de la campagne">
  <div class="tcd-progress-bar {{ $pourcentage >= 100 ? 'bg-success' : 'bg-primary' }}" style="width: {{ min(100, $pourcentage) }}%"></div>
</div>
