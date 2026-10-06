{{-- Styles du module Upcycling (front office) --}}
@once
@push('styles')
<style>
  .tcu-card { background: #fff; border: 1px solid rgba(0, 0, 0, .08); transition: box-shadow .2s; }
  .tcu-card:hover { box-shadow: 0 .5rem 1.5rem rgba(0, 0, 0, .08); }
  .tcu-cover { display: block; width: 100%; aspect-ratio: 4 / 3; object-fit: cover; }
  .tcu-cover-vide { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .25rem; width: 100%; aspect-ratio: 4 / 3; background: #f1f1f0; color: #8f8f8f; text-align: center; font-size: .9rem; }
  .tcu-filtres .btn { border-radius: 0; text-transform: uppercase; font-size: .8rem; letter-spacing: .05em; }
  .tcu-meta { font-size: .85rem; color: #6c757d; }
  .tcu-pre { white-space: pre-line; }
  .tcu-etape-num { flex: 0 0 auto; display: flex; align-items: center; justify-content: center; width: 2.75rem; height: 2.75rem; background: #3d7a4f; color: #fff; font-family: "Marcellus", serif; font-size: 1.25rem; }
  .tcu-etape-photo { max-width: 100%; max-height: 320px; object-fit: cover; }
  .tcu-form .form-control, .tcu-form .form-select { border-radius: 0; }
  .tcu-form .form-label { text-transform: uppercase; font-size: .8rem; letter-spacing: .05em; }
  .tcu-thumb { width: 64px; height: 64px; object-fit: cover; }
  .tcu-photo-label { position: absolute; top: .75rem; left: .75rem; padding: .25rem .75rem; background: rgba(17, 17, 17, .8); color: #fff; font-size: .75rem; text-transform: uppercase; letter-spacing: .08em; }
</style>
@endpush
@endonce
