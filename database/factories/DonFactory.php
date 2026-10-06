<?php

namespace Database\Factories;

use App\Models\Association;
use App\Models\Don;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Don>
 */
class DonFactory extends Factory
{
    public function definition(): array
    {
        $mode = fake()->randomElement(array_keys(Don::MODES));

        return [
            'association_id' => Association::factory(),
            'user_id' => User::factory(),
            'type_article' => fake()->randomElement(array_keys(Don::TYPES)),
            'quantite' => fake()->numberBetween(1, 40),
            'poids_kg' => fake()->optional(0.8)->randomFloat(2, 0.5, 30),
            'etat_general' => fake()->randomElement(array_keys(Don::ETATS)),
            'mode_remise' => $mode,
            'date_remise' => fake()->dateTimeBetween('-2 months', '+1 month')->format('Y-m-d'),
            'adresse_collecte' => $mode === 'collecte_a_domicile' ? fake()->streetAddress().', '.fake()->city() : null,
            'statut' => fake()->randomElement(array_keys(Don::STATUTS)),
            'message' => fake()->optional(0.5)->randomElement([
                'Vêtements lavés et pliés, prêts à être distribués.',
                'Plusieurs tailles, surtout du M et du L.',
                'Je suis disponible en fin de journée pour la remise.',
                'Quelques pièces ont de petits défauts mais restent portables.',
                "Vêtements d'enfants de 4 à 8 ans.",
            ]),
        ];
    }

    public function propose(): static
    {
        return $this->state(fn () => ['statut' => 'propose']);
    }
}
