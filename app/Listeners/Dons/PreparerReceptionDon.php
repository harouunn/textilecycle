<?php

namespace App\Listeners\Dons;

use App\Enums\Dons\DonStatut;
use App\Events\Dons\DonStatutChanged;
use App\Services\Dons\BadgeService;
use App\Services\Dons\RecuDon;

/**
 * Quand un don devient « Reçu » : le reçu PDF est généré et le donateur gagne ses points.
 * Exécuté tout de suite (pas en file d'attente) : le reçu est disponible dès le retour de la page.
 */
class PreparerReceptionDon
{
    public function __construct(private RecuDon $recu, private BadgeService $badges) {}

    public function handle(DonStatutChanged $event): void
    {
        if ($event->nouveau !== DonStatut::Recu) {
            return;
        }

        $this->recu->generer($event->don);
        $this->badges->crediterDonRecu($event->don);
    }
}
