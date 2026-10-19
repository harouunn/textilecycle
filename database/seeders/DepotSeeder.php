<?php

namespace Database\Seeders;

use App\Enums\Depot\Moderation;
use App\Enums\Depot\StatutVetement;
use App\Models\Categorie;
use App\Models\User;
use App\Models\Vetement;
use App\Services\Depot\GestionReservations;
use Illuminate\Database\Seeder;

/**
 * Module « Dépôt & vêtements » : 6 catégories, 20 vêtements (dont 3 en attente de validation)
 * et 4 réservations à différentes étapes (en attente, confirmée, retirée, annulée).
 *
 * php artisan db:seed --class=DepotSeeder
 */
class DepotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(GestionReservations $reservations): void
    {
        $categories = collect([
            ['nom' => 'Hauts', 'description' => 'T-shirts, chemises, blouses et tops.', 'icone' => 'bx-shopping-bag', 'couleur' => '#3d7a4f'],
            ['nom' => 'Pantalons & jeans', 'description' => 'Pantalons, jeans, shorts et leggings.', 'icone' => 'bx-closet', 'couleur' => '#4a6fa5'],
            ['nom' => 'Robes & jupes', 'description' => 'Robes, jupes et combinaisons.', 'icone' => 'bx-star', 'couleur' => '#b5838d'],
            ['nom' => 'Pulls & gilets', 'description' => 'Pulls, sweats, gilets et cardigans.', 'icone' => 'bx-wind', 'couleur' => '#8a5a44'],
            ['nom' => 'Vestes & manteaux', 'description' => 'Vestes, blousons, manteaux et imperméables.', 'icone' => 'bx-cloud-rain', 'couleur' => '#6d597a'],
            ['nom' => 'Enfants', 'description' => 'Vêtements pour bébés et enfants.', 'icone' => 'bx-happy', 'couleur' => '#d08c3c'],
        ])->map(fn (array $categorie, int $index) => Categorie::query()->updateOrCreate(
            ['nom' => $categorie['nom']],
            [...$categorie, 'ordre' => $index + 1, 'active' => true],
        ));

        // Au moins deux comptes : un déposant ne peut pas réserver son propre vêtement.
        $utilisateurs = User::query()->get();
        if ($utilisateurs->count() < 2) {
            $utilisateurs = $utilisateurs->concat(User::factory(2)->create());
        }

        $vetements = Vetement::factory(20)
            ->sequence(fn ($sequence) => [
                'categorie_id' => $categories[$sequence->index % $categories->count()]->id,
                'user_id' => $utilisateurs->random()->id,
                'statut' => $sequence->index < 14 ? StatutVetement::Disponible : fake()->randomElement([StatutVetement::Donne, StatutVetement::Recycle]),
                // Les 3 derniers attendent la validation de l'admin
                'moderation' => $sequence->index >= 17 ? Moderation::EnAttente : Moderation::Approuve,
            ])
            ->create();

        // Réservations passées par le vrai circuit métier (le statut des vêtements suit).
        foreach ($vetements->take(4) as $index => $vetement) {
            $client = $utilisateurs->firstWhere(fn (User $user) => $user->id !== $vetement->user_id);
            $reservation = $reservations->reserver($vetement, $client, [
                'date_retrait' => today()->addDays($index + 2)->toDateString(),
                'creneau' => $index % 2 ? 'apres_midi' : 'matin',
                'telephone' => '22 '.random_int(100, 999).' '.random_int(100, 999),
                'message' => $index === 0 ? 'Je passerai avec un sac, merci !' : null,
            ]);

            match ($index) {
                1 => $reservations->confirmer($reservation),
                2 => $reservations->marquerRetiree($reservation),
                3 => $reservations->annuler($reservation, 'Le client n\'est pas venu au rendez-vous.'),
                default => null,
            };
        }
    }
}
