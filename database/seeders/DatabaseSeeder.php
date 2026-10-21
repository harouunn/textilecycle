<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Données de démonstration complètes des 4 gestions, avec photos.
     *   php artisan migrate:fresh --seed
     *
     * Comptes (mot de passe : password) :
     *  - test@example.com   : administrateur (back office /admin)
     *  - client@example.com : client (site public) ; aussi ines@, youssef@ et salma@example.com
     *
     * À ne jamais lancer en production (comptes au mot de passe connu).
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            DepotSeeder::class,       // catégories, vêtements avec photos, réservations
            AtelierSeeder::class,     // ateliers et demandes de réparation
            UpcyclingSeeder::class,   // projets d'upcycling et leurs étapes
            DonsSeeder::class,        // associations, campagnes, créneaux et dons
            DemonstrationSeeder::class, // liens entre les gestions et photos manquantes
        ]);
    }
}
