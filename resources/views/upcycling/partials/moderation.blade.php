@include('upcycling.partials.motif-refus')
@if ($errors->any())
  <div class="alert alert-danger" role="alert">@foreach ($errors->all() as $message)<p>{{ $message }}</p>@endforeach</div>
@endif
@can('submit', $projet)
  <form method="POST" action="{{ route('upcycling.projets.soumettre', $projet) }}" class="my-3">
    @csrf <button class="btn btn-dark" type="submit">{{ $projet->statut === \App\Enums\Upcycling\StatutProjet::Rejete ? 'Resoumettre à validation' : 'Soumettre à validation' }}</button>
    <p>Vérifiez le projet, les étapes et leurs photos avant de le soumettre.</p>
  </form>
@endcan
