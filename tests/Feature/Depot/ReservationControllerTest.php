<?php

namespace Tests\Feature\Depot;

use App\Enums\Depot\StatutReservation;
use App\Enums\Depot\StatutVetement;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vetement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $vetement = Vetement::factory()->create();

        $this->get(route('depot.reservations.create', $vetement))->assertRedirect(route('login'));
        $this->post(route('depot.reservations.store', $vetement), $this->payload())->assertRedirect(route('login'));
        $this->get(route('depot.reservations.index'))->assertRedirect(route('login'));
    }

    public function test_catalogue_page_offers_to_reserve_an_available_clothing(): void
    {
        $vetement = Vetement::factory()->create();

        $this->actingAs(User::factory()->create())->get(route('depot.catalogue.show', $vetement))
            ->assertSeeText('Réserver ce vêtement');
    }

    public function test_client_reserves_a_clothing_which_leaves_the_catalogue(): void
    {
        $client = User::factory()->create();
        $vetement = Vetement::factory()->create(['titre' => 'Veste en jean']);

        $response = $this->actingAs($client)->post(route('depot.reservations.store', $vetement), $this->payload());

        $reservation = Reservation::query()->sole();
        $response->assertRedirect(route('depot.reservations.index'));
        $response->assertSessionHas('success', "Réservation {$reservation->code} enregistrée pour « Veste en jean ». "
            .'Retrait le '.today()->addDays(3)->format('d/m/Y').', Matin (9h – 12h).');
        $this->assertSame($client->id, $reservation->user_id);
        $this->assertSame(StatutReservation::EnAttente, $reservation->statut);
        $this->assertMatchesRegularExpression('/^RES-[A-Z0-9]{6}$/', $reservation->code);
        $this->assertSame(StatutVetement::Reserve, $vetement->fresh()->statut);

        // La page « Mes réservations » consomme le message flash, qui cite le titre.
        $this->get(route('depot.reservations.index'))->assertSeeText('Veste en jean');
        $this->get(route('depot.catalogue.index'))->assertDontSeeText('Veste en jean');
    }

    public function test_a_clothing_cannot_be_reserved_twice(): void
    {
        $vetement = Vetement::factory()->create();
        $this->actingAs(User::factory()->create())->post(route('depot.reservations.store', $vetement), $this->payload());

        $response = $this->actingAs(User::factory()->create())->post(route('depot.reservations.store', $vetement), $this->payload());

        $response->assertSessionHasErrors(['vetement' => 'Ce vêtement n\'est plus disponible à la réservation.']);
        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_depositor_cannot_reserve_their_own_clothing(): void
    {
        $deposant = User::factory()->create();
        $vetement = Vetement::factory()->for($deposant)->create();

        $this->actingAs($deposant)->get(route('depot.reservations.create', $vetement))
            ->assertRedirect(route('depot.catalogue.show', $vetement));
        $this->actingAs($deposant)->post(route('depot.reservations.store', $vetement), $this->payload())
            ->assertSessionHasErrors(['vetement' => 'Vous ne pouvez pas réserver un vêtement que vous avez déposé.']);

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_pending_clothing_cannot_be_reserved(): void
    {
        $vetement = Vetement::factory()->enAttente()->create();

        $this->actingAs(User::factory()->create())->post(route('depot.reservations.store', $vetement), $this->payload())
            ->assertSessionHasErrors('vetement');

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_invalid_reservation_is_rejected_with_french_messages(): void
    {
        $vetement = Vetement::factory()->create();

        $response = $this->actingAs(User::factory()->create())->post(route('depot.reservations.store', $vetement), [
            'date_retrait' => today()->addDays(30)->toDateString(),
            'creneau' => 'nuit',
            'telephone' => 'abc',
        ]);

        $response->assertSessionHasErrors([
            'date_retrait' => 'Le retrait doit avoir lieu dans les 14 prochains jours.',
            'creneau' => 'La valeur sélectionnée pour créneau est invalide.',
            'telephone' => 'Le numéro de téléphone n\'est pas valide (ex. 22 123 456).',
        ]);
        $this->assertSame(StatutVetement::Disponible, $vetement->fresh()->statut);
    }

    public function test_index_lists_only_the_clients_reservations(): void
    {
        $client = User::factory()->create();
        Reservation::factory()->for($client)->create(['vetement_id' => Vetement::factory()->create(['titre' => 'Mon pull'])]);
        Reservation::factory()->create(['vetement_id' => Vetement::factory()->create(['titre' => 'Pull du voisin'])]);

        $response = $this->actingAs($client)->get(route('depot.reservations.index'));

        $response->assertSeeText('Mon pull');
        $response->assertDontSeeText('Pull du voisin');
    }

    public function test_client_cancels_their_reservation_and_the_clothing_comes_back(): void
    {
        $client = User::factory()->create();
        $reservation = Reservation::factory()->for($client)->create();

        $this->actingAs($client)->patch(route('depot.reservations.annuler', $reservation))
            ->assertRedirect(route('depot.reservations.index'));

        $reservation->refresh();
        $this->assertSame(StatutReservation::Annulee, $reservation->statut);
        $this->assertSame('Annulée par le client.', $reservation->motif_annulation);
        $this->assertSame(StatutVetement::Disponible, $reservation->vetement->statut);
    }

    public function test_client_cannot_cancel_someone_elses_or_a_finished_reservation(): void
    {
        $client = User::factory()->create();
        $autre = Reservation::factory()->create();
        $retiree = Reservation::factory()->for($client)->retiree()->create();

        $this->actingAs($client)->patch(route('depot.reservations.annuler', $autre))->assertForbidden();
        $this->actingAs($client)->patch(route('depot.reservations.annuler', $retiree))->assertForbidden();

        $this->assertSame(StatutReservation::EnAttente, $autre->fresh()->statut);
    }

    public function test_reserved_clothing_is_locked_for_its_depositor(): void
    {
        $deposant = User::factory()->create();
        $vetement = Vetement::factory()->for($deposant)->statut(StatutVetement::Reserve)->create();

        $this->actingAs($deposant)->get(route('depot.mes-depots.edit', $vetement))->assertForbidden();
        $this->actingAs($deposant)->delete(route('depot.mes-depots.destroy', $vetement))->assertForbidden();
        $this->actingAs($deposant)->get(route('depot.mes-depots.index'))->assertSeeText('Verrouillé');
    }

    /**
     * @return array<string, string>
     */
    private function payload(): array
    {
        return [
            'date_retrait' => today()->addDays(3)->toDateString(),
            'creneau' => 'matin',
            'telephone' => '22 123 456',
            'message' => 'Je passerai avec un sac.',
        ];
    }
}
