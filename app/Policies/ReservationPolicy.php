<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

/**
 * Règles du front office : un client n'annule que ses propres réservations encore en cours.
 */
class ReservationPolicy
{
    public function annuler(User $user, Reservation $reservation): bool
    {
        return $user->id === $reservation->user_id && $reservation->statut->estActive();
    }
}
