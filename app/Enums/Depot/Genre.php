<?php

namespace App\Enums\Depot;

enum Genre: string
{
    case Homme = 'homme';
    case Femme = 'femme';
    case Enfant = 'enfant';
    case Unisexe = 'unisexe';

    public function label(): string
    {
        return match ($this) {
            self::Homme => 'Homme',
            self::Femme => 'Femme',
            self::Enfant => 'Enfant',
            self::Unisexe => 'Unisexe',
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
