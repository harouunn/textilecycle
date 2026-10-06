{{-- Flash messages for the front office (Bootstrap) --}}
@foreach (['success' => 'success', 'error' => 'danger'] as $key => $color)
  @if (session($key))
    <div class="alert alert-{{ $color }} alert-dismissible fade show" role="alert">
      {{ session($key) }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
  @endif
@endforeach
