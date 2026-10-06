<?php

namespace Database\Seeders;

use App\Enums\Depot\StatutVetement;
use App\Models\Categorie;
use App\Models\User;
use App\Models\Vetement;
use Illuminate\Database\Seeder;

/**
 * Module « Dépôt & vêtements » : 6 catégories et 20 vêtements.
 *
 * php artisan db:seed --class=DepotSeeder
 */
class DepotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = collect([
            ['nom' => 'Hauts', 'description' => 'T-shirts, chemises, blouses et tops.', 'icone' => 'bx-shopping-bag'],
            ['nom' => 'Pantalons & jeans', 'description' => 'Pantalons, jeans, shorts et leggings.', 'icone' => 'bx-closet'],
            ['nom' => 'Robes & jupes', 'description' => 'Robes, jupes et combinaisons.', 'icone' => 'bx-star'],
            ['nom' => 'Pulls & gilets', 'description' => 'Pulls, sweats, gilets et cardigans.', 'icone' => 'bx-wind'],
            ['nom' => 'Vestes & manteaux', 'description' => 'Vestes, blousons, manteaux et imperméables.', 'icone' => 'bx-cloud-rain'],
            ['nom' => 'Enfants', 'description' => 'Vêtements pour bébés et enfants.', 'icone' => 'bx-happy'],
        ])->map(fn (array $categorie) => Categorie::query()->firstOrCreate(['nom' => $categorie['nom']], $categorie));

        $deposants = User::query()->exists()
            ? User::query()->get()
            : User::factory(3)->create();

        Vetement::factory(20)
            ->sequence(fn ($sequence) => [
                'categorie_id' => $categories[$sequence->index % $categories->count()]->id,
                'user_id' => $deposants->random()->id,
                'statut' => $sequence->index < 14 ? StatutVetement::Disponible : fake()->randomElement(StatutVetement::cases()),
            ])
            ->create();
    }
}
