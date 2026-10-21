<?php

namespace App\Notifications;

use App\Models\DemandeReparation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NouvelleDemandeReparation extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public DemandeReparation $demande,
        public bool $analyseErreur = false
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
        $prioriteHaute = $this->demande->diagnostic['priorite'] ?? null === 'haute';
        
        return [
            'titre' => 'Nouvelle demande de réparation',
            'message' => $this->analyseErreur 
                ? 'Analyse IA indisponible, à traiter manuellement'
                : $this->getResume(),
            'demande_id' => $this->demande->id,
            'lien' => route('admin.ateliers.demandes.show', $this->demande),
            'priorite_haute' => $prioriteHaute,
            'type' => 'demande_reparation',
        ];
    }

    /**
     * Générer le résumé de la demande
     */
    private function getResume(): string
    {
        $diagnostic = $this->demande->diagnostic ?? [];
        
        $parts = [];
        
        if (isset($diagnostic['type_dommage'])) {
            $parts[] = "Type: {$diagnostic['type_dommage']}";
        }
        
        if (isset($diagnostic['gravite'])) {
            $parts[] = "Gravité: {$diagnostic['gravite']}";
        }
        
        if (isset($diagnostic['priorite'])) {
            $parts[] = "Priorité: {$diagnostic['priorite']}";
        }
        
        return implode(' | ', $parts) ?: 'Nouvelle demande à traiter';
    }
}
