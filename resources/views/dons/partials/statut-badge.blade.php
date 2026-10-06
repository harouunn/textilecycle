{{-- Statut badge of a donation (Bootstrap). Paramètre : don --}}
<span class="badge rounded-pill text-bg-{{ $don->statutColor() }}">{{ $don->statutLabel() }}</span>
