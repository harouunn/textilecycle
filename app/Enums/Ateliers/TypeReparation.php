<?php

namespace App\Enums\Ateliers;

/**
 * Réparations reconnues par le diagnostic, avec leur tarif et leur délai de base.
 */
enum TypeReparation: string
{
    case Bouton = 'bouton';
    case Couture = 'couture';
    case Ourlet = 'ourlet';
    case Trou = 'trou';
    case Fermeture = 'fermeture';
    case Retouche = 'retouche';
    case Doublure = 'doublure';
    case AExpertiser = 'a_expertiser';

    public function label(): string
    {
        return match ($this) {
            self::Bouton => 'Bouton / pression',
            self::Couture => 'Couture décousue',
            self::Ourlet => 'Ourlet',
            self::Trou => 'Trou / accroc',
            self::Fermeture => 'Fermeture éclair',
            self::Retouche => 'Retouche de taille',
            self::Doublure => 'Doublure',
            self::AExpertiser => 'À expertiser',
        };
    }

    /**
     * Tarif de base en dinars, avant coefficients.
     */
    public function coutBase(): float
    {
        return match ($this) {
            self::Bouton => 5,
            self::Couture => 10,
            self::Ourlet => 12,
            self::Trou => 15,
            self::Fermeture => 18,
            self::Retouche => 25,
            self::Doublure => 30,
            self::AExpertiser => 20,
        };
    }

    /**
     * Délai de base en jours ouvrés.
     */
    public function delaiBase(): int
    {
        return match ($this) {
            self::Bouton => 1,
            self::Couture, self::Ourlet => 2,
            self::Trou, self::Fermeture => 3,
            self::Retouche, self::Doublure, self::AExpertiser => 5,
        };
    }

    /**
     * Mots-clés (minuscules, sans accents) qui signalent cette réparation dans la description.
     *
     * @return list<string>
     */
    public function motsCles(): array
    {
        return match ($this) {
            self::Bouton => ['bouton', 'pression', 'boutonniere'],
            self::Couture => ['decousu', 'decoud', 'couture', 'craque'],
            self::Ourlet => ['ourlet', 'raccourcir', 'trop long', 'rallonger', 'longueur'],
            self::Trou => ['trou', 'troue', 'dechir', 'accroc', 'perce', 'mite'],
            self::Fermeture => ['fermeture', 'zip', 'eclair', 'glissiere', 'braguette'],
            self::Retouche => ['retouche', 'ajuster', 'trop large', 'trop grand', 'trop serre', 'trop petit', 'cintrer', 'reprendre'],
            self::Doublure => ['doublure'],
            self::AExpertiser => [],
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
