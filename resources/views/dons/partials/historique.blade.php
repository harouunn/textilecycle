{{-- Timeline of a donation's statuts (front office, Bootstrap). Paramètres : don (historiques.user chargés), afficherAuteurs (bool) --}}
<ol class="list-unstyled mb-0">
  @forelse ($don->historiques as $etape)
    <li class="d-flex gap-3 {{ $loop->last ? '' : 'pb-3 mb-3 border-bottom' }}">
      <span class="badge rounded-pill text-bg-{{ $etape->nouveau_statut->color() }} align-self-start mt-1">{{ $etape->nouveau_statut->label() }}</span>
      <div>
        <div>
          @if ($etape->estCreation())
            Don proposé
          @else
            {{ $etape->ancien_statut->label() }} → <strong>{{ $etape->nouveau_statut->label() }}</strong>
          @endif
        </div>
        <div class="small text-muted">
          {{ $etape->created_at->format('d/m/Y à H:i') }}
          @if ($afficherAuteurs ?? false)
            · {{ $etape->user?->name ?? 'Système' }}
          @endif
        </div>
        @if ($etape->commentaire)
          <div class="mt-1" style="white-space: pre-line">{{ $etape->commentaire }}</div>
        @endif
      </div>
    </li>
  @empty
    <li class="text-muted">Aucun historique.</li>
  @endforelse
</ol>
