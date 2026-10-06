{{-- Module « Upcycling » : entrée du menu front office (à modifier uniquement par l'équipe du module) --}}
<li><a href="{{ route('upcycling.index') }}" class="dropdown-item item-anchor">Upcycling</a></li>
@auth
  <li><a href="{{ route('upcycling.mes-projets') }}" class="dropdown-item item-anchor">Mes projets upcycling</a></li>
@endauth
