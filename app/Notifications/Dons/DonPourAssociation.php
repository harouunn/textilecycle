<?php

namespace App\Notifications\Dons;

use App\Enums\Dons\DonStatut;
use App\Models\Don;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Au responsable de l'association : un don vient d'être proposé, ou le donateur l'a annulé.
 */
class DonPourAssociation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Don $don, public DonStatut $statut) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->statut === DonStatut::Annule ? "Don n°{$this->don->id} annulé" : "Nouveau don pour {$this->don->association->nom}")
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line($this->texte())
            ->action('Voir le don', route('dons.espace.show', $this->don));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'don_id' => $this->don->id,
            'statut' => $this->statut->value,
            'message' => $this->texte(),
            'url' => route('dons.espace.show', $this->don),
        ];
    }

    private function texte(): string
    {
        $donateur = $this->don->user?->name ?? 'Un donateur';

        return $this->statut === DonStatut::Annule
            ? "{$donateur} a annulé son don n°{$this->don->id}."
            : "{$donateur} propose un don : {$this->don->quantite} × {$this->don->typeLabel()} ({$this->don->modeLabel()}).";
    }
}
