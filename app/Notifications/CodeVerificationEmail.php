<?php

namespace App\Notifications;

use App\Services\Auth\VerificationEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * E-mail contenant le code à 6 chiffres qui confirme l'adresse du compte.
 */
class CodeVerificationEmail extends Notification
{
    public function __construct(public string $code) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->code} est votre code de vérification TexTileCycle")
            ->greeting("Bonjour {$notifiable->name},")
            ->line('Bienvenue sur TexTileCycle ! Pour confirmer votre adresse e-mail, saisissez ce code sur le site :')
            ->line("**{$this->code}**")
            ->line('Ce code est valable '.VerificationEmail::DUREE_MINUTES.' minutes.')
            ->action('Saisir mon code', route('verification.notice'))
            ->line('Vous n\'êtes pas à l\'origine de cette inscription ? Ignorez simplement cet e-mail.')
            ->salutation('L\'équipe TexTileCycle');
    }
}
