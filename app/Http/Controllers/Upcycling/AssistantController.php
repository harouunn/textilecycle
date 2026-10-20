<?php

namespace App\Http\Controllers\Upcycling;

use App\Http\Controllers\Controller;
use App\Http\Requests\Upcycling\AssistantRequest;
use App\Http\Requests\Upcycling\EtapesAssistantRequest;
use App\Models\EtapeProjet;
use App\Models\ProjetUpcycling;
use App\Services\Upcycling\AssistantIA;
use App\Services\Upcycling\AssistantIndisponible;
use App\Services\Upcycling\EnregistrerAvecPhotos;
use App\Services\Upcycling\ModerationProjet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AssistantController extends Controller
{
    private function repondre(callable $action): JsonResponse
    {
        try {
            return response()->json(['proposition' => $action()]);
        } catch (AssistantIndisponible $e) {
            $payload = ['message' => $e->getMessage(), 'code' => $e->raison];
            $headers = [];
            $idee = request()->input('idee');
            if (! is_string($idee)) {
                $idee = request()->route('projet')?->titre ?? '';
            }
            $payload['fallback_url'] = route('upcycling.index', ['idee' => mb_substr($idee, 0, 2000)]);
            $payload['message'] .= ' Votre saisie est conservée. Le formulaire manuel et la recherche locale restent disponibles.';
            if ($e->retryAfter !== null) {
                $payload['retry_after'] = $e->retryAfter;
                $headers['Retry-After'] = (string) $e->retryAfter;
                $payload['message'] .= ' Attendez au moins '.$e->retryAfter.' seconde(s).';
            }

            return response()->json($payload, 503, $headers);
        }
    }

    public function projet(AssistantRequest $request, AssistantIA $assistant): JsonResponse
    {
        return $this->repondre(fn () => $assistant->projet($request->validated('idee')));
    }

    public function etapes(AssistantRequest $request, ProjetUpcycling $projet, AssistantIA $assistant): JsonResponse
    {
        return $this->repondre(fn () => $assistant->etapes($projet));
    }

    public function enregistrerEtapes(EtapesAssistantRequest $request, ProjetUpcycling $projet, EnregistrerAvecPhotos $enregistrement): RedirectResponse
    {
        $fichiers = [];
        try {
            DB::transaction(function () use ($request, $projet, $enregistrement, &$fichiers) {
                $projet = ProjetUpcycling::query()->lockForUpdate()->findOrFail($projet->id);
                // Revérifier l'unicité après verrouillage en cas de requêtes simultanées.
                Validator::make($request->all(), $request->rules())->validate();
                foreach ($request->validated('etapes') as $i => $data) {
                    $file = $request->file("etapes.$i.photo");
                    unset($data['photo']);
                    $r = Request::create('/', 'POST');
                    $r->files->set('photo', $file);
                    $etape = new EtapeProjet(['projet_upcycling_id' => $projet->id]);
                    $enregistrement->enregistrer($etape, $data, $r, ['photo'], 'upcycling/etapes');
                    $fichiers[] = $etape->photo;
                }
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($fichiers);
            throw $e;
        }

        return back()->with('success', 'Les étapes sélectionnées ont été enregistrées.');
    }

    public function traduction(AssistantRequest $request, ProjetUpcycling $projet, AssistantIA $assistant): JsonResponse
    {
        return $this->repondre(function () use ($request, $projet, $assistant) {
            $hash = AssistantIA::empreinte($projet);
            $texte = $assistant->traduire($projet, $request->validated('langue'));
            $jeton = bin2hex(random_bytes(24));
            $request->session()->put('upcycling.traductions.'.$projet->id, ['jeton' => $jeton, 'user' => $request->user()->id, 'langue' => $request->validated('langue'), 'empreinte' => $hash, 'texte' => $texte, 'expire' => time() + 900]);

            return ['texte' => $texte, 'jeton' => $jeton, 'langue' => $request->validated('langue')];
        });
    }

    public function confirmerTraduction(Request $request, ProjetUpcycling $projet, string $langue): RedirectResponse
    {
        Gate::authorize('assist', $projet);
        $data = $request->validate(['jeton' => 'required|string', 'texte' => 'sometimes|array']);
        $apercu = $request->session()->get('upcycling.traductions.'.$projet->id);
        if (! $apercu || $apercu['user'] !== $request->user()->id || $apercu['langue'] !== $langue || $apercu['expire'] < time() || ! hash_equals($apercu['jeton'], $data['jeton'])) {
            throw ValidationException::withMessages(['traduction' => 'Cet aperçu a expiré. Générez une nouvelle traduction.']);
        }
        if (isset($data['texte'])) {
            if ($request->boolean('sans_etapes') && ! $projet->etapes()->exists()) {
                $data['texte']['etapes'] = [];
            }
            try {
                $apercu['texte'] = app(AssistantIA::class)->validerTraduction($projet, $data['texte']);
            } catch (AssistantIndisponible $e) {
                throw ValidationException::withMessages(['traduction' => $e->getMessage()]);
            }
        }
        DB::transaction(function () use ($projet, $apercu, $langue) {
            $projet = ProjetUpcycling::query()->lockForUpdate()->findOrFail($projet->id);
            if (! hash_equals($apercu['empreinte'], AssistantIA::empreinte($projet))) {
                throw ValidationException::withMessages(['traduction' => 'Le projet a changé. Générez une nouvelle traduction.']);
            }
            ModerationProjet::remettreEnBrouillon($projet);
            $traductions = $projet->traductions ?? [];
            $traductions[$langue] = ['empreinte' => $apercu['empreinte'], 'texte' => $apercu['texte']];
            $projet->forceFill(['traductions' => $traductions])->save();
            foreach ($apercu['texte']['etapes'] as $texte) {
                $etape = $projet->etapes()->findOrFail($texte['id']);
                $tr = $etape->traductions ?? [];
                unset($texte['id'], $texte['numero']);
                $tr[$langue] = $texte;
                $etape->forceFill(['traductions' => $tr])->save();
            }
        });
        $request->session()->forget('upcycling.traductions.'.$projet->id);

        return back()->with('success', 'Traduction enregistrée. Les textes originaux sont conservés ; une nouvelle validation est nécessaire.');
    }
}
