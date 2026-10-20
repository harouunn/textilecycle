{{-- Icônes SVG du module (trait fin, couleur héritée). Usage : <x-ui.icone nom="calendrier" /> --}}
@props(['nom'])
@php
  $traces = [
      'recherche' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
      'filtre' => '<path d="M3 5h18M6 12h12M10 19h4"/>',
      'calendrier' => '<rect x="3" y="5" width="18" height="16" rx="1"/><path d="M3 10h18M8 3v4M16 3v4"/>',
      'horloge' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
      'valide' => '<path d="m5 12 4.5 4.5L19 7"/>',
      'fermer' => '<path d="M6 6l12 12M18 6 6 18"/>',
      'etiquette' => '<path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.5" cy="8.5" r="1.5"/>',
      'colis' => '<path d="m3 7 9-4 9 4-9 4zM3 7v10l9 4 9-4V7M12 11v10"/>',
      'cintre' => '<path d="M12 6a2 2 0 1 1 2 2c-1 0-2 .8-2 2v1M12 11 3 17.5h18z"/>',
      'photo' => '<rect x="3" y="5" width="18" height="15" rx="1"/><circle cx="12" cy="12.5" r="3.5"/><path d="M8 5l1.5-2h5L16 5"/>',
      'etincelle' => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5 18 18M6 18l2.5-2.5M15.5 8.5 18 6"/>',
      'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
      'alerte' => '<path d="M12 3 2 20h20zM12 10v4M12 17h.01"/>',
      'fleche' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
      'retour' => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
      'plus' => '<path d="M12 5v14M5 12h14"/>',
      'crayon' => '<path d="M4 20h4L19 9l-4-4L4 16zM14 6l4 4"/>',
      'corbeille' => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
      'cadenas' => '<rect x="5" y="11" width="14" height="10" rx="1"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
      'oeil' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
      'oeil-barre' => '<path d="M3 3l18 18M10.6 6.1A10 10 0 0 1 12 6c6.5 0 10 6 10 6a17 17 0 0 1-3.2 3.9M6.6 6.6A17 17 0 0 0 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.6M9.9 9.9a3 3 0 0 0 4.2 4.2"/>',
      'bouclier' => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6z"/><path d="m9 12 2 2 4-4"/>',
      'cle' => '<circle cx="8" cy="15" r="4"/><path d="m11 12 9-9M17 6l3 3M15 8l2 2"/>',
      'qr' => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3h-3zM20 14v.01M14 20h.01M17 20h4v-3"/>',
      'utilisateur' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
      'enveloppe' => '<rect x="3" y="5" width="18" height="14" rx="1"/><path d="m3 7 9 6 9-6"/>',
      'feuille' => '<path d="M5 19c0-9 6-14 15-15-1 9-6 15-15 15zM5 19l7-7"/>',
      'actualiser' => '<path d="M20 11a8 8 0 0 0-14.9-3.9M4 4v4h4M4 13a8 8 0 0 0 14.9 3.9M20 20v-4h-4"/>',
      'telephone' =>'<path d="M5 3h4l2 5-2.5 1.5a11 11 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 5a2 2 0 0 1 2-2"/>',
  ];
@endphp
<svg {{ $attributes->merge(['class' => 'tc-icone']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">{!! $traces[$nom] ?? $traces['info'] !!}</svg>
