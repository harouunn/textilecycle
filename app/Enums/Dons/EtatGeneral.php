<?php

namespace App\Enums\Dons;

enum EtatGeneral: string
{
    case Bon = 'bon';
    case Moyen = 'moyen';
    case ARecycler = 'a_recycler';

    public function label(): string
    {
        return match ($this) {
            self::Bon => 'Bon état',
            self::Moyen => 'État moyen',
            self::ARecycler => 'À recycler',
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
