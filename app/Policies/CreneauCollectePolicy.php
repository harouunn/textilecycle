<?php

namespace App\Policies;

use App\Models\CreneauCollecte;
use App\Models\User;

/**
 * Les créneaux sont gérés par le responsable de l'association, dans son Espace association.
 */
class CreneauCollectePolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function update(User $user, CreneauCollecte $creneau): bool
    {
        return $creneau->association?->estGereePar($user) ?? false;
    }

    /**
     * Un créneau déjà réservé ne peut pas être supprimé : les donateurs comptent sur ce rendez-vous.
     */
    public function delete(User $user, CreneauCollecte $creneau): bool
    {
        return $this->update($user, $creneau) && $creneau->nombreReservations() === 0;
    }
}
