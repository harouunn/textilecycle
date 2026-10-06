@push('styles')
  <style>
    .tc-vetement-image { width: 100%; aspect-ratio: 3 / 4; object-fit: cover; }
    .tc-vetement-photo { width: 100%; max-height: 640px; object-fit: cover; }
    .tc-filter-list a { display: flex; justify-content: space-between; padding: .4rem 0; color: inherit; text-decoration: none; }
    .tc-filter-list a.active, .tc-filter-list a:hover { color: #3d7a4f; font-weight: 500; }
    .tc-thumb { width: 56px; height: 56px; object-fit: cover; }
    .tc-depot-form .form-control, .tc-depot-form .form-select { border-radius: 0; }
    .tc-depot-form .form-label { text-transform: uppercase; font-size: .85rem; letter-spacing: .05em; }
  </style>
@endpush
