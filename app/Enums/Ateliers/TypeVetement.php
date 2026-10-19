<?php

namespace App\Enums\Ateliers;

enum TypeVetement: string
{
    case Haut = 'haut';
    case Pantalon = 'pantalon';
    case RobeJupe = 'robe_jupe';
    case Maille = 'maille';
    case VesteManteau = 'veste_manteau';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Haut => 'Haut (t-shirt, chemise…)',
            self::Pantalon => 'Pantalon / jean',
            self::RobeJupe => 'Robe / jupe',
            self::Maille => 'Pull / maille',
            self::VesteManteau => 'Veste / manteau',
            self::Autre => 'Autre',
        };
    }

    /**
     * Multiplicateur du coût : une veste doublée demande plus de travail qu'un t-shirt.
     */
    public function coefficient(): float
    {
        return match ($this) {
            self::RobeJupe, self::Maille => 1.2,
            self::VesteManteau => 1.5,
            default => 1.0,
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
