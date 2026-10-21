<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * E-mail « Mot de passe oublié » en français : lien de réinitialisation avec jeton à usage unique,
 * valable le temps défini dans config/auth.php (passwords.users.expire, 60 minutes par défaut).
 */
class ReinitialisationMotDePasse extends Notification
{
    public function __construct(public string $token) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $duree = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');
        $lien = route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);

        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe TexTileCycle')
            ->greeting("Bonjour {$notifiable->name},")
            ->line('Vous avez demandé à réinitialiser le mot de passe de votre compte TexTileCycle.')
            ->action('Choisir un nouveau mot de passe', $lien)
            ->line("Ce lien est valable {$duree} minutes et ne peut servir qu'une seule fois.")
            ->line('Vous n\'êtes pas à l\'origine de cette demande ? Ignorez cet e-mail : votre mot de passe reste inchangé.')
            ->salutation('L\'équipe TexTileCycle');
    }
}
