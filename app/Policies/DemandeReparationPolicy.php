<?php

namespace App\Policies;

use App\Models\DemandeReparation;
use App\Models\User;

/**
 * Règles du front office : un utilisateur n'annule que ses propres demandes encore en attente.
 */
class DemandeReparationPolicy
{
    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DemandeReparation $demande): bool
    {
        return $user->id === $demande->user_id && $demande->estAnnulable();
    }
}
