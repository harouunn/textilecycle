<?php

namespace App\Policies;

use App\Models\Association;
use App\Models\User;

/**
 * L'administrateur gère toutes les associations ; un responsable ne voit que la sienne.
 * Les pages publiques (associations actives) ne passent pas par cette policy : elles sont ouvertes aux visiteurs.
 */
class AssociationPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Association $association): bool
    {
        return $association->estGereePar($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Association $association): bool
    {
        return false;
    }

    public function delete(User $user, Association $association): bool
    {
        return false;
    }

    /**
     * Le responsable met à jour la présentation publique (description, besoins) de son association.
     */
    public function modifierPresentation(User $user, Association $association): bool
    {
        return $association->estGereePar($user);
    }

    /**
     * Le responsable ouvre et ferme les créneaux de collecte de son association.
     */
    public function gererCreneaux(User $user, Association $association): bool
    {
        return $association->estGereePar($user);
    }

    /**
     * L'Espace association est ouvert aux comptes responsables d'au moins une association.
     */
    public function accederEspace(User $user): bool
    {
        return Association::query()->gereesPar($user)->exists();
    }
}
