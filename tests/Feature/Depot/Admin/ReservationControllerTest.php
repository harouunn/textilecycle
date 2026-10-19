<?php

namespace Tests\Feature\Depot\Admin;

use App\Enums\Depot\StatutReservation;
use App\Enums\Depot\StatutVetement;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vetement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReservationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.depot.reservations.index'))->assertRedirect(route('login'));
    }

    public function test_index_shows_reservations_with_counters_and_menu_badge(): void
    {
        Reservation::factory()->create(['vetement_id' => Vetement::factory()->statut(StatutVetement::Reserve)->create(['titre' => 'Robe fleurie'])]);
        Reservation::factory()->confirmee()->create();

        $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.depot.reservations.index'));

        $response->assertOk();
        $response->assertSeeText('Robe fleurie');
        $response->assertSeeTextInOrder(['Réservations', '1']);
        $response->assertSeeTextInOrder(['2', 'Toutes', '1', 'En attente', '1', 'Confirmées']);
    }

    /**
     * @return array<string, array{0: array<string, string>}>
     */
    public static function filtres(): array
    {
        return [
            'statut' => [['statut' => 'confirmee']],
            'recherche par vêtement' => [['search' => 'Manteau']],
        ];
    }

    /**
     * @param  array<string, string>  $filtre
     */
    #[DataProvider('filtres')]
    public function test_index_filters_reservations(array $filtre): void
    {
        Reservation::factory()->confirmee()->create(['vetement_id' => Vetement::factory()->statut(StatutVetement::Reserve)->create(['titre' => 'Manteau long'])]);
        Reservation::factory()->create(['vetement_id' => Vetement::factory()->statut(StatutVetement::Reserve)->create(['titre' => 'Short court'])]);

        $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.depot.reservations.index', $filtre));

        $response->assertSeeText('Manteau long');
        $response->assertDontSeeText('Short court');
    }

    public function test_show_displays_the_client_and_the_actions(): void
    {
        $reservation = Reservation::factory()->for(User::factory()->create(['name' => 'Sana Trabelsi']))->create(['telephone' => '98 765 432']);

        $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.depot.reservations.show', $reservation));

        $response->assertOk();
        $response->assertSeeText(['Sana Trabelsi', '98 765 432', 'Confirmer le créneau', 'Marquer comme retiré']);
    }

    public function test_full_cycle_confirm_then_pick_up_gives_the_clothing(): void
    {
        $admin = User::factory()->admin()->create();
        $reservation = Reservation::factory()->create();

        $this->actingAs($admin)->patch(route('admin.depot.reservations.confirmer', $reservation))
            ->assertRedirect(route('admin.depot.reservations.show', $reservation));
        $this->assertSame(StatutReservation::Confirmee, $reservation->fresh()->statut);
        $this->assertNotNull($reservation->fresh()->confirmee_le);

        $this->actingAs($admin)->patch(route('admin.depot.reservations.retirer', $reservation));

        $reservation->refresh();
        $this->assertSame(StatutReservation::Retiree, $reservation->statut);
        $this->assertSame(StatutVetement::Donne, $reservation->vetement->statut);
    }

    public function test_confirming_twice_is_refused(): void
    {
        $reservation = Reservation::factory()->confirmee()->create();

        $this->actingAs(User::factory()->admin()->create())->patch(route('admin.depot.reservations.confirmer', $reservation))
            ->assertSessionHasErrors(['statut' => 'Seule une réservation en attente peut être confirmée.']);
    }

    public function test_admin_cancellation_requires_a_reason_and_frees_the_clothing(): void
    {
        $admin = User::factory()->admin()->create();
        $reservation = Reservation::factory()->create();

        $this->actingAs($admin)->patch(route('admin.depot.reservations.annuler', $reservation), ['motif_annulation' => ''])
            ->assertSessionHasErrors(['motif_annulation' => 'Le champ motif de l\'annulation est obligatoire.']);

        $this->actingAs($admin)->patch(route('admin.depot.reservations.annuler', $reservation), ['motif_annulation' => 'Client absent.']);

        $reservation->refresh();
        $this->assertSame(StatutReservation::Annulee, $reservation->statut);
        $this->assertSame('Client absent.', $reservation->motif_annulation);
        $this->assertSame(StatutVetement::Disponible, $reservation->vetement->statut);
    }

    public function test_deleting_an_active_reservation_frees_the_clothing(): void
    {
        $reservation = Reservation::factory()->create();
        $vetement = $reservation->vetement;

        $this->actingAs(User::factory()->admin()->create())->delete(route('admin.depot.reservations.destroy', $reservation))
            ->assertRedirect(route('admin.depot.reservations.index'));

        $this->assertModelMissing($reservation);
        $this->assertSame(StatutVetement::Disponible, $vetement->fresh()->statut);
    }

    public function test_clothing_page_lists_its_reservation_history(): void
    {
        $reservation = Reservation::factory()->create();

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.depot.vetements.show', $reservation->vetement))
            ->assertSeeText($reservation->code);
    }

    public function test_deleting_a_clothing_deletes_its_reservations(): void
    {
        $reservation = Reservation::factory()->create();

        $this->actingAs(User::factory()->admin()->create())->delete(route('admin.depot.vetements.destroy', $reservation->vetement));

        $this->assertModelMissing($reservation);
    }
}
