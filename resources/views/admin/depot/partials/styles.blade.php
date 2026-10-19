{{-- Styles des formulaires du module (les champs Vuetify n'existent pas en HTML statique) --}}
@push('styles')
  <style>
    .tc-label { display: block; margin-bottom: .25rem; font-size: .8125rem; color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity)); }
    .tc-control { width: 100%; padding: .5rem .875rem; border: 1px solid rgba(var(--v-theme-on-surface), .22); border-radius: 6px; background: rgb(var(--v-theme-surface)); color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity)); font: inherit; font-size: .9375rem; line-height: 1.5rem; outline: none; transition: border-color .15s, box-shadow .15s; }
    .tc-control:focus { border-color: rgb(var(--v-theme-primary)); box-shadow: 0 0 0 1px rgb(var(--v-theme-primary)); }
    .tc-control.is-invalid { border-color: rgb(var(--v-theme-error)); }
    select.tc-control { appearance: auto; min-height: 42px; }
    textarea.tc-control { min-height: 110px; resize: vertical; }
    .tc-error { margin-top: .25rem; color: rgb(var(--v-theme-error)); font-size: .8125rem; }
    .tc-alert { display: flex; align-items: center; gap: .5rem; padding: .75rem 1rem; border-radius: 6px; background: rgba(var(--v-theme-success), .16); color: rgb(var(--v-theme-success)); }
    .tc-alert--error { background: rgba(var(--v-theme-error), .16); color: rgb(var(--v-theme-error)); }
    .tc-alert--info { align-items: flex-start; background: rgba(var(--v-theme-info), .16); color: rgb(var(--v-theme-info)); }
    .tc-thumb { width: 48px; height: 48px; object-fit: cover; border-radius: 6px; }
    .tc-photo { width: 100%; max-height: 420px; object-fit: cover; border-radius: 6px; }
    .v-table table { width: 100%; }
    .v-table td, .v-table th { white-space: nowrap; }
    .tc-pagination { display: flex; flex-wrap: wrap; gap: .25rem; }
    form.d-inline { display: inline; }

    /* Indicateurs, en-têtes de page et détails */
    .tc-num { font-variant-numeric: tabular-nums; }
    .tc-kpi { text-decoration: none; transition: transform .15s, box-shadow .15s; }
    a.tc-kpi:hover { transform: translateY(-2px); }
    .tc-kpi--actif { box-shadow: 0 0 0 2px rgb(var(--v-theme-primary)) !important; }
    .tc-page-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 1rem; margin-bottom: 1.5rem; }
    .tc-page-head h4 { margin: 0; }
    .tc-page-head p { margin: .25rem 0 0; color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity)); }
    .tc-swatch { display: inline-block; width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .1); }
    .tc-dl { display: grid; grid-template-columns: minmax(120px, 38%) 1fr; gap: .65rem 1rem; margin: 0; }
    .tc-dl dt { color: rgba(var(--v-theme-on-surface), var(--v-medium-emphasis-opacity)); font-weight: 400; }
    .tc-dl dd { margin: 0; color: rgba(var(--v-theme-on-surface), var(--v-high-emphasis-opacity)); }
    .tc-timeline { list-style: none; margin: 0; padding: 0; }
    .tc-timeline li { position: relative; padding: 0 0 1.1rem 1.6rem; }
    .tc-timeline li::before { content: ""; position: absolute; left: 4px; top: 6px; width: 10px; height: 10px; border-radius: 50%; background: rgba(var(--v-theme-on-surface), .2); }
    .tc-timeline li::after { content: ""; position: absolute; left: 8px; top: 20px; bottom: 0; width: 2px; background: rgba(var(--v-theme-on-surface), .08); }
    .tc-timeline li:last-child::after { display: none; }
    .tc-timeline li.fait::before { background: rgb(var(--v-theme-success)); }
    .tc-timeline li.annule::before { background: rgb(var(--v-theme-error)); }
    .tc-switch { display: inline-flex; align-items: center; gap: .6rem; cursor: pointer; }
    .tc-switch input { width: 18px; height: 18px; accent-color: rgb(var(--v-theme-primary)); }
    input[type=color].tc-control { padding: .25rem; height: 42px; }
  </style>
@endpush
