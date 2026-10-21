{{-- Statut chip of a campaign. Paramètre : statut (StatutCampagne) --}}
<span class="v-chip v-theme--light text-{{ $statut->color() }} v-chip--density-default v-chip--size-small v-chip--variant-tonal">
  <span class="v-chip__underlay"></span>
  <div class="v-chip__content">{{ $statut->label() }}</div>
</span>
