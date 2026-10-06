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
    .tc-thumb { width: 48px; height: 48px; object-fit: cover; border-radius: 6px; }
    .tc-photo { width: 100%; max-height: 420px; object-fit: cover; border-radius: 6px; }
    .v-table table { width: 100%; }
    .v-table td, .v-table th { white-space: nowrap; }
    .tc-pagination { display: flex; flex-wrap: wrap; gap: .25rem; }
    form.d-inline { display: inline; }
  </style>
@endpush
