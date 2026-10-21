<?php

namespace App\Observers;

use App\Enums\Ateliers\StatutDemande;
use App\Enums\Role;
use App\Models\DemandeReparation;
use App\Models\User;
use App\Notifications\NouvelleDemandeReparation;
use App\Notifications\StatutDemandeReparationChange;

class DemandeReparationObserver
{
    /**
     * Handle the DemandeReparation "created" event.
     * Lance uniquement le job d'analyse.
     */
    public function created(DemandeReparation $demandeReparation): void
    {
        \App\Jobs\AnalyserDemandeJob::dispatch($demandeReparation);
    }

    /**
     * Handle the DemandeReparation "updated" event.
     * Notifie les admins si l'analyse vient de se terminer, et le particulier si le statut change.
     */
    public function updated(DemandeReparation $demandeReparation): void
    {
        // Notifier les admins si l'analyse vient de se terminer
        if ($demandeReparation->wasChanged('analyse_statut')) {
            $this->notifierAdminSiAnalyseTerminee($demandeReparation);
        }

        // Notifier le particulier si le statut change vers acceptée, refusée ou terminée
        if ($demandeReparation->wasChanged('statut')) {
            $nouveauStatut = $demandeReparation->statut;
            
            if (in_array($nouveauStatut, [StatutDemande::Acceptee, StatutDemande::Refusee, StatutDemande::Terminee])) {
                $demandeReparation->user->notify(
                    new StatutDemandeReparationChange($demandeReparation, $nouveauStatut)
                );
            }
        }
    }

    /**
     * Notifie les admins si l'analyse est terminée (ok ou erreur) et qu'ils n'ont pas déjà été notifiés.
     */
    private function notifierAdminSiAnalyseTerminee(DemandeReparation $demandeReparation): void
    {
        // Ne notifier que si l'analyse est terminée et que les admins n'ont pas déjà été notifiés
        if ($demandeReparation->analyse_statut !== null && !$demandeReparation->admin_notifie) {
            $analyseErreur = $demandeReparation->analyse_statut === 'erreur';
            
            // Récupérer tous les admins
            $admins = User::where('role', Role::Admin)->get();
            
            // Notifier chaque admin
            foreach ($admins as $admin) {
                $admin->notify(new NouvelleDemandeReparation($demandeReparation, $analyseErreur));
            }
            
            // Marquer comme notifié pour éviter les doublons
            $demandeReparation->admin_notifie = true;
            $demandeReparation->saveQuietly(); // saveQuietly pour ne pas déclencher l'observer à nouveau
        }
    }
}
