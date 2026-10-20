<?php

namespace App\Http\Controllers\Upcycling;

use App\Enums\Upcycling\Difficulte;
use App\Http\Controllers\Controller;
use App\Models\ProjetUpcycling;
use App\Services\Upcycling\AssistantIA;
use App\Services\Upcycling\RecommandationProjets;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Galerie publique des projets publiés. */
class GalerieController extends Controller
{
    public function index(Request $request, RecommandationProjets $recommandations): View
    {
        $request->validate(['idee' => 'nullable|string|max:2000', 'page' => 'nullable|integer|min:1']);
        $idee = trim((string) $request->query('idee', ''));
        $difficulte = Difficulte::tryFrom((string) $request->query('difficulte'));

        $projets = ProjetUpcycling::query()
            ->publie()
            ->with('user')
            ->withCount('etapes')
            ->when($difficulte, fn ($query) => $query->where('difficulte', $difficulte))
            ->latest()
            ->paginate(9)
            ->withQueryString();

        if ($idee !== '') {
            $resultats = $recommandations->chercher($idee, $difficulte);
            $page = LengthAwarePaginator::resolveCurrentPage();
            $projets = new LengthAwarePaginator($resultats->forPage($page, 9)->values(), $resultats->count(), 9, $page, ['path' => $request->url(), 'query' => $request->query()]);
        }

        return view('upcycling.index', [
            'idee' => $idee,
            'projets' => $projets,
            'difficultes' => Difficulte::options(),
            'difficulteActive' => $difficulte?->value,
        ]);
    }

    public function show(Request $request, ProjetUpcycling $projet): View
    {
        // Un brouillon n'est visible que par son auteur (aperçu)
        Gate::authorize('view', $projet);

        $projet->load(['user', 'etapes']);

        $request->validate(['langue' => 'nullable|in:fr,ar,en']);
        $langueAffichee = $request->query('langue');
        $empreinte = AssistantIA::empreinte($projet);
        $traductionsDisponibles = collect($projet->traductions ?? [])->filter(fn ($tr) => isset($tr['empreinte']) && hash_equals($tr['empreinte'], $empreinte))->all();
        if ($langueAffichee && isset($traductionsDisponibles[$langueAffichee])) {
            $texte = $traductionsDisponibles[$langueAffichee]['texte'];
            // Modèles en mémoire uniquement : aucun save(), les originaux restent intacts.
            foreach (AssistantIA::CHAMPS as $champ) {
                $projet->setAttribute($champ, $texte[$champ]);
            }
            $etapes = collect($texte['etapes'])->keyBy('id');
            foreach ($projet->etapes as $etape) {
                foreach (['titre', 'contenu', 'outils_necessaires', 'conseils_securite'] as $champ) {
                    $etape->setAttribute($champ, $etapes[$etape->id][$champ] ?? $etape->$champ);
                }
            }
        } else {
            $langueAffichee = null;
        }

        return view('upcycling.show', compact('projet', 'langueAffichee', 'traductionsDisponibles'));
    }
}
