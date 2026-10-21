<?php

namespace App\Exceptions\Dons;

use App\Enums\Dons\DonStatut;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Levée par DonWorkflowService quand le statut demandé n'est pas atteignable depuis le statut actuel.
 */
class InvalidDonTransitionException extends RuntimeException
{
    public function __construct(public readonly DonStatut $from, public readonly DonStatut $to)
    {
        parent::__construct("Un don « {$from->label()} » ne peut pas passer au statut « {$to->label()} ».");
    }

    /**
     * Erreur de l'utilisateur, pas de l'application : rien à journaliser.
     */
    public function report(): void {}

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage()], 422);
        }

        return back()->with('error', $this->getMessage());
    }
}
