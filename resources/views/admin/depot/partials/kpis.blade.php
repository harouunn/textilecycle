{{--
  Rangée d'indicateurs cliquables (style Sneat).
  Paramètre : $kpis = liste de [libellé, valeur, icône boxicons, couleur, URL ou null, actif (bool)]
--}}
<div class="v-row mb-2">
  @foreach ($kpis as [$libelle, $valeur, $icone, $couleur, $url, $actif])
    <div class="v-col-lg v-col-sm-4 v-col-6">
      <{!! $url ? 'a href="'.e($url).'"' : 'div' !!} @class(['v-card v-theme--light v-card--density-default v-card--variant-elevated d-block h-100 tc-kpi', 'tc-kpi--actif' => $actif])>
        <div class="v-card-text d-flex align-center gap-3">
          <div class="v-avatar v-theme--light text-{{ $couleur }} v-avatar--density-default v-avatar--variant-tonal rounded flex-shrink-0" style="width: 40px; height: 40px;">
            <i class="{{ $icone }} v-icon notranslate v-theme--light" aria-hidden="true" style="font-size: 22px; height: 22px; width: 22px;"></i>
            <span class="v-avatar__underlay"></span>
          </div>
          <div class="overflow-hidden">
            <div class="text-h5 mb-0 tc-num">{{ $valeur }}</div>
            <div class="text-body-2 text-medium-emphasis text-truncate">{{ $libelle }}</div>
          </div>
        </div>
        <span class="v-card__underlay"></span>
      </{!! $url ? 'a' : 'div' !!}>
    </div>
  @endforeach
</div>
