<?php

namespace App\Console\Commands;

use App\Services\Depot\GestionReservations;
use Illuminate\Console\Command;

/**
 * Tâche quotidienne du module Dépôt (planifiée dans routes/console.php) :
 *  - annule les réservations non retirées à la date prévue et remet les vêtements au catalogue ;
 *  - envoie un rappel aux clients dont le retrait est prévu le lendemain.
 *
 * php artisan depot:reservations
 */
class TraiterReservations extends Command
{
    protected $signature = 'depot:reservations';

    protected $description = 'Expire les réservations non retirées et envoie les rappels de la veille';

    public function handle(GestionReservations $reservations): int
    {
        $expirees = $reservations->expirer();
        $rappels = $reservations->envoyerRappels();

        $this->info("{$expirees} réservation(s) expirée(s), {$rappels} rappel(s) envoyé(s).");

        return self::SUCCESS;
    }
}
