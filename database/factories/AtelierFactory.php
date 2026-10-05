<?php

namespace Database\Factories;

use App\Models\Atelier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Atelier>
 */
class AtelierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nom' => fake()->company(),
            'description' => fake()->optional()->paragraph(),
            'adresse' => fake()->streetAddress(),
            'ville' => fake()->city(),
            'code_postal' => fake()->postcode(),
            'telephone' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->companyEmail(),
            'actif' => fake()->boolean(90),
        ];
    }
}
