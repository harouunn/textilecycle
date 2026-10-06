{{-- Badge. Paramètres : enum (Difficulte ou StatutProjet) --}}
@php($couleur = $enum->color() === 'danger' ? 'error' : $enum->color())
<span class="v-chip v-theme--light text-{{ $couleur }} v-chip--density-default v-chip--size-small v-chip--variant-tonal">
  <span class="v-chip__underlay"></span>
  <div class="v-chip__content">{{ $enum->label() }}</div>
</span>
