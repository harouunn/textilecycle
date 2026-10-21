<?php

namespace App\Notifications\Dons;

use App\Enums\Dons\DonStatut;
use App\Models\Don;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Au donateur : le statut de son don a changé.
 */
class StatutDonModifie extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Don $don,
        public ?DonStatut $ancien,
        public DonStatut $nouveau,
        public ?string $commentaire = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("Votre don n°{$this->don->id} : {$this->nouveau->label()}")
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line($this->texte());

        if ($this->commentaire) {
            $message->line('Message de l\'association : « '.$this->commentaire.' »');
        }
        if ($this->nouveau === DonStatut::Recu) {
            $message->line('Votre reçu est disponible : vous pouvez le télécharger depuis « Mes dons ».');
        }

        return $message->action('Suivre mon don', route('dons.mes-dons.show', $this->don))
            ->salutation('Merci pour votre générosité — l\'équipe TexTileCycle');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'don_id' => $this->don->id,
            'statut' => $this->nouveau->value,
            'message' => $this->texte(),
            'url' => route('dons.mes-dons.show', $this->don),
        ];
    }

    private function texte(): string
    {
        $association = $this->don->association?->nom ?? 'l\'association';

        return match ($this->nouveau) {
            DonStatut::Accepte => "{$association} a accepté votre don.",
            DonStatut::Planifie => "La remise de votre don à {$association} est planifiée.",
            DonStatut::Recu => "{$association} a bien reçu votre don. Merci !",
            DonStatut::Distribue => "Votre don a été distribué par {$association}.",
            DonStatut::Refuse => "{$association} ne peut pas accepter votre don.",
            DonStatut::Annule => 'Votre don a été annulé.',
            DonStatut::Propose => 'Votre don est de nouveau en attente de réponse.',
        };
    }
}
