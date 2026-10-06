<?php

namespace App\Policies;

use App\Models\ProjetUpcycling;
use App\Models\User;

/**
 * Règles du front office : un utilisateur ne gère que ses propres projets.
 * Le back office est réservé aux utilisateurs authentifiés et n'utilise pas cette policy.
 */
class ProjetUpcyclingPolicy
{
    public function view(?User $user, ProjetUpcycling $projet): bool
    {
        return $projet->estPublie() || $user?->id === $projet->user_id;
    }

    public function update(User $user, ProjetUpcycling $projet): bool
    {
        return $user->id === $projet->user_id;
    }

    public function delete(User $user, ProjetUpcycling $projet): bool
    {
        return $user->id === $projet->user_id;
    }
}
