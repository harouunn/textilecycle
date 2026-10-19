<?php

namespace App\Http\Controllers\Depot;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bon de réservation (PDF) à présenter au retrait. Son QR code ouvre la fiche de la réservation
 * dans le back office : l'équipe la scanne et marque le vêtement comme retiré.
 */
class BonReservationController extends Controller
{
    public function __invoke(Request $request, Reservation $reservation, QrCode $qrCode): Response
    {
        abort_unless($request->user()->id === $reservation->user_id || $request->user()->isAdmin(), 403);

        $reservation->load(['vetement.categorie', 'user']);

        return Pdf::loadView('depot.reservations.bon', [
            'reservation' => $reservation,
            'qrCode' => $qrCode->pngDataUri(route('admin.depot.reservations.show', $reservation)),
        ])->setPaper('a5')->download("bon-reservation-{$reservation->code}.pdf");
    }
}
