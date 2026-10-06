<?php

namespace Database\Factories;

use App\Enums\Upcycling\Difficulte;
use App\Enums\Upcycling\StatutProjet;
use App\Models\ProjetUpcycling;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjetUpcycling>
 */
class ProjetUpcyclingFactory extends Factory
{
    protected $model = ProjetUpcycling::class;

    public function definition(): array
    {
        $transformation = fake()->randomElement([
            ['vieux jean', 'sac cabas'],
            ['chemise en lin', 'tablier de cuisine'],
            ['t-shirts usés', 'tapis tressé'],
            ['pull en laine', 'coussin douillet'],
            ['drap en coton', 'sacs à vrac'],
            ['robe d\'été', 'jupe portefeuille'],
            ['cravates', 'pochette patchwork'],
            ['jean troué', 'short frangé'],
        ]);

        return [
            'user_id' => User::factory(),
            'titre' => ucfirst($transformation[1]).' upcyclé',
            'description' => fake('fr_FR')->paragraphs(2, true),
            'vetement_origine' => $transformation[0],
            'resultat' => $transformation[1],
            'difficulte' => fake()->randomElement(Difficulte::cases()),
            'duree_minutes' => fake()->randomElement([30, 45, 60, 90, 120, 180, 240]),
            'materiel_necessaire' => implode("\n", fake()->randomElements([
                'Ciseaux de couture', 'Machine à coudre', 'Fil assorti', 'Épingles', 'Craie de tailleur',
                'Mètre ruban', 'Fer à repasser', 'Aiguille à main', 'Biais', 'Boutons pression',
            ], 4)),
            'photo_avant' => null,
            'photo_apres' => null,
            'statut' => StatutProjet::Publie,
        ];
    }

    public function brouillon(): static
    {
        return $this->state(fn () => ['statut' => StatutProjet::Brouillon]);
    }
}
