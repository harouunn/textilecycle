{{-- Pastille Vuetify statique. Paramètres : label, color --}}
<span class="v-chip v-theme--light text-{{ $color ?? 'primary' }} v-chip--density-default v-chip--size-small v-chip--variant-tonal">
  <span class="v-chip__underlay"></span>
  <div class="v-chip__content">{{ $label }}</div>
</span>
