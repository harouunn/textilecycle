<?php

namespace App\Http\Controllers\Dons;

use App\Enums\Dons\DonStatut;
use App\Enums\Dons\TypeArticle;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dons\UpdateDonStatutRequest;
use App\Models\Association;
use App\Models\Don;
use App\Services\Dons\DonWorkflowService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Espace association : le responsable d'une association traite les dons qu'elle reçoit.
 */
class EspaceAssociationController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $this->authorize('accederEspace', Association::class);

        $associations = Association::gereesPar($request->user())->orderBy('nom')->get();

        $filters = [
            'q' => trim((string) $request->query('q')),
            'association_id' => $request->query('association_id'),
            'statut' => $request->query('statut'),
            'type_article' => $request->query('type_article'),
        ];
        $statut = DonStatut::tryFrom((string) $filters['statut']);
        $type = TypeArticle::tryFrom((string) $filters['type_article']);
        // Le filtre par association ne peut viser qu'une association gérée par ce compte.
        $associationIds = $associations->contains('id', (int) $filters['association_id'])
            ? [(int) $filters['association_id']]
            : $associations->modelKeys();

        $base = Don::query()->whereIn('association_id', $associationIds);

        $compteurs = (clone $base)->selectRaw('statut, count(*) as total')->groupBy('statut')->pluck('total', 'statut');

        $dons = $base
            ->with(['association', 'user'])
            ->when($filters['q'] !== '', fn ($query) => $query->where(function ($query) use ($filters) {
                $search = "%{$filters['q']}%";
                $query->where('message', 'like', $search)
                    ->orWhereHas('user', fn ($q) => $q->where('name', 'like', $search)->orWhere('email', 'like', $search));
            }))
            ->when($statut, fn ($query) => $query->where('statut', $statut))
            ->when($type, fn ($query) => $query->where('type_article', $type))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('dons.espace.index', compact('associations', 'dons', 'filters', 'compteurs'));
    }

    public function show(Don $don): View
    {
        $this->authorize('gerer', $don);

        $don->load(['association', 'user', 'campagne', 'creneau', 'historiques.user']);

        return view('dons.espace.show', compact('don'));
    }

    public function transition(UpdateDonStatutRequest $request, Don $don, DonWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('gerer', $don);
        $this->authorize('transition', [$don, $request->statut()]);

        $workflow->transition($don, $request->statut(), $request->validated('commentaire'), $request->user());

        return back()->with('success', "Le don n°{$don->id} est maintenant « {$don->statutLabel()} ».");
    }
}
