<?php

namespace App\Enums\Depot;

/**
 * Cycle de vie d'une réservation : en attente → confirmée → retirée, ou annulée.
 */
enum StatutReservation: string
{
    case EnAttente = 'en_attente';
    case Confirmee = 'confirmee';
    case Retiree = 'retiree';
    case Annulee = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente',
            self::Confirmee => 'Confirmée',
            self::Retiree => 'Retirée',
            self::Annulee => 'Annulée',
        };
    }

    /**
     * Couleur du badge (nom de couleur commun à Bootstrap et Vuetify).
     */
    public function color(): string
    {
        return match ($this) {
            self::EnAttente => 'warning',
            self::Confirmee => 'info',
            self::Retiree => 'success',
            self::Annulee => 'secondary',
        };
    }

    public function icone(): string
    {
        return match ($this) {
            self::EnAttente => 'bx-time-five',
            self::Confirmee => 'bx-calendar-check',
            self::Retiree => 'bx-package',
            self::Annulee => 'bx-x-circle',
        };
    }

    /**
     * Une réservation active bloque le vêtement : il n'est plus proposé dans le catalogue.
     */
    public function estActive(): bool
    {
        return in_array($this, self::actives(), true);
    }

    /**
     * @return list<self>
     */
    public static function actives(): array
    {
        return [self::EnAttente, self::Confirmee];
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
