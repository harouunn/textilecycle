{{-- Module « Associations & dons » : entrée du menu front office (à modifier uniquement par l'équipe du module) --}}
<li><a href="{{ route('dons.index') }}" class="dropdown-item item-anchor">Associations &amp; dons</a></li>
<li><a href="{{ route('dons.campagnes.index') }}" class="dropdown-item item-anchor">Campagnes de collecte</a></li>
@auth
  @php($donsNonLues = auth()->user()->unreadNotifications()->whereIn('type', \App\Http\Controllers\Dons\NotificationController::TYPES)->count())
  <li><a href="{{ route('dons.mes-dons') }}" class="dropdown-item item-anchor">Mes dons</a></li>
  <li><a href="{{ route('dons.mon-impact') }}" class="dropdown-item item-anchor">Mon impact</a></li>
  <li>
    <a href="{{ route('dons.notifications.index') }}" class="dropdown-item item-anchor d-flex align-items-center gap-2"
      aria-label="Notifications des dons{{ $donsNonLues ? ' : '.$donsNonLues.' non lue(s)' : '' }}">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
      </svg>
      Notifications
      @if ($donsNonLues)
        <span class="badge rounded-pill text-bg-danger">{{ $donsNonLues > 99 ? '99+' : $donsNonLues }}</span>
      @endif
    </a>
  </li>
@endauth
