<?php

namespace App\Enums\Ateliers;

enum StatutDemande: string
{
    case EnAttente = 'en_attente';
    case Acceptee = 'acceptee';
    case EnCours = 'en_cours';
    case Terminee = 'terminee';
    case Refusee = 'refusee';

    public function label(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente',
            self::Acceptee => 'Acceptée',
            self::EnCours => 'En cours',
            self::Terminee => 'Terminée',
            self::Refusee => 'Refusée',
        };
    }

    /**
     * Couleur du badge (nom de couleur commun à Bootstrap et Vuetify).
     */
    public function color(): string
    {
        return match ($this) {
            self::EnAttente => 'warning',
            self::Acceptee => 'info',
            self::EnCours => 'primary',
            self::Terminee => 'success',
            self::Refusee => 'secondary',
        };
    }

    /**
     * Statuts qui occupent l'atelier : ils allongent le délai des nouvelles demandes.
     *
     * @return list<self>
     */
    public static function occupantLAtelier(): array
    {
        return [self::EnAttente, self::Acceptee, self::EnCours];
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
