<?php

namespace Database\Factories;

use App\Enums\Depot\Etat;
use App\Enums\Depot\Genre;
use App\Enums\Depot\Moderation;
use App\Enums\Depot\StatutVetement;
use App\Enums\Depot\Taille;
use App\Models\Categorie;
use App\Models\User;
use App\Models\Vetement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vetement>
 */
class VetementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'categorie_id' => Categorie::factory(),
            'user_id' => User::factory(),
            'titre' => fake()->randomElement(['Chemise', 'Jean', 'Robe', 'Pull', 'Veste', 'T-shirt', 'Manteau', 'Jupe'])
                .' '.fake()->randomElement(['en coton', 'en lin', 'en laine', 'vintage', 'à fleurs', 'rayé', 'en denim']),
            'description' => fake()->randomElement([
                'Porté quelques fois, aucun défaut visible. Lavé avant dépôt.',
                'Très confortable, la couleur a un peu passé au lavage.',
                'Petit accroc au niveau de la manche, facilement réparable.',
                'Jamais porté, l\'étiquette est encore attachée.',
                'Coupe ample, idéal pour la mi-saison. Un bouton à recoudre.',
                'Tissu de bonne qualité, quelques bouloches sur les côtés.',
            ]),
            'taille' => fake()->randomElement(Taille::cases()),
            'genre' => fake()->randomElement(Genre::cases()),
            'matiere' => fake()->randomElement(['Coton', 'Lin', 'Laine', 'Polyester', 'Denim', 'Soie', 'Viscose']),
            'etat' => fake()->randomElement(Etat::cases()),
            'photo' => null,
            'statut' => StatutVetement::Disponible,
            'date_depot' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
        ];
    }

    public function statut(StatutVetement $statut): static
    {
        return $this->state(fn (array $attributes) => ['statut' => $statut]);
    }

    public function enAttente(): static
    {
        return $this->state(fn (array $attributes) => ['moderation' => Moderation::EnAttente]);
    }

    public function refuse(string $motif = 'La photo est floue.'): static
    {
        return $this->state(fn (array $attributes) => ['moderation' => Moderation::Refuse, 'motif_refus' => $motif]);
    }
}
