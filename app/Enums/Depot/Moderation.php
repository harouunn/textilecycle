<?php

namespace App\Enums\Depot;

/**
 * Validation par l'admin d'un vêtement déposé par un client, avant sa publication dans le catalogue.
 */
enum Moderation: string
{
    case EnAttente = 'en_attente';
    case Approuve = 'approuve';
    case Refuse = 'refuse';

    public function label(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente de validation',
            self::Approuve => 'Approuvé',
            self::Refuse => 'Refusé',
        };
    }

    /**
     * Couleur du badge (nom de couleur commun à Bootstrap et Vuetify).
     */
    public function color(): string
    {
        return match ($this) {
            self::EnAttente => 'warning',
            self::Approuve => 'success',
            self::Refuse => 'error',
        };
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
