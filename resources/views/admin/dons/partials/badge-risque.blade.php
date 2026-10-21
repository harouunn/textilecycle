{{-- Badge rouge « À vérifier » d'un don au score de risque élevé. Paramètre : don --}}
<span class="v-chip v-theme--light text-error v-chip--density-default v-chip--size-x-small v-chip--variant-flat bg-error ms-1"
  title="Score de risque {{ $don->score_risque }}/100{{ ($explication = data_get($don->ia_analyse, 'risque.explication')) ? ' — '.$explication : '' }}">
  <span class="v-chip__underlay"></span>
  <div class="v-chip__content text-white">À vérifier</div>
</span>
