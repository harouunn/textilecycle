<?php

namespace App\Enums\Dons;

/**
 * Cycle de vie d'un don :
 *   proposé → accepté → planifié → reçu → distribué
 * Un don peut être refusé par l'association (tant qu'il n'est pas planifié)
 * ou annulé (tant qu'il n'est pas reçu). Distribué, refusé et annulé sont des statuts finaux.
 */
enum DonStatut: string
{
    case Propose = 'propose';
    case Accepte = 'accepte';
    case Planifie = 'planifie';
    case Recu = 'recu';
    case Distribue = 'distribue';
    case Refuse = 'refuse';
    case Annule = 'annule';

    public function label(): string
    {
        return match ($this) {
            self::Propose => 'Proposé',
            self::Accepte => 'Accepté',
            self::Planifie => 'Planifié',
            self::Recu => 'Reçu',
            self::Distribue => 'Distribué',
            self::Refuse => 'Refusé',
            self::Annule => 'Annulé',
        };
    }

    /**
     * Couleur du badge (classes contextuelles Bootstrap, aussi définies par le thème Sneat du back office).
     */
    public function color(): string
    {
        return match ($this) {
            self::Propose => 'warning',
            self::Accepte => 'info',
            self::Planifie => 'primary',
            self::Recu, self::Distribue => 'success',
            self::Refuse => 'danger',
            self::Annule => 'secondary',
        };
    }

    public function icone(): string
    {
        return match ($this) {
            self::Propose => 'bx-send',
            self::Accepte => 'bx-check',
            self::Planifie => 'bx-calendar',
            self::Recu => 'bx-package',
            self::Distribue => 'bx-donate-heart',
            self::Refuse => 'bx-x',
            self::Annule => 'bx-block',
        };
    }

    /**
     * Libellé du bouton qui fait passer un don à ce statut.
     */
    public function action(): string
    {
        return match ($this) {
            self::Propose => 'Remettre en proposition',
            self::Accepte => 'Accepter',
            self::Planifie => 'Planifier la remise',
            self::Recu => 'Marquer comme reçu',
            self::Distribue => 'Marquer comme distribué',
            self::Refuse => 'Refuser',
            self::Annule => 'Annuler le don',
        };
    }

    /**
     * Statuts que l'on peut atteindre depuis celui-ci.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Propose => [self::Accepte, self::Refuse, self::Annule],
            // Un dépôt sur place peut être reçu sans rendez-vous planifié.
            self::Accepte => [self::Planifie, self::Recu, self::Refuse, self::Annule],
            self::Planifie => [self::Recu, self::Annule],
            self::Recu => [self::Distribue],
            self::Distribue, self::Refuse, self::Annule => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
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
