<?php

namespace App\Console\Commands\Dons;

use App\Enums\Dons\StatutCampagne;
use App\Models\CampagneCollecte;
use Illuminate\Console\Command;

/**
 * Module Associations & dons : passe en « Terminée » les campagnes actives dont la date de fin est passée.
 * Planifiée chaque nuit dans routes/console.php.
 */
class CloturerCampagnesExpirees extends Command
{
    protected $signature = 'dons:cloturer-campagnes';

    protected $description = 'Termine les campagnes de collecte actives dont la date de fin est dépassée';

    public function handle(): int
    {
        $total = CampagneCollecte::query()
            ->where('statut', StatutCampagne::Active)
            ->whereDate('date_fin', '<', today())
            ->update(['statut' => StatutCampagne::Terminee, 'updated_at' => now()]);

        $this->info("{$total} campagne(s) terminée(s).");

        return self::SUCCESS;
    }
}
