<?php

namespace App\Http\Controllers\Depot;

use App\Http\Controllers\Controller;
use App\Http\Requests\Depot\AnnulerReservationRequest;
use App\Http\Requests\Depot\StoreReservationRequest;
use App\Models\Reservation;
use App\Models\Vetement;
use App\Services\Depot\GestionReservations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * « Réserver ce vêtement » et « Mes réservations » : le client ne voit que ses propres réservations.
 */
class ReservationController extends Controller
{
    public function __construct(private GestionReservations $reservations) {}

    public function index(Request $request): View
    {
        $reservations = Reservation::query()
            ->with('vetement.categorie')
            ->whereBelongsTo($request->user())
            ->latest('id')
            ->paginate(10);

        return view('depot.reservations.index', compact('reservations'));
    }

    public function create(Request $request, Vetement $vetement): View|RedirectResponse
    {
        if (! $this->reservable($request, $vetement)) {
            return redirect()
                ->route('depot.catalogue.show', $vetement)
                ->with('erreur', 'Ce vêtement ne peut pas être réservé.');
        }

        return view('depot.reservations.create', [
            'vetement' => $vetement->load('categorie'),
            'reservation' => new Reservation(['date_retrait' => today()->addDay(), 'creneau' => 'matin']),
        ]);
    }

    public function store(StoreReservationRequest $request, Vetement $vetement): RedirectResponse
    {
        $reservation = $this->reservations->reserver($vetement, $request->user(), $request->validated());

        return redirect()
            ->route('depot.reservations.index')
            ->with('success', "Réservation {$reservation->code} enregistrée pour « {$vetement->titre} ». "
                ."Retrait le {$reservation->date_retrait->format('d/m/Y')}, {$reservation->creneau->label()}.");
    }

    public function annuler(AnnulerReservationRequest $request, Reservation $reservation): RedirectResponse
    {
        Gate::authorize('annuler', $reservation);

        $this->reservations->annuler($reservation, $request->validated('motif_annulation') ?: 'Annulée par le client.');

        return redirect()
            ->route('depot.reservations.index')
            ->with('success', "Votre réservation {$reservation->code} a été annulée.");
    }

    private function reservable(Request $request, Vetement $vetement): bool
    {
        return $vetement->user_id !== $request->user()->id
            && Vetement::query()->auCatalogue()->whereKey($vetement->id)->exists();
    }
}
