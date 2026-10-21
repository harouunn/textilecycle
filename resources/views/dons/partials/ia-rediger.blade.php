{{--
  IA 4 — boutons « Générer avec l'IA » (front office et back office).
  Un bouton [data-ia-rediger] envoie à dons.ia.rediger :
    data-type   : description | campagne | rapport
    data-cible  : id du textarea qui reçoit le texte (rien n'est enregistré sans que l'utilisateur valide le formulaire)
    data-champs : JSON { paramètre: sélecteur CSS } lus dans la page au moment du clic (cases cochées → tableau)
    data-fixes  : JSON { paramètre: valeur }
  Le message (succès, modèle sans IA, erreur) s'affiche dans l'élément [data-ia-message="<id du textarea>"].
--}}
@include('dons.partials.ia-styles')
@once
@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const url = @js(route('dons.ia.rediger'));

    function lire(selecteur) {
      const elements = Array.from(document.querySelectorAll(selecteur));
      if (elements.length && elements.every((el) => el.type === 'checkbox')) {
        return elements.filter((el) => el.checked).map((el) => el.value);
      }
      return elements.length ? elements[0].value : null;
    }

    document.querySelectorAll('[data-ia-rediger]').forEach(function (bouton) {
      bouton.addEventListener('click', async function () {
        const cible = document.getElementById(bouton.dataset.cible);
        const message = document.querySelector('[data-ia-message="' + bouton.dataset.cible + '"]');
        const afficher = (texte, classe) => { if (message) { message.className = 'small mt-1 ' + classe; message.textContent = texte; } };

        if (cible.value.trim() && !confirm('Remplacer le texte actuel par une proposition ? (vous pourrez encore la modifier)')) return;

        const corps = { type: bouton.dataset.type, ...JSON.parse(bouton.dataset.fixes || '{}') };
        Object.entries(JSON.parse(bouton.dataset.champs || '{}')).forEach(([parametre, selecteur]) => {
          const valeur = lire(selecteur);
          if (valeur !== null && valeur !== '') corps[parametre] = valeur;
        });

        bouton.disabled = true;
        bouton.setAttribute('aria-busy', 'true');
        if (message) {
          message.className = 'small mt-1 text-muted';
          window.tcdChargement(message, 'Rédaction en cours…');
        }
        try {
          const reponse = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify(corps),
          });
          const r = await reponse.json().catch(() => ({}));
          if (reponse.status === 422) throw new Error(Object.values(r.errors || {}).flat()[0] || 'Données invalides.');
          if (reponse.status === 429) throw new Error('Trop de demandes à l\'IA : réessayez dans une minute.');
          if (!reponse.ok) throw new Error('Rédaction indisponible pour le moment.');
          if (r.ok && r.texte) {
            cible.value = r.texte;
            cible.dispatchEvent(new Event('input'));
            cible.focus();
          }
          afficher(r.message, r.ia ? 'text-success' : 'text-muted');
        } catch (e) {
          afficher(e.message, 'text-danger');
        } finally {
          bouton.disabled = false;
          bouton.removeAttribute('aria-busy');
        }
      });
    });
  });
</script>
@endpush
@endonce
