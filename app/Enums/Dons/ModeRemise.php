<?php

namespace App\Enums\Dons;

enum ModeRemise: string
{
    case DepotSurPlace = 'depot_sur_place';
    case CollecteADomicile = 'collecte_a_domicile';

    public function label(): string
    {
        return match ($this) {
            self::DepotSurPlace => 'Dépôt sur place',
            self::CollecteADomicile => 'Collecte à domicile',
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
