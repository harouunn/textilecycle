<?php

namespace Database\Factories;

use App\Enums\Ateliers\StatutDemande;
use App\Enums\Ateliers\TypeVetement;
use App\Models\Atelier;
use App\Models\DemandeReparation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DemandeReparation>
 */
class DemandeReparationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$titre, $description] = fake()->randomElement([
            ['Jean troué au genou', 'Un trou au genou gauche, environ 3 cm.'],
            ['Chemise sans bouton', 'Il manque deux boutons sur le devant.'],
            ['Pantalon trop long', "Il faudrait raccourcir l'ourlet de 4 cm."],
            ['Veste à la fermeture cassée', 'La fermeture éclair ne remonte plus.'],
            ['Robe décousue', 'La couture du côté droit est décousue sur 10 cm.'],
            ['Manteau à la doublure déchirée', 'La doublure est déchirée sous le bras.'],
            ['Pull à ajuster', 'Pull trop large, à reprendre sur les côtés.'],
        ]);

        return [
            'atelier_id' => Atelier::factory()->state(['actif' => true]),
            'user_id' => User::factory(),
            'titre' => $titre,
            'type_vetement' => fake()->randomElement(TypeVetement::cases()),
            'description' => $description,
            'photo' => null,
            'statut' => StatutDemande::EnAttente,
        ];
    }

    /**
     * Le diagnostic est calculé comme dans l'application, sauf si le test le fournit.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (DemandeReparation $demande) {
            if ($demande->cout_estime === null) {
                $demande->diagnostiquer();
            }
        });
    }

    public function statut(StatutDemande $statut): static
    {
        return $this->state(fn (array $attributes) => ['statut' => $statut]);
    }
}
