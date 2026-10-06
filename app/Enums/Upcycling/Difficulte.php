<?php

namespace App\Enums\Upcycling;

enum Difficulte: string
{
    case Facile = 'facile';
    case Moyen = 'moyen';
    case Difficile = 'difficile';

    public function label(): string
    {
        return match ($this) {
            self::Facile => 'Facile',
            self::Moyen => 'Moyen',
            self::Difficile => 'Difficile',
        };
    }

    /** Couleur utilisée pour les badges (success, warning, danger). */
    public function color(): string
    {
        return match ($this) {
            self::Facile => 'success',
            self::Moyen => 'warning',
            self::Difficile => 'danger',
        };
    }

    /** @return array<string, string> valeur => libellé */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
