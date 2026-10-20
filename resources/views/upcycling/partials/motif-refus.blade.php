@if ($projet->commentaire_refus && (auth()->id() === $projet->user_id || auth()->user()?->isAdmin()))
  <div class="alert alert-warning" role="status"><strong>Motif du refus :</strong> {{ $projet->commentaire_refus }}</div>
@endif
