@push('styles')
  <style>
    .tc-atelier-card { height: 100%; border: 1px solid rgba(0, 0, 0, .1); padding: 1.5rem; display: flex; flex-direction: column; }
    .tc-atelier-card p { flex-grow: 1; }
    .tc-filter-list a { display: flex; justify-content: space-between; padding: .4rem 0; color: inherit; text-decoration: none; }
    .tc-filter-list a.active, .tc-filter-list a:hover { color: #3d7a4f; font-weight: 500; }
    .tc-thumb { width: 56px; height: 56px; object-fit: cover; }
    .tc-atelier-form .form-control, .tc-atelier-form .form-select { border-radius: 0; }
    .tc-atelier-form .form-label { text-transform: uppercase; font-size: .85rem; letter-spacing: .05em; }
    .tc-diagnostic { border-left: 3px solid #3d7a4f; background: #f6f8f5; padding: 1rem 1.25rem; }
    .tc-diagnostic-chiffre { font-size: 1.35rem; font-weight: 500; }
  </style>
@endpush
