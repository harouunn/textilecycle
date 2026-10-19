<?php

namespace App\Enums\Depot;

/**
 * Créneau de retrait d'un vêtement réservé.
 */
enum Creneau: string
{
    case Matin = 'matin';
    case ApresMidi = 'apres_midi';

    public function label(): string
    {
        return match ($this) {
            self::Matin => 'Matin (9h – 12h)',
            self::ApresMidi => 'Après-midi (14h – 18h)',
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
