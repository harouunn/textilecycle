<?php

namespace App\Notifications;

use App\Enums\Ateliers\StatutDemande;
use App\Models\DemandeReparation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StatutDemandeReparationChange extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public DemandeReparation $demande,
        public StatutDemande $nouveauStatut
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $statutTexte = match ($this->nouveauStatut) {
            StatutDemande::Acceptee => 'acceptée',
            StatutDemande::Refusee => 'refusée',
            StatutDemande::Terminee => 'terminée',
            default => 'mise à jour',
        };
        
        return [
            'titre' => 'Demande de réparation ' . $statutTexte,
            'message' => "Votre demande de réparation « {$this->demande->titre} » a été {$statutTexte}",
            'demande_id' => $this->demande->id,
            'lien' => route('ateliers.demandes.index'),
            'type' => 'statut_demande',
            'statut' => $this->nouveauStatut->value,
        ];
    }
}
