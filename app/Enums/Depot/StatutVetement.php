<?php

namespace App\Enums\Depot;

enum StatutVetement: string
{
    case Disponible = 'disponible';
    case Reserve = 'reserve';
    case Donne = 'donne';
    case Recycle = 'recycle';

    public function label(): string
    {
        return match ($this) {
            self::Disponible => 'Disponible',
            self::Reserve => 'Réservé',
            self::Donne => 'Donné',
            self::Recycle => 'Recyclé',
        };
    }

    /**
     * Couleur du badge (nom de couleur commun à Bootstrap et Vuetify).
     */
    public function color(): string
    {
        return match ($this) {
            self::Disponible => 'success',
            self::Reserve => 'warning',
            self::Donne => 'info',
            self::Recycle => 'secondary',
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
