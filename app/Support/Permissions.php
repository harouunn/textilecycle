<?php

namespace App\Support;

/**
 * Catalogue des permissions Spatie du projet, par rôle.
 * Le rôle de chaque compte reste dans users.role (App\Enums\Role) ; le rôle Spatie est synchronisé
 * automatiquement à chaque enregistrement du compte (voir User::booted()).
 */
final class Permissions
{
    // Back office
    public const ADMINISTRATION = 'acceder administration';

    public const GERER_UTILISATEURS = 'gerer utilisateurs';

    public const MODERER_DEPOTS = 'moderer depots';

    public const GERER_CATEGORIES = 'gerer categories';

    public const GERER_RESERVATIONS = 'gerer reservations';

    public const VOIR_STATISTIQUES = 'voir statistiques';

    public const EXPORTER = 'exporter donnees';

    public const GERER_ATELIERS = 'gerer ateliers';

    public const MODERER_UPCYCLING = 'moderer upcycling';

    public const GERER_DONS = 'gerer dons et associations';

    // Site public
    public const DEPOSER = 'deposer vetements';

    public const RESERVER = 'reserver vetements';

    public const DEMANDER_REPARATION = 'demander reparation';

    public const PROPOSER_UPCYCLING = 'proposer projets upcycling';

    public const FAIRE_DON = 'faire dons';

    private const CLIENT = [self::DEPOSER, self::RESERVER, self::DEMANDER_REPARATION, self::PROPOSER_UPCYCLING, self::FAIRE_DON];

    /** @var array<string, list<string>> */
    public const PAR_ROLE = [
        'admin' => [
            self::ADMINISTRATION, self::GERER_UTILISATEURS, self::MODERER_DEPOTS, self::GERER_CATEGORIES,
            self::GERER_RESERVATIONS, self::VOIR_STATISTIQUES, self::EXPORTER, self::GERER_ATELIERS,
            self::MODERER_UPCYCLING, self::GERER_DONS, ...self::CLIENT,
        ],
        'client' => self::CLIENT,
    ];

    /**
     * @return list<string>
     */
    public static function toutes(): array
    {
        return array_values(array_unique(array_merge(...array_values(self::PAR_ROLE))));
    }
}
