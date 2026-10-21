<?php

namespace App\Enums\Dons;

enum TypeArticle: string
{
    case VetementsHomme = 'vetements_homme';
    case VetementsFemme = 'vetements_femme';
    case VetementsEnfant = 'vetements_enfant';
    case Chaussures = 'chaussures';
    case LingeMaison = 'linge_maison';
    case Accessoires = 'accessoires';

    public function label(): string
    {
        return match ($this) {
            self::VetementsHomme => 'Vêtements homme',
            self::VetementsFemme => 'Vêtements femme',
            self::VetementsEnfant => 'Vêtements enfant',
            self::Chaussures => 'Chaussures',
            self::LingeMaison => 'Linge de maison',
            self::Accessoires => 'Accessoires',
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
