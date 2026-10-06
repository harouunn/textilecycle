{{-- Styles du module Upcycling (back office) : champs de formulaire simples aux couleurs du thème Sneat --}}
@once
@push('styles')
<style>
  .tcu-label { display: block; margin-bottom: .375rem; font-size: .8125rem; font-weight: 500; color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity)); }
  .tcu-label .text-error { margin-inline-start: 2px; }
  .tcu-input { display: block; width: 100%; padding: .5rem .875rem; font: inherit; font-size: .9375rem; line-height: 1.5; color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity)); background: rgb(var(--v-theme-surface)); border: 1px solid rgba(var(--v-theme-on-surface), .22); border-radius: 6px; transition: border-color .15s, box-shadow .15s; }
  .tcu-input:focus { outline: none; border-color: rgb(var(--v-theme-primary)); box-shadow: 0 0 0 3px rgba(var(--v-theme-primary), .16); }
  .tcu-input.is-invalid { border-color: rgb(var(--v-theme-error)); }
  .tcu-input[type=file] { padding: .375rem .5rem; }
  select.tcu-input { appearance: auto; }
  textarea.tcu-input { min-height: 110px; resize: vertical; }
  .tcu-error { margin-top: .25rem; font-size: .8125rem; color: rgb(var(--v-theme-error)); }
  .tcu-help { margin-top: .25rem; font-size: .8125rem; color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity)); }
  .tcu-thumb { width: 56px; height: 56px; object-fit: cover; border-radius: 6px; }
  .tcu-photo { display: block; width: 100%; aspect-ratio: 4 / 3; object-fit: cover; border-radius: 6px; }
  .tcu-photo-vide { display: flex; align-items: center; justify-content: center; width: 100%; aspect-ratio: 4 / 3; border: 1px dashed rgba(var(--v-theme-on-surface), .22); border-radius: 6px; color: rgba(var(--v-theme-on-surface), var(--v-disabled-opacity)); }
  .tcu-pre { white-space: pre-line; }
  .tcu-etape + .tcu-etape { border-top: 1px solid rgba(var(--v-theme-on-surface), .12); }
  .tcu-inline-form { display: inline; }
</style>
@endpush
@endonce
