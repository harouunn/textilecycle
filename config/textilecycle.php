<?php

/*
|--------------------------------------------------------------------------
| TexTileCycle — réglages métier partagés
|--------------------------------------------------------------------------
|
| Chaque module range ses réglages sous sa propre clé.
|
*/

return [

    'dons' => [

        /*
         * Impact environnemental d'un kilo de textile donné puis réutilisé (au lieu d'un vêtement neuf produit).
         *
         * ⚠ VALEURS INDICATIVES À SOURCER AVANT LA MISE EN PRODUCTION.
         * Les ordres de grandeur publiés varient beaucoup selon la fibre, le pays de production et la méthode
         * (analyse de cycle de vie). Remplacez ces valeurs par celles de l'étude retenue par l'équipe et citez-la ici :
         *   Source : <titre de l'étude, organisme, année, page/tableau> — à compléter.
         * Pistes à vérifier : base Empreinte de l'ADEME, rapports WRAP (Royaume-Uni) sur la réutilisation textile.
         */
        'impact' => [
            'co2_kg_par_kg' => (float) env('DONS_CO2_KG_PAR_KG', 15.0),
            'eau_litres_par_kg' => (float) env('DONS_EAU_LITRES_PAR_KG', 6000),
        ],

        /*
         * Gamification : points gagnés par kilo reçu par une association, et paliers des badges.
         */
        'points_par_kg' => (int) env('DONS_POINTS_PAR_KG', 10),

        'badges' => [
            'premier_don' => ['titre' => 'Premier don', 'description' => 'Un premier don reçu par une association.', 'dons' => 1],
            'dix_kg' => ['titre' => '10 kg', 'description' => '10 kg de textiles donnés.', 'kg' => 10],
            'cinquante_kg' => ['titre' => '50 kg', 'description' => '50 kg de textiles donnés.', 'kg' => 50],
            'fidele' => ['titre' => 'Fidèle', 'description' => '5 dons reçus par des associations.', 'dons' => 5],
        ],

        // Durée du cache des statistiques (tableau de bord, impact global, classement), en secondes.
        'cache_ttl' => 600,

        // Rayon par défaut du filtre « près de moi », en kilomètres.
        'rayon_km' => 10,
    ],

];
