<?php

namespace App\Http\Controllers\Upcycling;

use App\Http\Controllers\Controller;
use App\Models\ProjetUpcycling;
use App\Services\Upcycling\ModerationProjet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ModerationController extends Controller
{
    public function soumettre(Request $request, ProjetUpcycling $projet, ModerationProjet $moderation): RedirectResponse
    {
        $moderation->soumettre($projet, $request->user());

        return back()->with('success', 'Projet soumis à validation.');
    }

    public function publier(Request $request, ProjetUpcycling $projet, ModerationProjet $moderation): RedirectResponse
    {
        $moderation->decider($projet, $request->user(), true);

        return back()->with('success', 'Projet validé et publié.');
    }

    public function rejeter(Request $request, ProjetUpcycling $projet, ModerationProjet $moderation): RedirectResponse
    {
        Gate::authorize('moderate', $projet);
        $data = $request->validate(ModerationProjet::reglesRefus(), ModerationProjet::messagesRefus());
        $moderation->decider($projet, $request->user(), false, $data['commentaire_refus']);

        return back()->with('success', 'Projet rejeté ; le commentaire est visible par son auteur.');
    }
}
