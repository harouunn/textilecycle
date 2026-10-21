<?php

namespace App\Http\Controllers\Dons;

use App\Enums\Dons\DonStatut;
use App\Enums\Dons\TypeArticle;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dons\FaireDonRequest;
use App\Models\Association;
use App\Models\CampagneCollecte;
use App\Models\Don;
use App\Services\Dons\DonWorkflowService;
use App\Services\Dons\Ia\AnalysePhotoDon;
use App\Services\Dons\ImpactCalculator;
use App\Services\Dons\RecuDon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Front office: a logged-in user proposes donations and follows them in "Mes dons".
 */
class DonController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $dons = Don::whereBelongsTo($request->user())
            ->with('association')
            ->latest()
            ->paginate(10);

        $estResponsable = $request->user()->can('accederEspace', Association::class);

        return view('dons.mes-dons.index', compact('dons', 'estResponsable'));
    }

    public function show(Don $don, ImpactCalculator $impact): View
    {
        $this->authorize('view', $don);

        $don->load(['association', 'campagne', 'creneau', 'historiques.user']);

        return view('dons.mes-dons.show', ['don' => $don, 'impact' => $impact->pourDon($don)]);
    }

    public function create(Request $request, Association $association): View
    {
        abort_unless($association->active, 404);
        $this->authorize('create', Don::class);

        $campagnes = $association->campagnes()->enCours()->orderBy('date_fin')->get();
        $creneaux = $association->creneaux()->disponibles()->avecReservations()->orderBy('date')->orderBy('heure_debut')->get();
        $campagneChoisie = $campagnes->firstWhere('id', (int) $request->query('campagne'))?->id;
        // Pour les associations recommandées (IA) : villes des associations et type d'article éventuellement transmis.
        $villes = Association::active()->distinct()->orderBy('ville')->pluck('ville');
        $typeChoisi = TypeArticle::tryFrom((string) $request->query('type_article'))?->value;

        return view('dons.create', compact('association', 'campagnes', 'creneaux', 'campagneChoisie', 'villes', 'typeChoisi'));
    }

    public function store(FaireDonRequest $request, Association $association, DonWorkflowService $workflow, AnalysePhotoDon $photo): RedirectResponse
    {
        abort_unless($association->active, 404);
        $this->authorize('create', Don::class);

        $data = $request->validated();
        $jeton = $data['ia_jeton'] ?? null;
        unset($data['ia_jeton']);

        // Analyse photo (IA) : relue côté serveur à partir du jeton ; une photo sans textile bloque l'envoi.
        $analyse = $photo->recuperer($request->user(), $jeton);
        if ($analyse !== null && ! $analyse['est_textile']) {
            throw ValidationException::withMessages(['photo' => 'La photo analysée ne montre pas de textiles : choisissez une photo de vos vêtements, ou retirez-la.']);
        }

        $workflow->proposer($association, $data, $request->user(), $analyse ? ['photo' => $analyse] : null);
        if ($jeton) {
            $photo->oublier($request->user(), $jeton);
        }

        return redirect()->route('dons.mes-dons')
            ->with('success', "Merci ! Votre don à « {$association->nom} » a bien été proposé. L'association va l'examiner.");
    }

    public function cancel(Request $request, Don $don, DonWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('cancel', $don);

        $workflow->transition($don, DonStatut::Annule, 'Annulé par le donateur.', $request->user());

        return redirect()->route('dons.mes-dons')->with('success', 'Votre don a été annulé.');
    }

    public function recu(Don $don, RecuDon $recu): Response
    {
        abort_if($don->reference === null, 404);
        $this->authorize('telechargerRecu', $don);

        return response($recu->pdf($don), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="recu-'.$don->reference.'.pdf"',
        ]);
    }

    /**
     * Campagnes actives des associations partenaires, avec leur progression.
     */
    public function campagnes(): View
    {
        $campagnes = CampagneCollecte::query()
            ->enCours()
            ->whereHas('association', fn ($q) => $q->active())
            ->with('association')
            ->avecProgression()
            ->orderBy('date_fin')
            ->paginate(9);

        return view('dons.campagnes.index', compact('campagnes'));
    }
}
