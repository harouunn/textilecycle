<section data-ai-steps data-url="{{ route('upcycling.ia.etapes', $projet) }}" data-next="{{ $projet->prochainNumeroEtape() }}" class="tc-ai-assistant border rounded p-3 my-4">
  <h2 class="h5">Générer les étapes avec l’IA</h2>
  <button type="button" data-ai-generate class="btn btn-outline-dark">Générer les étapes avec l’IA</button>
  <p data-ai-message role="status" aria-live="polite"></p>
  <form data-ai-steps-form hidden method="POST" action="{{ route('upcycling.ia.etapes.enregistrer', $projet) }}" enctype="multipart/form-data">
    @csrf
    <p>Ces étapes ne sont pas encore enregistrées. Corrigez-les, cochez celles à accepter et ajoutez une photo à chacune.</p>
    <div data-ai-steps-list></div>
    <button type="button" data-ai-add class="btn btn-outline-dark">Ajouter une étape manuellement à l’aperçu</button>
    <button type="submit" class="btn btn-dark">Enregistrer les étapes cochées</button>
  </form>
  @if (old('etapes'))<script type="application/json" data-ai-old-steps>@json(old('etapes'))</script>@endif
  <template data-ai-step-template>
    <fieldset class="tc-ai-assistant border rounded p-3 mb-3" data-ai-step>
      <legend class="h6">Étape proposée</legend>
      <label><input type="checkbox" data-ai-accept checked> Accepter cette étape</label>
      <button type="button" data-ai-remove class="btn btn-sm btn-outline-danger">Retirer de l’aperçu</button>
      <div data-ai-step-fields></div>
    </fieldset>
  </template>
</section>
@include('upcycling.partials.assistant-assets')
