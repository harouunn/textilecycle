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
            'couleur' => fake()->randomElement(['#3d7a4f', '#8a5a44', '#4a6fa5', '#b5838d', '#6d597a']),
            'ordre' => fake()->numberBetween(1, 50),
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['active' => false]);
    }
}
