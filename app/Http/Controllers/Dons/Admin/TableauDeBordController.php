<?php

namespace App\Http\Controllers\Dons\Admin;

use App\Http\Controllers\Controller;
use App\Models\Don;
use App\Services\Dons\ImpactCalculator;
use App\Services\Dons\StatistiquesDons;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;

/**
 * Tableau de bord du module : indicateurs et graphiques (Chart.js), calculés puis mis en cache 10 minutes.
 */
class TableauDeBordController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(StatistiquesDons $statistiques, ImpactCalculator $impact): View
    {
        $this->authorize('viewAny', Don::class);

        return view('admin.dons.tableau-de-bord', [...$statistiques->tableauDeBord(), 'impact' => $impact->global()]);
    }
}
