<?php

namespace App\Enums\Dons;

/**
 * Une campagne est préparée en brouillon, publiée (active), puis terminée
 * (à la main, ou chaque nuit par dons:cloturer-campagnes une fois sa date de fin passée).
 */
enum StatutCampagne: string
{
    case Brouillon = 'brouillon';
    case Active = 'active';
    case Terminee = 'terminee';

    public function label(): string
    {
        return match ($this) {
            self::Brouillon => 'Brouillon',
            self::Active => 'Active',
            self::Terminee => 'Terminée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Brouillon => 'secondary',
            self::Active => 'success',
            self::Terminee => 'info',
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
