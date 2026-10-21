<?php

namespace App\Events\Dons;

use App\Enums\Dons\DonStatut;
use App\Models\Don;
use App\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Le statut d'un don a changé. Émis après la validation de la transaction :
 * un écouteur (notification, e-mail…) ne voit jamais un changement annulé.
 * ancien est vide quand un donateur vient de proposer le don (DonWorkflowService::proposer).
 */
class DonStatutChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Don $don,
        public ?DonStatut $ancien,
        public DonStatut $nouveau,
        public User $par,
        public ?string $commentaire = null,
    ) {}

    public function estNouveauDon(): bool
    {
        return $this->ancien === null;
    }
}
