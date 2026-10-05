{{-- Module « Associations & dons » : entrée du menu front office (à modifier uniquement par l'équipe du module) --}}
<li><a href="{{ route('dons.index') }}" class="dropdown-item item-anchor">Associations &amp; dons</a></li>
@auth
  <li><a href="{{ route('dons.mes-dons') }}" class="dropdown-item item-anchor">Mes dons</a></li>
@endauth
