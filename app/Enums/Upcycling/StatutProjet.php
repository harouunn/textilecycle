<?php

namespace App\Enums\Upcycling;

enum StatutProjet: string
{
    case Brouillon = 'brouillon';
    case Publie = 'publie';

    public function label(): string
    {
        return match ($this) {
            self::Brouillon => 'Brouillon',
            self::Publie => 'Publié',
        };
    }

    /** Couleur utilisée pour les badges (secondary, success). */
    public function color(): string
    {
        return match ($this) {
            self::Brouillon => 'secondary',
            self::Publie => 'success',
        };
    }

    /** @return array<string, string> valeur => libellé */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
