{{-- Indicateur de chargement des appels AJAX (IA), commun au site et au back office. --}}
@once
@push('styles')
<style>
  .tcd-spinner { display: inline-block; width: 1em; height: 1em; margin-right: .4em; vertical-align: -.15em;
    border: .15em solid currentColor; border-right-color: transparent; border-radius: 50%; animation: tcd-tourne .7s linear infinite; }
  @keyframes tcd-tourne { to { transform: rotate(360deg); } }
  @media (prefers-reduced-motion: reduce) { .tcd-spinner { animation-duration: 2s; } }
</style>
@endpush
@push('scripts')
<script>
  // Remplace le contenu d'un élément par « ⟳ texte » (spinner + texte), sans HTML venant de l'extérieur.
  window.tcdChargement = function (element, texte) {
    const spinner = document.createElement('span');
    spinner.className = 'tcd-spinner';
    spinner.setAttribute('aria-hidden', 'true');
    element.replaceChildren(spinner, document.createTextNode(texte));
  };
</script>
@endpush
@endonce
