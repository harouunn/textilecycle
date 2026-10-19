<?php

namespace App\Http\Controllers\Admin\Depot;

use App\Enums\Depot\StatutReservation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Depot\AnnulerReservationRequest;
use App\Models\Reservation;
use App\Services\Depot\GestionReservations;
use App\Services\Depot\StatistiquesDepot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Suivi des réservations par l'admin : confirmer, marquer comme retirée, annuler ou supprimer.
 * Les réservations sont créées par les clients depuis le catalogue.
 */
class ReservationController extends Controller
{
    public function __construct(private GestionReservations $reservations) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, StatistiquesDepot $statistiques): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'statut' => ['nullable', Rule::enum(StatutReservation::class)],
        ]);

        $reservations = Reservation::query()
            ->with(['vetement.categorie', 'user'])
            ->filter($filters)
            ->orderBy('date_retrait')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.depot.reservations.index', [
            'reservations' => $reservations,
            'filters' => $filters,
            'stats' => $statistiques->reservations(),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Reservation $reservation): View
    {
        $reservation->load(['vetement.categorie', 'vetement.user', 'user']);

        return view('admin.depot.reservations.show', compact('reservation'));
    }

    public function confirmer(Reservation $reservation): RedirectResponse
    {
        $this->reservations->confirmer($reservation);

        return $this->retour($reservation, "La réservation {$reservation->code} est confirmée.");
    }

    public function retirer(Reservation $reservation): RedirectResponse
    {
        $this->reservations->marquerRetiree($reservation);

        return $this->retour($reservation, "Le vêtement de la réservation {$reservation->code} a été retiré : il est maintenant « Donné ».");
    }

    public function annuler(AnnulerReservationRequest $request, Reservation $reservation): RedirectResponse
    {
        $this->reservations->annuler($reservation, $request->validated('motif_annulation'));

        return $this->retour($reservation, "La réservation {$reservation->code} est annulée : le vêtement revient au catalogue.");
    }

    /**
     * Remove the specified resource from storage. Une réservation en cours est d'abord annulée.
     */
    public function destroy(Reservation $reservation): RedirectResponse
    {
        if ($reservation->statut->estActive()) {
            $this->reservations->annuler($reservation, 'Réservation supprimée par l\'administrateur.');
        }
        $reservation->delete();

        return redirect()
            ->route('admin.depot.reservations.index')
            ->with('success', "La réservation {$reservation->code} a été supprimée.");
    }

    private function retour(Reservation $reservation, string $message): RedirectResponse
    {
        return redirect()->route('admin.depot.reservations.show', $reservation)->with('success', $message);
    }
}
