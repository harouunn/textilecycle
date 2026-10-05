{{-- Statut chip of a donation. Paramètre : don --}}
@php($chipColor = ['danger' => 'error'][$don->statutColor()] ?? $don->statutColor())
<span class="v-chip v-theme--light text-{{ $chipColor }} v-chip--density-default v-chip--size-small v-chip--variant-tonal">
  <span class="v-chip__underlay"></span>
  <div class="v-chip__content">{{ $don->statutLabel() }}</div>
</span>
