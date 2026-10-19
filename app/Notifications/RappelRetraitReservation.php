<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * E-mail envoyé la veille du retrait d'un vêtement réservé.
 */
class RappelRetraitReservation extends Notification
{
    use Queueable;

    public function __construct(public Reservation $reservation) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reservation = $this->reservation;

        return (new MailMessage)
            ->subject("Rappel : retrait de « {$reservation->vetement->titre} » demain")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre réservation {$reservation->code} est prévue demain, {$reservation->date_retrait->format('d/m/Y')}, {$reservation->creneau->label()}.")
            ->line('Présentez votre bon de réservation (ou son QR code) à l\'équipe sur place.')
            ->action('Télécharger mon bon de réservation', route('depot.reservations.bon', $reservation))
            ->line('Un empêchement ? Annulez la réservation depuis « Mes réservations » pour que le vêtement profite à quelqu\'un d\'autre.');
    }
}
