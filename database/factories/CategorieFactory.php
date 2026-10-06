<?php

namespace Database\Factories;

use App\Models\Categorie;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Categorie>
 */
class CategorieFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nom' => ucfirst(fake()->unique()->words(2, true)),
            'description' => 'Vêtements de la catégorie '.fake()->word().'.',
            'icone' => fake()->randomElement(['bx-closet', 'bx-shopping-bag', 'bx-star']),
        ];
    }
}
