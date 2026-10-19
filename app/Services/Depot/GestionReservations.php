<?php

namespace App\Services\Depot;

use App\Enums\Depot\Moderation;
use App\Enums\Depot\StatutReservation;
use App\Enums\Depot\StatutVetement;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vetement;
use App\Notifications\RappelRetraitReservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Règles métier des réservations. Le statut du vêtement suit celui de sa réservation :
 *  - réservation créée   → vêtement « Réservé » (il quitte le catalogue) ;
 *  - réservation retirée → vêtement « Donné » ;
 *  - réservation annulée → vêtement de nouveau « Disponible ».
 * Chaque changement se fait dans une transaction, avec le vêtement verrouillé :
 * deux clients ne peuvent pas réserver le même vêtement au même moment.
 */
class GestionReservations
{
    /**
     * @param  array{date_retrait: string, creneau: string, telephone: string, message?: ?string}  $donnees
     */
    public function reserver(Vetement $vetement, User $client, array $donnees): Reservation
    {
        return DB::transaction(function () use ($vetement, $client, $donnees) {
            $vetement = Vetement::query()->lockForUpdate()->findOrFail($vetement->id);

            if ($vetement->user_id === $client->id) {
                throw ValidationException::withMessages(['vetement' => 'Vous ne pouvez pas réserver un vêtement que vous avez déposé.']);
            }
            if ($vetement->moderation !== Moderation::Approuve || $vetement->statut !== StatutVetement::Disponible) {
                throw ValidationException::withMessages(['vetement' => 'Ce vêtement n\'est plus disponible à la réservation.']);
            }

            $reservation = $vetement->reservations()->create([...$donnees, 'user_id' => $client->id]);
            $vetement->update(['statut' => StatutVetement::Reserve]);

            return $reservation;
        });
    }

    public function confirmer(Reservation $reservation): void
    {
        $this->exigerStatut($reservation, [StatutReservation::EnAttente], 'Seule une réservation en attente peut être confirmée.');

        $reservation->update(['statut' => StatutReservation::Confirmee, 'confirmee_le' => now()]);
    }

    public function marquerRetiree(Reservation $reservation): void
    {
        $this->exigerStatut($reservation, StatutReservation::actives(), 'Seule une réservation en cours peut être marquée comme retirée.');

        DB::transaction(function () use ($reservation) {
            $reservation->update(['statut' => StatutReservation::Retiree, 'retiree_le' => now()]);
            $reservation->vetement()->update(['statut' => StatutVetement::Donne]);
        });
    }

    public function annuler(Reservation $reservation, ?string $motif = null): void
    {
        $this->exigerStatut($reservation, StatutReservation::actives(), 'Cette réservation est déjà terminée.');

        DB::transaction(function () use ($reservation, $motif) {
            $reservation->update(['statut' => StatutReservation::Annulee, 'annulee_le' => now(), 'motif_annulation' => $motif]);

            // Le vêtement revient au catalogue s'il était bloqué par cette réservation.
            $reservation->vetement()
                ->where('statut', StatutVetement::Reserve)
                ->update(['statut' => StatutVetement::Disponible]);
        });
    }

    /**
     * Annule les réservations en cours dont la date de retrait est passée : le vêtement revient au catalogue.
     *
     * @return int nombre de réservations expirées
     */
    public function expirer(): int
    {
        $expirees = Reservation::query()->actives()->whereDate('date_retrait', '<', today())->get();

        $expirees->each(fn (Reservation $reservation) => $this->annuler($reservation, 'Expirée : le vêtement n\'a pas été retiré à la date prévue.'));

        return $expirees->count();
    }

    /**
     * Envoie un rappel aux clients dont le retrait est prévu demain (une seule fois par réservation).
     *
     * @return int nombre de rappels envoyés
     */
    public function envoyerRappels(): int
    {
        $aRappeler = Reservation::query()
            ->with(['user', 'vetement'])
            ->actives()
            ->whereDate('date_retrait', today()->addDay())
            ->whereNull('rappel_envoye_le')
            ->get();

        foreach ($aRappeler as $reservation) {
            $reservation->user->notify(new RappelRetraitReservation($reservation));
            $reservation->update(['rappel_envoye_le' => now()]);
        }

        return $aRappeler->count();
    }

    /**
     * @param  list<StatutReservation>  $statuts
     */
    private function exigerStatut(Reservation $reservation, array $statuts, string $message): void
    {
        if (! in_array($reservation->statut, $statuts, true)) {
            throw ValidationException::withMessages(['statut' => $message]);
        }
    }
}
