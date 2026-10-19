<?php

namespace Database\Seeders;

use App\Enums\Ateliers\StatutDemande;
use App\Models\Atelier;
use App\Models\DemandeReparation;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Module « Ateliers & réparations » : 10 ateliers et 15 demandes de réparation diagnostiquées.
 *
 * php artisan db:seed --class=AtelierSeeder
 */
class AtelierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ateliers = Atelier::factory()->count(10)->create();
        $actifs = $ateliers->where('actif', true)->values();
        if ($actifs->isEmpty()) {
            $ateliers->first()->update(['actif' => true]);
            $actifs = collect([$ateliers->first()]);
        }

        $clients = User::query()->exists()
            ? User::query()->get()
            : User::factory(3)->create();

        // Une par une, pour que chaque diagnostic tienne compte de la charge déjà créée.
        foreach (range(0, 14) as $index) {
            DemandeReparation::factory()->create([
                'atelier_id' => $actifs[$index % $actifs->count()]->id,
                'user_id' => $clients->random()->id,
                'statut' => $index < 9 ? StatutDemande::EnAttente : fake()->randomElement(StatutDemande::cases()),
            ]);
        }
    }
}
