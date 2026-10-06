<?php

namespace Database\Factories;

use App\Models\EtapeProjet;
use App\Models\ProjetUpcycling;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Le numéro est unique par projet : utilisez ->sequence() pour créer plusieurs étapes d'un même projet.
 *
 * @extends Factory<EtapeProjet>
 */
class EtapeProjetFactory extends Factory
{
    protected $model = EtapeProjet::class;

    public function definition(): array
    {
        return [
            'projet_upcycling_id' => ProjetUpcycling::factory(),
            'numero' => 1,
            'titre' => fake()->randomElement([
                'Préparer le vêtement', 'Découper les pièces', 'Assembler', 'Coudre les bords',
                'Ajouter les finitions', 'Repasser', 'Poser les anses',
            ]),
            'contenu' => fake('fr_FR')->paragraph(3),
            'photo' => null,
        ];
    }
}
