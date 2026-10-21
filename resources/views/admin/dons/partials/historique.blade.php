{{-- Timeline of a donation's statuts (Sneat back office). Paramètre : don (historiques.user chargés) --}}
@php($chip = fn ($statut) => ['danger' => 'error'][$statut->color()] ?? $statut->color())
<ol class="tcd-timeline">
  @forelse ($don->historiques as $etape)
    <li>
      <span class="tcd-timeline-dot text-{{ $chip($etape->nouveau_statut) }}"><i class="{{ $etape->nouveau_statut->icone() }} v-icon notranslate v-theme--light" aria-hidden="true"></i></span>
      <div>
        @if ($etape->estCreation())
          Don créé au statut <strong>{{ $etape->nouveau_statut->label() }}</strong>
        @else
          {{ $etape->ancien_statut->label() }} → <strong>{{ $etape->nouveau_statut->label() }}</strong>
        @endif
      </div>
      <div class="tcd-timeline-meta">
        {{ $etape->created_at->format('d/m/Y à H:i') }} · {{ $etape->user?->name ?? 'Système' }}
      </div>
      @if ($etape->commentaire)
        <div class="tcd-timeline-comment">{{ $etape->commentaire }}</div>
      @endif
    </li>
  @empty
    <li class="text-medium-emphasis">Aucun historique.</li>
  @endforelse
</ol>
