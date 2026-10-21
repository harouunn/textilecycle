{{--
  IA 3 — assistant des donateurs : bulle de discussion en bas à droite des pages du module.
  Les réponses sont insérées avec textContent (aucun HTML de l'IA n'est interprété) ; les liens viennent du serveur.
--}}
@include('dons.partials.ia-styles')
@once
@push('styles')
<style>
  .tcd-assistant { position: fixed; right: 1rem; bottom: 1rem; z-index: 1050; }
  .tcd-assistant-panneau { width: min(360px, calc(100vw - 2rem)); max-height: min(520px, calc(100vh - 6rem)); display: flex; flex-direction: column; }
  .tcd-assistant-fil { overflow-y: auto; flex: 1 1 auto; min-height: 140px; }
  .tcd-assistant-bulle { max-width: 85%; white-space: pre-line; }
</style>
@endpush

<div class="tcd-assistant" id="assistant-dons" data-url="{{ route('dons.ia.assistant') }}" data-url-oublier="{{ route('dons.ia.assistant.oublier') }}">
  <div class="card shadow tcd-assistant-panneau mb-2" id="assistant-panneau" hidden role="dialog" aria-label="Assistant des donateurs">
    <div class="card-header d-flex justify-content-between align-items-center bg-dark text-white">
      <strong class="small text-uppercase">Assistant des dons</strong>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-link text-white p-0" id="assistant-oublier" title="Nouvelle conversation">↺</button>
        <button type="button" class="btn-close btn-close-white" id="assistant-fermer" aria-label="Fermer l'assistant"></button>
      </div>
    </div>
    <div class="card-body tcd-assistant-fil d-flex flex-column gap-2" id="assistant-fil" aria-live="polite">
      <div class="tcd-assistant-bulle bg-light rounded p-2 small">Bonjour ! Je peux vous aider à trouver une association, connaître ses besoins ou les campagnes en cours. Par exemple : « Qui a besoin de manteaux à Ariana ? »</div>
    </div>
    <form class="card-footer d-flex gap-2" id="assistant-formulaire">
      <label for="assistant-question" class="visually-hidden">Votre question</label>
      <input type="text" id="assistant-question" class="form-control form-control-sm" maxlength="500" placeholder="Votre question…" autocomplete="off" required>
      <button type="submit" class="btn btn-sm btn-dark">Envoyer</button>
    </form>
  </div>
  <button type="button" class="btn btn-dark rounded-pill shadow float-end" id="assistant-ouvrir" aria-expanded="false" aria-controls="assistant-panneau">💬 Une question ?</button>
</div>

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const racine = document.getElementById('assistant-dons');
    if (!racine) return;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const panneau = document.getElementById('assistant-panneau');
    const ouvrir = document.getElementById('assistant-ouvrir');
    const fil = document.getElementById('assistant-fil');
    const formulaire = document.getElementById('assistant-formulaire');
    const question = document.getElementById('assistant-question');

    function basculer(visible) {
      panneau.hidden = !visible;
      ouvrir.setAttribute('aria-expanded', String(visible));
      if (visible) question.focus();
    }
    ouvrir.addEventListener('click', () => basculer(panneau.hidden));
    document.getElementById('assistant-fermer').addEventListener('click', () => basculer(false));

    function bulle(texte, deLUtilisateur, liens) {
      const b = document.createElement('div');
      b.className = 'tcd-assistant-bulle rounded p-2 small ' + (deLUtilisateur ? 'bg-dark text-white align-self-end' : 'bg-light');
      b.textContent = texte;
      (liens || []).forEach((a) => {
        const lien = document.createElement('a');
        lien.href = a.url;
        lien.className = 'd-block mt-1';
        lien.textContent = '→ ' + a.nom + ' (' + a.ville + ')';
        b.append(lien);
      });
      fil.append(b);
      fil.scrollTop = fil.scrollHeight;
      return b;
    }

    formulaire.addEventListener('submit', async function (e) {
      e.preventDefault();
      const texte = question.value.trim();
      if (!texte) return;
      bulle(texte, true);
      question.value = '';
      const attente = bulle('', false);
      window.tcdChargement(attente, 'L\'assistant réfléchit…');
      const envoyerQuestion = formulaire.querySelector('button[type="submit"]');
      envoyerQuestion.disabled = true;
      try {
        const reponse = await fetch(racine.dataset.url, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
          body: JSON.stringify({ question: texte }),
        });
        const r = await reponse.json().catch(() => ({}));
        attente.remove();
        if (reponse.status === 429) return bulle('Vous avez posé beaucoup de questions : réessayez dans une minute.', false);
        if (!reponse.ok) return bulle((r.errors && Object.values(r.errors).flat()[0]) || 'L\'assistant est indisponible pour le moment.', false);
        bulle(r.reponse, false, r.associations);
      } catch (err) {
        attente.remove();
        bulle('L\'assistant est indisponible pour le moment.', false);
      } finally {
        envoyerQuestion.disabled = false;
      }
    });

    document.getElementById('assistant-oublier').addEventListener('click', async function () {
      await fetch(racine.dataset.urlOublier, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } }).catch(() => {});
      fil.replaceChildren(fil.firstElementChild);
    });
  });
</script>
@endpush
@endonce
