<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vetement;

/**
 * Règles du front office : un utilisateur ne gère que ses propres dépôts.
 */
class VetementPolicy
{
    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Vetement $vetement): bool
    {
        return $user->id === $vetement->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Vetement $vetement): bool
    {
        return $user->id === $vetement->user_id;
    }
}
