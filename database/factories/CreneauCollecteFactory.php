<?php

namespace Database\Factories;

use App\Models\Association;
use App\Models\CreneauCollecte;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreneauCollecte>
 */
class CreneauCollecteFactory extends Factory
{
    public function definition(): array
    {
        $debut = fake()->randomElement(['09:00', '10:00', '14:00', '16:00']);

        return [
            'association_id' => Association::factory(),
            'date' => today()->addDays(fake()->numberBetween(1, 20)),
            'heure_debut' => $debut,
            'heure_fin' => date('H:i', strtotime($debut) + 2 * 3600),
            'capacite_max' => fake()->numberBetween(2, 6),
        ];
    }
}
