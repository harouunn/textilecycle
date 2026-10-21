@extends('layouts.front')

@section('title', 'Notifications')

@section('content')
<div class="tc-page">
  <x-ui.hero surtitre="Mon espace" titre="Notifications" sous-titre="Les nouvelles de vos dons et des dons reçus par votre association."
    :fil="['Dons' => route('dons.index'), 'Notifications' => null]">
    @if ($notifications->contains(fn ($n) => $n->unread()))
      <form method="POST" action="{{ route('dons.notifications.tout-lire') }}">
        @csrf
        <button type="submit" class="tc-btn">Tout marquer comme lu</button>
      </form>
    @endif
  </x-ui.hero>
</div>

<section class="py-5">
  <div class="container" style="max-width: 760px">
    @include('dons.partials.flash')

    @if ($notifications->isEmpty())
      <p class="text-center text-muted py-5">Aucune notification pour le moment.</p>
    @else
      <div class="list-group">
        @foreach ($notifications as $notification)
          <a href="{{ route('dons.notifications.ouvrir', $notification->id) }}" @class(['list-group-item list-group-item-action py-3', 'fw-semibold' => $notification->unread()])>
            <div class="d-flex justify-content-between gap-3">
              <span>
                @if ($notification->unread()) <span class="badge rounded-pill text-bg-danger me-1">Nouveau</span> @endif
                {{ $notification->data['message'] ?? 'Mise à jour d\'un don' }}
              </span>
              <small class="text-muted text-nowrap">{{ $notification->created_at->locale('fr')->diffForHumans() }}</small>
            </div>
          </a>
        @endforeach
      </div>

      <div class="d-flex justify-content-center mt-4">
        {{ $notifications->links('dons.partials.pagination') }}
      </div>
    @endif
  </div>
</section>
@endsection
