@extends('layouts.front')

@section('title', 'Classement des donateurs')

@section('content')
<div class="tc-page">
  <x-ui.hero surtitre="Associations & dons" titre="Classement des donateurs"
    sous-titre="Les 10 donateurs qui ont fait parvenir le plus de textiles aux associations."
    :fil="['Dons' => route('dons.index'), 'Classement' => null]">
    @auth
      <a href="{{ route('dons.mon-impact') }}" class="tc-btn">Mon impact</a>
    @endauth
  </x-ui.hero>
</div>

<section class="py-5">
  <div class="container" style="max-width: 720px">
    @if (empty($classement))
      <p class="text-center text-muted py-5">Le classement apparaîtra dès qu'un premier don aura été reçu par une association.</p>
    @else
      <ol class="list-group list-group-numbered">
        @foreach ($classement as $ligne)
          <li class="list-group-item d-flex justify-content-between align-items-center py-3">
            <span class="ms-2 me-auto fw-semibold">
              {{ $ligne['prenom'] }}
              @if ($ligne['rang'] <= 3) <span aria-hidden="true">{{ ['🥇', '🥈', '🥉'][$ligne['rang'] - 1] }}</span> @endif
            </span>
            <span class="badge text-bg-dark rounded-pill">{{ number_format($ligne['points'], 0, ',', ' ') }} pts</span>
          </li>
        @endforeach
      </ol>
      <p class="small text-muted mt-3">Seul le prénom des donateurs est affiché. Classement mis à jour toutes les 10 minutes.</p>
    @endif
  </div>
</section>
@include('dons.partials.assistant')
@endsection
