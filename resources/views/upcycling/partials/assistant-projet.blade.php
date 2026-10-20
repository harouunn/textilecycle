<section data-ai-project data-url="{{ route('upcycling.ia.projet') }}" class="tc-ai-assistant border rounded p-3 mb-4" aria-label="Assistance pour le projet">
  <h2 class="h5">Assistant IA</h2>
  <label for="idee-assistant">Quel vêtement voulez-vous transformer et en quoi ?</label>
  <textarea id="idee-assistant" data-ai-idea class="form-control tcu-input" rows="3" maxlength="2000" placeholder="J’ai un vieux jean et je veux créer un sac facile."></textarea>
  <button type="button" data-ai-generate class="btn btn-outline-dark mt-2">Aider à remplir avec l’IA</button>
  <p data-ai-message role="status" aria-live="polite"></p>
  <div data-ai-preview hidden><h3 class="h6">Proposition à relire</h3><dl data-ai-values></dl><button type="button" data-ai-apply class="btn btn-dark">Utiliser cette proposition</button><p>Cette action remplit les champs ci-dessous. Vous pouvez ensuite tout modifier. Ajoutez vos deux photos manuellement.</p></div>
  <p>Facultatif : vous pouvez remplir le formulaire manuellement à tout moment.</p>
</section>
@include('upcycling.partials.assistant-assets')
