<?php

namespace App\Listeners\Dons;

use App\Enums\Dons\DonStatut;
use App\Events\Dons\DonStatutChanged;
use App\Notifications\Dons\DonPourAssociation;
use App\Notifications\Dons\StatutDonModifie;

/**
 * Prévient les personnes concernées par un don (e-mail + cloche du site, envoyés en file d'attente) :
 *   - nouveau don → le responsable de l'association ;
 *   - don annulé par le donateur → le responsable de l'association ;
 *   - autre changement de statut → le donateur, sauf s'il en est l'auteur.
 */
class NotifierDon
{
    public function handle(DonStatutChanged $event): void
    {
        $don = $event->don->loadMissing(['user', 'association.responsable']);
        $responsable = $don->association?->responsable;
        $parLeDonateur = $event->par->is($don->user);

        if ($event->estNouveauDon() || ($parLeDonateur && $event->nouveau === DonStatut::Annule)) {
            if ($responsable && ! $responsable->is($event->par)) {
                $responsable->notify(new DonPourAssociation($don, $event->nouveau));
            }

            return;
        }

        if (! $parLeDonateur && $don->user) {
            $don->user->notify(new StatutDonModifie($don, $event->ancien, $event->nouveau, $event->commentaire));
        }
    }
}
