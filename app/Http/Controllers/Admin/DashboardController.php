<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Ateliers\StatutDemande;
use App\Enums\Depot\Moderation;
use App\Enums\Depot\StatutReservation;
use App\Http\Controllers\Controller;
use App\Models\DemandeReparation;
use App\Models\Don;
use App\Models\ProjetUpcycling;
use App\Models\Reservation;
use App\Models\Vetement;
use App\Services\Depot\ImpactEcologique;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Tableau de bord du back office : chiffres réels des quatre gestions et file des tâches à traiter.
 */
class DashboardController extends Controller
{
    public function __invoke(ImpactEcologique $impact): View
    {
        return view('admin.dashboard', [
            'totaux' => [
                'vetements' => Vetement::query()->count(),
                'reparations' => DemandeReparation::query()->whereIn('statut', StatutDemande::occupantLAtelier())->count(),
                'upcycling' => ProjetUpcycling::query()->count(),
                'dons' => Don::query()->count(),
            ],
            'aTraiter' => [
                ['Dépôts à valider', Vetement::query()->where('moderation', Moderation::EnAttente)->count(), 'bx-check-shield', 'warning', route('admin.depot.vetements.index', ['moderation' => 'en_attente'])],
                ['Réservations à confirmer', Reservation::query()->where('statut', StatutReservation::EnAttente)->count(), 'bx-calendar-check', 'info', route('admin.depot.reservations.index', ['statut' => 'en_attente'])],
                ['Demandes de réparation en attente', DemandeReparation::query()->where('statut', StatutDemande::EnAttente)->count(), 'bx-wrench', 'primary', route('admin.ateliers.demandes.index', ['statut' => 'en_attente'])],
                ['Dons proposés', Don::query()->where('statut', 'propose')->count(), 'bx-donate-heart', 'success', route('admin.dons.dons.index', ['statut' => 'propose'])],
            ],
            'impact' => Cache::remember('depot.impact-global', now()->addMinutes(10), fn () => $impact->global()),
        ]);
    }
}
