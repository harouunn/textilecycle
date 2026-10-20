<?php

namespace App\Policies;

use App\Enums\Upcycling\StatutProjet;
use App\Models\ProjetUpcycling;
use App\Models\User;

/**
 * L’auteur gère ses projets ; la modération est exclusivement réservée aux administrateurs.
 */
class ProjetUpcyclingPolicy
{
    public function view(?User $user, ProjetUpcycling $projet): bool
    {
        return $user?->isAdmin() || $projet->estPublie() || $user?->id === $projet->user_id;
    }

    public function submit(User $user, ProjetUpcycling $projet): bool
    {
        return $user->id === $projet->user_id && in_array($projet->statut, [StatutProjet::Brouillon, StatutProjet::Rejete], true);
    }

    public function assist(User $user, ProjetUpcycling $projet): bool
    {
        return $user->isAdmin() || $user->id === $projet->user_id;
    }

    public function moderate(User $user, ProjetUpcycling $projet): bool
    {
        return $user->isAdmin();
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
