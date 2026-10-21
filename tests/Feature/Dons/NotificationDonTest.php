<?php

namespace Tests\Feature\Dons;

use App\Enums\Dons\DonStatut;
use App\Models\Association;
use App\Models\Don;
use App\Models\User;
use App\Notifications\Dons\DonPourAssociation;
use App\Notifications\Dons\StatutDonModifie;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NotificationDonTest extends TestCase
{
    use RefreshDatabase;

    private User $donateur;

    private User $responsable;

    private Association $association;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->donateur = User::factory()->create();
        $this->responsable = User::factory()->create();
        $this->association = Association::factory()->gereePar($this->responsable)->create();
    }

    private function proposer(): Don
    {
        $this->actingAs($this->donateur)->post(route('dons.store', $this->association), [
            'type_article' => 'chaussures', 'quantite' => 2, 'poids_kg' => 3, 'etat_general' => 'bon',
            'mode_remise' => 'depot_sur_place', 'date_remise' => today()->addDay()->toDateString(),
        ])->assertSessionHasNoErrors();

        return Don::query()->latest('id')->first();
    }

    public function test_new_donation_notifies_the_association_responsable(): void
    {
        Notification::fake();

        $don = $this->proposer();

        Notification::assertSentTo($this->responsable, DonPourAssociation::class,
            fn (DonPourAssociation $n, array $canaux) => $n->don->is($don) && $n->statut === DonStatut::Propose && $canaux === ['mail', 'database']);
        Notification::assertNotSentTo($this->donateur, StatutDonModifie::class);
    }

    public function test_status_change_notifies_the_donor_by_mail_and_database(): void
    {
        Notification::fake();
        $don = $this->proposer();

        $this->actingAs($this->responsable)->patch(route('dons.espace.statut', $don), ['statut' => 'accepte', 'commentaire' => 'Merci, à mardi !']);

        Notification::assertSentTo($this->donateur, StatutDonModifie::class, function (StatutDonModifie $n, array $canaux) {
            $mail = $n->toMail($this->donateur);

            return $n->nouveau === DonStatut::Accepte
                && $canaux === ['mail', 'database']
                && str_contains(implode(' ', $mail->introLines), 'Merci, à mardi !');
        });
    }

    public function test_donor_cancellation_notifies_the_responsable_not_the_donor(): void
    {
        $don = $this->proposer();
        Notification::fake();

        $this->actingAs($this->donateur)->patch(route('dons.cancel', $don));

        Notification::assertSentTo($this->responsable, DonPourAssociation::class, fn (DonPourAssociation $n) => $n->statut === DonStatut::Annule);
        Notification::assertNothingSentTo($this->donateur);
    }

    public function test_notifications_are_queued(): void
    {
        $this->assertInstanceOf(ShouldQueue::class, new StatutDonModifie(Don::factory()->create(), null, DonStatut::Accepte));
        $this->assertInstanceOf(ShouldQueue::class, new DonPourAssociation(Don::factory()->create(), DonStatut::Propose));
    }

    public function test_bell_shows_unread_count_and_notifications_can_be_read(): void
    {
        // File d'attente « sync » en test : la notification est enregistrée tout de suite.
        $don = $this->proposer();
        $this->actingAs($this->responsable)->patch(route('dons.espace.statut', $don), ['statut' => 'accepte']);

        $this->actingAs($this->donateur)->get(route('dons.index'))
            ->assertOk()
            ->assertSee('Notifications des dons : 1 non lue(s)', false);

        $notification = $this->donateur->notifications()->sole();
        $this->actingAs($this->donateur)->get(route('dons.notifications.index'))->assertOk()->assertSeeText('a accepté votre don');
        $this->actingAs($this->donateur)->get(route('dons.notifications.ouvrir', $notification->id))
            ->assertRedirect(route('dons.mes-dons.show', $don));

        $this->assertNotNull($notification->fresh()->read_at);
        $this->actingAs($this->donateur)->get(route('dons.index'))->assertDontSee('non lue(s)');
    }

    public function test_users_cannot_open_someone_elses_notification(): void
    {
        $this->proposer();
        $notification = $this->responsable->notifications()->sole();

        $this->actingAs($this->donateur)->get(route('dons.notifications.ouvrir', $notification->id))->assertNotFound();
    }

    public function test_mark_all_as_read(): void
    {
        $this->proposer();
        $this->proposer();
        $this->assertSame(2, $this->responsable->unreadNotifications()->count());

        $this->actingAs($this->responsable)->post(route('dons.notifications.tout-lire'))->assertRedirect();

        $this->assertSame(0, $this->responsable->unreadNotifications()->count());
    }
}
