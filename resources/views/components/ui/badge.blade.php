{{-- Badge de statut. Paramètres : couleur (success, warning, info, error, secondary), libellé dans le slot --}}
@props(['couleur' => 'secondary'])
<span {{ $attributes->merge(['class' => 'tc-badge tc-badge--'.$couleur]) }}>{{ $slot }}</span>
