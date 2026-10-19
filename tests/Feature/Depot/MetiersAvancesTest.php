<?php

namespace Tests\Feature\Depot;

use App\Enums\Depot\StatutReservation;
use App\Enums\Depot\StatutVetement;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Vetement;
use App\Notifications\RappelRetraitReservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MetiersAvancesTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_task_expires_overdue_reservations_and_frees_the_clothes(): void
    {
        Notification::fake();
        $enRetard = Reservation::factory()->confirmee()->create(['date_retrait' => today()->subDay()]);
        $aVenir = Reservation::factory()->create(['date_retrait' => today()->addDays(3)]);
        $retiree = Reservation::factory()->retiree()->create(['date_retrait' => today()->subDays(5)]);

        $this->artisan('depot:reservations')
            ->expectsOutput('1 réservation(s) expirée(s), 0 rappel(s) envoyé(s).')
            ->assertSuccessful();

        $this->assertSame(StatutReservation::Annulee, $enRetard->fresh()->statut);
        $this->assertStringStartsWith('Expirée', $enRetard->fresh()->motif_annulation);
        $this->assertSame(StatutVetement::Disponible, $enRetard->vetement->fresh()->statut);
        $this->assertSame(StatutReservation::EnAttente, $aVenir->fresh()->statut);
        $this->assertSame(StatutReservation::Retiree, $retiree->fresh()->statut);
    }

    public function test_daily_task_reminds_clients_the_day_before_only_once(): void
    {
        Notification::fake();
        $demain = Reservation::factory()->create(['date_retrait' => today()->addDay()]);
        Reservation::factory()->annulee()->create(['date_retrait' => today()->addDay()]);

        $this->artisan('depot:reservations')->expectsOutput('0 réservation(s) expirée(s), 1 rappel(s) envoyé(s).');
        $this->artisan('depot:reservations')->expectsOutput('0 réservation(s) expirée(s), 0 rappel(s) envoyé(s).');

        Notification::assertSentToTimes($demain->user, RappelRetraitReservation::class, 1);
        $this->assertNotNull($demain->fresh()->rappel_envoye_le);
    }

    public function test_client_downloads_a_pdf_reservation_slip_with_a_qr_code(): void
    {
        $reservation = Reservation::factory()->create();

        $response = $this->actingAs($reservation->user)->get(route('depot.reservations.bon', $reservation));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString("bon-reservation-{$reservation->code}.pdf", $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_reservation_slip_is_private(): void
    {
        $reservation = Reservation::factory()->create();

        $this->actingAs(User::factory()->create())->get(route('depot.reservations.bon', $reservation))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('depot.reservations.bon', $reservation))->assertOk();
    }

    public function test_admin_exports_clothes_as_csv_for_excel_with_the_current_filters(): void
    {
        Vetement::factory()->create(['titre' => 'Chemise exportée', 'statut' => StatutVetement::Disponible]);
        Vetement::factory()->create(['titre' => 'Robe donnée', 'statut' => StatutVetement::Donne]);

        $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.depot.export.vetements', ['statut' => 'disponible']));

        $csv = $response->streamedContent();
        $response->assertOk();
        $this->assertStringStartsWith("\xEF\xBB\xBFN°;Titre;Catégorie", $csv);
        $this->assertStringContainsString('Chemise exportée', $csv);
        $this->assertStringNotContainsString('Robe donnée', $csv);
    }

    public function test_admin_exports_reservations_and_clients_cannot(): void
    {
        $reservation = Reservation::factory()->create();

        $csv = $this->actingAs(User::factory()->admin()->create())->get(route('admin.depot.export.reservations'))->streamedContent();
        $this->assertStringContainsString($reservation->code, $csv);

        $this->actingAs(User::factory()->create())->get(route('admin.depot.export.reservations'))->assertForbidden();
    }
}
