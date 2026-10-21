<?php

namespace App\Policies;

use App\Models\CampagneCollecte;
use App\Models\User;

/**
 * Les campagnes sont gérées par l'administrateur (back office). Le public ne voit que les campagnes actives.
 */
class CampagneCollectePolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, CampagneCollecte $campagne): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, CampagneCollecte $campagne): bool
    {
        return false;
    }

    public function delete(User $user, CampagneCollecte $campagne): bool
    {
        return false;
    }
}
