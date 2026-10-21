<?php

namespace App\Http\Controllers\Dons\Admin;

use App\Enums\Dons\DonStatut;
use App\Enums\Dons\ModeRemise;
use App\Exports\Dons\DonsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dons\DonRequest;
use App\Http\Requests\Dons\UpdateDonStatutRequest;
use App\Models\Association;
use App\Models\Don;
use App\Models\User;
use App\Services\Dons\DonWorkflowService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DonController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Don::class);

        $filters = $this->filters($request);

        $dons = Don::query()
            ->with(['association', 'user'])
            ->filtrer($filters)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $associations = Association::orderBy('nom')->pluck('nom', 'id');

        return view('admin.dons.dons.index', compact('dons', 'associations', 'filters'));
    }

    /**
     * La liste filtrée telle qu'affichée, en Excel (xlsx) ou en CSV.
     */
    public function export(Request $request, string $format): BinaryFileResponse
    {
        $this->authorize('viewAny', Don::class);

        $type = match ($format) {
            'xlsx' => Excel::XLSX,
            'csv' => Excel::CSV,
            default => abort(404),
        };

        return ExcelFacade::download(new DonsExport($this->filters($request)), 'dons-'.now()->format('Y-m-d').'.'.$format, $type);
    }

    /**
     * @return array{q: string, association_id: mixed, statut: mixed, type_article: mixed, campagne_id: mixed, risque: ?string}
     */
    private function filters(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q')),
            'association_id' => $request->query('association_id'),
            'statut' => $request->query('statut'),
            'type_article' => $request->query('type_article'),
            'campagne_id' => $request->query('campagne_id'),
            'risque' => $request->boolean('risque') ? '1' : null,
        ];
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Don::class);

        $don = new Don([
            'association_id' => $request->query('association_id'),
            'statut' => DonStatut::Propose,
            'mode_remise' => ModeRemise::DepotSurPlace,
        ]);

        return view('admin.dons.dons.create', ['don' => $don, ...$this->formOptions()]);
    }

    public function store(DonRequest $request): RedirectResponse
    {
        $this->authorize('create', Don::class);

        $don = Don::create($request->validated());

        return redirect()->route('admin.dons.dons.show', $don)
            ->with('success', "Le don n°{$don->id} a été enregistré.");
    }

    public function show(Don $don): View
    {
        $this->authorize('view', $don);

        $don->load(['association', 'user', 'vetement', 'campagne', 'historiques.user']);

        return view('admin.dons.dons.show', compact('don'));
    }

    public function edit(Don $don): View
    {
        $this->authorize('update', $don);

        return view('admin.dons.dons.edit', ['don' => $don, ...$this->formOptions()]);
    }

    public function update(DonRequest $request, Don $don): RedirectResponse
    {
        $this->authorize('update', $don);

        $don->update($request->validated());

        return redirect()->route('admin.dons.dons.show', $don)
            ->with('success', "Le don n°{$don->id} a été mis à jour.");
    }

    public function updateStatut(UpdateDonStatutRequest $request, Don $don, DonWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('transition', [$don, $request->statut()]);

        $workflow->transition($don, $request->statut(), $request->validated('commentaire'), $request->user());

        return back()->with('success', "Le statut du don n°{$don->id} est maintenant « {$don->statutLabel()} ».");
    }

    public function destroy(Don $don): RedirectResponse
    {
        $this->authorize('delete', $don);

        $don->delete();

        return redirect()->route('admin.dons.dons.index')
            ->with('success', "Le don n°{$don->id} a été supprimé.");
    }

    private function formOptions(): array
    {
        return [
            'associations' => Association::orderBy('nom')->pluck('nom', 'id'),
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
        ];
    }
}
