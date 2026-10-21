<?php

namespace App\Policies;

use App\Enums\Dons\DonStatut;
use App\Models\Don;
use App\Models\User;

/**
 * Qui peut faire quoi sur un don :
 *   - l'administrateur : tout ;
 *   - le donateur : voir ses dons et les annuler tant qu'ils sont « Proposé » ;
 *   - le responsable de l'association destinataire : accepter, refuser, planifier, recevoir, distribuer.
 * Que la transition soit possible depuis le statut actuel est vérifié par DonWorkflowService.
 */
class DonPolicy
{
    /** Transitions réservées au responsable de l'association (en plus de l'administrateur). */
    private const TRANSITIONS_RESPONSABLE = [DonStatut::Accepte, DonStatut::Refuse, DonStatut::Planifie, DonStatut::Recu, DonStatut::Distribue];

    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Don $don): bool
    {
        return $this->estDonateur($user, $don) || $this->gerer($user, $don);
    }

    /**
     * Tout compte connecté peut proposer un don.
     */
    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Don $don): bool
    {
        return false;
    }

    public function delete(User $user, Don $don): bool
    {
        return false;
    }

    public function cancel(User $user, Don $don): bool
    {
        return $this->estDonateur($user, $don) && $don->statut === DonStatut::Propose;
    }

    /**
     * Le reçu existe une fois le don reçu ; le donateur et l'association peuvent le télécharger.
     */
    public function telechargerRecu(User $user, Don $don): bool
    {
        return $don->reference !== null && $this->view($user, $don);
    }

    /**
     * Le don a été envoyé à une association que ce compte gère.
     */
    public function gerer(User $user, Don $don): bool
    {
        return $don->association?->estGereePar($user) ?? false;
    }

    public function transition(User $user, Don $don, DonStatut $to): bool
    {
        if ($to === DonStatut::Annule) {
            return $this->cancel($user, $don);
        }

        return in_array($to, self::TRANSITIONS_RESPONSABLE, true) && $this->gerer($user, $don);
    }

    private function estDonateur(User $user, Don $don): bool
    {
        return (int) $don->user_id === (int) $user->id;
    }
}
