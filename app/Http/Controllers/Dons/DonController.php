<?php

namespace App\Http\Controllers\Dons;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dons\FaireDonRequest;
use App\Models\Association;
use App\Models\Don;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Front office: a logged-in user proposes donations and follows them in "Mes dons".
 */
class DonController extends Controller
{
    public function index(Request $request): View
    {
        $dons = Don::whereBelongsTo($request->user())
            ->with('association')
            ->latest()
            ->paginate(10);

        return view('dons.mes-dons.index', compact('dons'));
    }

    public function create(Association $association): View
    {
        abort_unless($association->active, 404);

        return view('dons.create', compact('association'));
    }

    public function store(FaireDonRequest $request, Association $association): RedirectResponse
    {
        abort_unless($association->active, 404);

        $association->dons()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
            'statut' => 'propose',
        ]);

        return redirect()->route('dons.mes-dons')
            ->with('success', "Merci ! Votre don à « {$association->nom} » a bien été proposé. L'association va l'examiner.");
    }

    public function destroy(Request $request, Don $don): RedirectResponse
    {
        abort_unless((int) $don->user_id === (int) $request->user()->id, 403);

        if (! $don->isCancellable()) {
            return back()->with('error', 'Seuls les dons au statut « Proposé » peuvent être annulés.');
        }

        $don->delete();

        return redirect()->route('dons.mes-dons')->with('success', 'Votre don a été annulé.');
    }
}
