<?php

namespace Database\Factories;

use App\Enums\Dons\StatutCampagne;
use App\Enums\Dons\TypeArticle;
use App\Models\Association;
use App\Models\CampagneCollecte;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampagneCollecte>
 */
class CampagneCollecteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'association_id' => Association::factory(),
            'titre' => fake()->randomElement(['Grand froid', 'Rentrée scolaire', 'Linge pour les foyers', 'Tenues d\'entretien', 'Chaussures solidaires']).' '.fake()->year(),
            'description' => 'Collecte organisée pour répondre aux besoins les plus urgents des familles accompagnées par l\'association.',
            'type_articles_cibles' => fake()->randomElements(array_map(fn (TypeArticle $t) => $t->value, TypeArticle::cases()), 2),
            'objectif_kg' => fake()->randomElement([50, 100, 200, 500]),
            'date_debut' => today()->subDays(7),
            'date_fin' => today()->addDays(30),
            'statut' => StatutCampagne::Active,
            'image' => null,
        ];
    }

    public function brouillon(): static
    {
        return $this->state(fn () => ['statut' => StatutCampagne::Brouillon]);
    }

    public function expiree(): static
    {
        return $this->state(fn () => ['date_debut' => today()->subMonths(2), 'date_fin' => today()->subDay()]);
    }
}
