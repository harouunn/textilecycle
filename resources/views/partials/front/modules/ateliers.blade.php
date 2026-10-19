{{-- Module « Ateliers & réparations » : entrées du menu front office (à modifier uniquement par l'équipe du module) --}}
<li><a href="{{ route('ateliers.index') }}" class="dropdown-item item-anchor">Ateliers &amp; réparations</a></li>
@auth
  <li><a href="{{ route('ateliers.demandes.index') }}" class="dropdown-item item-anchor">Mes demandes de réparation</a></li>
@endauth
