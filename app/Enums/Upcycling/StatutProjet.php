<?php

namespace App\Enums\Upcycling;

enum StatutProjet: string
{
    case Brouillon = 'brouillon';
    case Soumis = 'soumis';
    case Publie = 'publie';
    case Rejete = 'rejete';

    public function label(): string
    {
        return match ($this) {
            self::Brouillon => 'Brouillon', self::Soumis => 'Soumis à validation', self::Publie => 'Publié', self::Rejete => 'Rejeté'
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Brouillon => 'secondary', self::Soumis => 'warning', self::Publie => 'success', self::Rejete => 'error'
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
