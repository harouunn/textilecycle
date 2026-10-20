<section data-ai-translation data-url="{{ route('upcycling.ia.traduction', $projet) }}" data-confirm-url="{{ route('upcycling.ia.traduction.confirmer', [$projet, 'fr']) }}" class="tc-ai-assistant border rounded p-3 my-4">
  <h2 class="h5">Traduire le projet et ses étapes</h2>
  <label>Langue cible <select data-ai-language class="form-select tcu-input"><option value="fr">Français</option><option value="ar">العربية — Arabe</option><option value="en">English — Anglais</option></select></label>
  <button type="button" data-ai-generate class="btn btn-outline-dark">Prévisualiser la traduction</button>
  <p data-ai-message role="status" aria-live="polite"></p>
  <div data-ai-preview hidden><h3 class="h6">Aperçu modifiable de la traduction</h3>
    <form data-ai-translation-form method="POST">@csrf @method('PUT')<input name="jeton" type="hidden"><div data-ai-values></div><button type="submit" class="btn btn-dark">Confirmer cette traduction</button></form>
    <p>Les textes originaux seront conservés. L’enregistrement demande une nouvelle validation du projet.</p>
  </div>
</section>
@include('upcycling.partials.assistant-assets')
