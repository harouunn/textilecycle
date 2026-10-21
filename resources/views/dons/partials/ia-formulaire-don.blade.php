{{--
  Formulaire « Faire un don » : IA 1 (analyse photo → pré-remplissage) et IA 2 (associations recommandées).
  Les réponses du serveur sont insérées avec textContent : aucun HTML venant de l'IA n'est interprété.
--}}
@include('dons.partials.ia-styles')
@once
@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const formulaire = document.getElementById('formulaire-don');
    if (!formulaire) return;
    const champ = (nom) => formulaire.elements.namedItem(nom);

    async function appeler(url, corps) {
      const reponse = await fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', ...(corps instanceof FormData ? {} : { 'Content-Type': 'application/json' }) },
        body: corps instanceof FormData ? corps : JSON.stringify(corps),
      });
      const donnees = await reponse.json().catch(() => ({}));
      if (reponse.status === 429) throw new Error('Trop de demandes à l\'IA : réessayez dans une minute.');
      if (reponse.status === 422) throw new Error(Object.values(donnees.errors || {}).flat()[0] || 'Données invalides.');
      if (!reponse.ok) throw new Error('Service indisponible pour le moment.');
      return donnees;
    }

    // IA 1 — analyse photo
    const photo = document.getElementById('photo-ia');
    const statut = document.getElementById('ia-photo-statut');
    const jeton = document.getElementById('ia_jeton');
    const envoyer = document.getElementById('envoyer-don');

    function afficherStatut(texte, classe) {
      statut.className = 'small mt-2 ' + classe;
      statut.textContent = texte;
    }
    function badge(nom, visible) {
      const b = formulaire.querySelector('[data-ia-badge="' + nom + '"]');
      if (b) b.hidden = !visible;
    }
    function bloquer(oui) {
      envoyer.disabled = oui;
      envoyer.title = oui ? 'Choisissez une photo de textiles ou retirez la photo.' : '';
    }

    photo.addEventListener('change', async function () {
      jeton.value = '';
      bloquer(false);
      ['type_article', 'etat_general', 'quantite', 'poids_kg', 'message'].forEach((n) => badge(n, false));
      if (!photo.files.length) { afficherStatut('', ''); return; }

      const donnees = new FormData();
      donnees.append('photo', photo.files[0]);
      statut.className = 'small mt-2 text-muted';
      window.tcdChargement(statut, 'Analyse de la photo en cours…');
      photo.disabled = true;
      envoyer.disabled = true;
      try {
        const r = await appeler(photo.dataset.url, donnees);
        photo.disabled = false;
        envoyer.disabled = false;
        if (!r.ok) { afficherStatut(r.message, 'text-muted'); return; }
        jeton.value = r.jeton;
        if (!r.analyse.est_textile) {
          afficherStatut('⚠ ' + r.message, 'text-danger fw-semibold');
          bloquer(true);
          return;
        }
        const a = r.analyse;
        const valeurs = { type_article: a.type_article, etat_general: a.etat_general, quantite: a.quantite_estimee, poids_kg: a.poids_estime_kg };
        Object.entries(valeurs).forEach(([nom, valeur]) => {
          if (valeur === undefined || valeur === null) return;
          champ(nom).value = valeur;
          badge(nom, true);
        });
        if (a.description_courte && !champ('message').value.trim()) {
          champ('message').value = a.description_courte;
          badge('message', true);
        }
        afficherStatut('✨ ' + r.message + ' (confiance : ' + Math.round(a.confiance * 100) + ' %)', 'text-success');
        champ('type_article').dispatchEvent(new Event('change'));
      } catch (e) {
        photo.disabled = false;
        envoyer.disabled = false;
        afficherStatut(e.message, 'text-muted');
      }
    });

    // Une valeur modifiée à la main n'est plus « suggérée par l'IA ».
    ['type_article', 'etat_general', 'quantite', 'poids_kg', 'message'].forEach((nom) => {
      champ(nom).addEventListener('input', (e) => { if (e.isTrusted) badge(nom, false); });
    });

    // IA 2 — associations recommandées
    const bloc = document.getElementById('ia-recommandations');
    const etat = bloc.querySelector('[data-ia-reco-etat]');
    const liste = bloc.querySelector('[data-ia-reco-liste]');
    const ville = document.getElementById('ville_donateur');
    let minuterie;

    function carte(r) {
      const lien = document.createElement('a');
      lien.href = r.url;
      lien.className = 'd-block p-2 rounded border text-decoration-none text-body' + (String(r.id) === bloc.dataset.association ? ' border-dark' : '');
      const entete = document.createElement('div');
      entete.className = 'd-flex justify-content-between gap-2';
      const nom = document.createElement('strong');
      nom.textContent = r.nom;
      const score = document.createElement('span');
      score.className = 'badge text-bg-dark align-self-start';
      score.textContent = r.score + ' / 100';
      entete.append(nom, score);
      const lieu = document.createElement('div');
      lieu.className = 'small text-muted';
      lieu.textContent = r.ville + (String(r.id) === bloc.dataset.association ? ' · association actuelle' : '');
      lien.append(entete, lieu);
      if (r.justification) {
        const pourquoi = document.createElement('div');
        pourquoi.className = 'small mt-1';
        pourquoi.textContent = r.justification;
        lien.append(pourquoi);
      }
      return lien;
    }

    async function recommander() {
      if (!champ('type_article').value) return;
      window.tcdChargement(etat, 'Recherche des associations…');
      bloc.setAttribute('aria-busy', 'true');
      try {
        const r = await appeler(bloc.dataset.url, {
          type_article: champ('type_article').value,
          ville: ville.value || null,
          description: champ('message').value || null,
          poids_kg: champ('poids_kg').value || null,
        });
        liste.replaceChildren(...r.recommandations.map(carte));
        etat.textContent = r.recommandations.length
          ? (r.simplifiee ? 'Recommandation simplifiée (sans IA).' : 'Score : règles (ville, capacité, campagnes) + pertinence des besoins (IA).')
          : 'Aucune association à recommander.';
      } catch (e) {
        etat.textContent = e.message;
      } finally {
        bloc.removeAttribute('aria-busy');
      }
    }
    function planifier() { clearTimeout(minuterie); minuterie = setTimeout(recommander, 600); }

    champ('type_article').addEventListener('change', planifier);
    ville.addEventListener('change', planifier);
    champ('message').addEventListener('input', planifier);
    if (champ('type_article').value) planifier();
  });
</script>
@endpush
@endonce
