{{-- Module « Dépôt & vêtements » : entrées du menu front office (à modifier uniquement par l'équipe du module) --}}
<li><a href="{{ route('depot.catalogue.index') }}" class="dropdown-item item-anchor">Catalogue des vêtements</a></li>
<li><a href="{{ route('depot.mes-depots.create') }}" class="dropdown-item item-anchor">Déposer un vêtement</a></li>
@auth
  <li><a href="{{ route('depot.mes-depots.index') }}" class="dropdown-item item-anchor">Mes dépôts</a></li>
@endauth
