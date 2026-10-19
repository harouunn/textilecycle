<?php

namespace Database\Factories;

use App\Enums\Depot\Creneau;
use App\Enums\Depot\StatutReservation;
use App\Enums\Depot\StatutVetement;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vetement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * Define the model's default state. Le vêtement est créé « Réservé », comme après une vraie réservation.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vetement_id' => Vetement::factory()->statut(StatutVetement::Reserve),
            'user_id' => User::factory(),
            'date_retrait' => fake()->dateTimeBetween('+1 day', '+14 days')->format('Y-m-d'),
            'creneau' => fake()->randomElement(Creneau::cases()),
            'telephone' => fake()->numerify('## ### ###'),
            'message' => fake()->optional()->randomElement([
                'Je passerai avec un sac, merci !',
                'Est-il possible de l\'essayer sur place ?',
                'Je viendrai plutôt en fin de matinée.',
            ]),
            'statut' => StatutReservation::EnAttente,
        ];
    }

    public function confirmee(): static
    {
        return $this->state(fn (array $attributes) => ['statut' => StatutReservation::Confirmee, 'confirmee_le' => now()]);
    }

    public function retiree(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => StatutReservation::Retiree,
            'confirmee_le' => now()->subDay(),
            'retiree_le' => now(),
        ]);
    }

    public function annulee(string $motif = 'Le client n\'est pas venu.'): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => StatutReservation::Annulee,
            'annulee_le' => now(),
            'motif_annulation' => $motif,
        ]);
    }
}
