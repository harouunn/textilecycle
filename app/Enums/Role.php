<?php

namespace App\Enums;

/**
 * Rôle d'un compte : l'administrateur gère le back office (/admin), le client utilise le site public.
 */
enum Role: string
{
    case Admin = 'admin';
    case Client = 'client';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrateur',
            self::Client => 'Client',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Admin => 'primary',
            self::Client => 'secondary',
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
