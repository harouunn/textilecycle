{{-- Module « Associations & dons » : styles for native form controls in the Sneat back office --}}
@once
@push('styles')
<style>
  .tcd-field { margin-bottom: 1.25rem; }
  .tcd-label { display: block; margin-bottom: .25rem; font-size: .8125rem; color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity)); }
  .tcd-label .tcd-required { color: rgb(var(--v-theme-error)); }
  .tcd-input {
    display: block; width: 100%; padding: .5rem .875rem; font: inherit; font-size: .9375rem;
    color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity));
    background: rgb(var(--v-theme-surface)); border: 1px solid rgba(var(--v-border-color), .22); border-radius: 6px;
    transition: border-color .15s, box-shadow .15s;
  }
  .tcd-input:focus { outline: none; border-color: rgb(var(--v-theme-primary)); box-shadow: 0 0 0 2px rgba(var(--v-theme-primary), .16); }
  .tcd-input.is-invalid { border-color: rgb(var(--v-theme-error)); }
  select.tcd-input { appearance: auto; }
  textarea.tcd-input { min-height: 96px; resize: vertical; }
  .tcd-error { margin-top: .25rem; font-size: .8125rem; color: rgb(var(--v-theme-error)); }
  .tcd-hint { margin-top: .25rem; font-size: .75rem; color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity)); }
  .tcd-check { display: inline-flex; align-items: center; gap: .5rem; cursor: pointer; }
  .tcd-check input { width: 18px; height: 18px; accent-color: rgb(var(--v-theme-primary)); }
  .tcd-filters { display: flex; flex-wrap: wrap; gap: .75rem; align-items: flex-end; }
  .tcd-filters > * { flex: 1 1 170px; }
  .tcd-filters > .tcd-filters-actions { flex: 0 0 auto; display: flex; gap: .5rem; }
  .tcd-table th, .tcd-table td.tcd-nowrap { white-space: nowrap; }
  .tcd-table td { vertical-align: middle; }
  .tcd-logo { width: 38px; height: 38px; border-radius: 6px; object-fit: cover; background: rgba(var(--v-theme-primary), .12); display: inline-flex; align-items: center; justify-content: center; color: rgb(var(--v-theme-primary)); font-weight: 600; }
  .tcd-logo-lg { width: 96px; height: 96px; font-size: 2rem; }
  .tcd-statut-form select { padding: .25rem .5rem; font-size: .8125rem; width: auto; display: inline-block; }
  .tcd-dl { display: grid; grid-template-columns: max-content 1fr; gap: .5rem 1.5rem; margin: 0; }
  .tcd-dl dt { font-weight: 500; color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity)); }
  .tcd-dl dd { margin: 0; }
  .tcd-pagination { display: flex; flex-wrap: wrap; gap: .25rem; list-style: none; padding: 0; margin: 0; }
  .tcd-pagination a, .tcd-pagination span {
    display: inline-flex; min-width: 34px; height: 34px; padding: 0 .5rem; align-items: center; justify-content: center;
    border-radius: 6px; text-decoration: none; font-size: .875rem;
    color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity)); background: rgba(var(--v-theme-on-surface), .06);
  }
  .tcd-pagination a:hover { background: rgba(var(--v-theme-primary), .16); color: rgb(var(--v-theme-primary)); }
  .tcd-pagination .active span { background: rgb(var(--v-theme-primary)); color: #fff; }
  .tcd-pagination .disabled span { opacity: .45; }
  .tcd-pre { white-space: pre-line; }
  .tcd-inline { display: inline; }
</style>
@endpush
@endonce
