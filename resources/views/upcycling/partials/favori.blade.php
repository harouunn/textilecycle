@if ($projet->estPublie())
  <div class="d-flex flex-wrap gap-2 mt-3">
    @auth
      @php($estFavori = auth()->user()->projetsFavoris()->whereKey($projet->id)->exists())
      <form method="POST" action="{{ route($estFavori ? 'upcycling.favoris.destroy' : 'upcycling.favoris.store', $projet) }}">
        @csrf
        @if ($estFavori) @method('DELETE') @endif
        <button type="submit" class="btn btn-outline-dark">{{ $estFavori ? 'Retirer des favoris' : 'Ajouter aux favoris' }}</button>
      </form>
      <a href="{{ route('upcycling.mes-favoris') }}" class="btn btn-outline-dark">Mes favoris</a>
    @else
      <a href="{{ route('login') }}" class="btn btn-outline-dark">Se connecter pour ajouter aux favoris</a>
    @endauth
  </div>
@endif
