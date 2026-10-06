<?php

namespace App\Enums\Depot;

enum Etat: string
{
    case Neuf = 'neuf';
    case TresBon = 'tres_bon';
    case Bon = 'bon';
    case Use = 'use';
    case AReparer = 'a_reparer';

    public function label(): string
    {
        return match ($this) {
            self::Neuf => 'Neuf',
            self::TresBon => 'Très bon état',
            self::Bon => 'Bon état',
            self::Use => 'Usé',
            self::AReparer => 'À réparer',
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
