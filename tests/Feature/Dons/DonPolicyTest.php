<?php

namespace Tests\Feature\Dons;

use App\Enums\Dons\DonStatut;
use App\Models\Association;
use App\Models\Don;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DonPolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $responsable;

    private Association $association;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->responsable = User::factory()->create();
        $this->association = Association::factory()->gereePar($this->responsable)->create(['nom' => 'Croissant Rouge']);
    }

    private function don(DonStatut $statut = DonStatut::Propose, ?User $donateur = null): Don
    {
        return Don::factory()->statut($statut)->create([
            'association_id' => $this->association->id,
            'user_id' => ($donateur ?? User::factory()->create())->id,
        ]);
    }

    // Donateur

    public function test_donor_sees_the_timeline_of_their_own_donation(): void
    {
        $donateur = User::factory()->create();
        $don = $this->don(donateur: $donateur);

        $this->actingAs($donateur)->get(route('dons.mes-dons.show', $don))
            ->assertOk()
            ->assertSeeText(['Suivi', 'Don proposé', 'Croissant Rouge']);
    }

    public function test_donor_cannot_see_someone_elses_donation(): void
    {
        $this->actingAs(User::factory()->create())->get(route('dons.mes-dons.show', $this->don()))->assertForbidden();
    }

    public function test_donor_cancels_a_proposed_donation(): void
    {
        $donateur = User::factory()->create();
        $don = $this->don(donateur: $donateur);

        $this->actingAs($donateur)->patch(route('dons.cancel', $don))
            ->assertRedirect(route('dons.mes-dons'))
            ->assertSessionHas('success');

        $this->assertSame(DonStatut::Annule, $don->fresh()->statut);
        $this->assertDatabaseHas('don_historiques', ['don_id' => $don->id, 'user_id' => $donateur->id, 'nouveau_statut' => 'annule']);
    }

    public function test_donor_cannot_cancel_once_the_donation_is_accepted(): void
    {
        $donateur = User::factory()->create();
        $don = $this->don(DonStatut::Accepte, $donateur);

        $this->actingAs($donateur)->patch(route('dons.cancel', $don))->assertForbidden();
        $this->assertSame(DonStatut::Accepte, $don->fresh()->statut);
    }

    public function test_donor_cannot_cancel_someone_elses_donation(): void
    {
        $don = $this->don();

        $this->actingAs(User::factory()->create())->patch(route('dons.cancel', $don))->assertForbidden();
        $this->assertSame(DonStatut::Propose, $don->fresh()->statut);
    }

    public function test_donor_cannot_accept_their_own_donation(): void
    {
        $donateur = User::factory()->create();
        $don = $this->don(donateur: $donateur);

        $this->actingAs($donateur)->patch(route('dons.espace.statut', $don), ['statut' => 'accepte'])->assertForbidden();
        $this->assertSame(DonStatut::Propose, $don->fresh()->statut);
    }

    // Responsable de l'association

    public function test_responsable_sees_and_filters_donations_sent_to_their_association(): void
    {
        $this->don(DonStatut::Propose, User::factory()->create(['name' => 'Amel Propose']));
        $this->don(DonStatut::Accepte, User::factory()->create(['name' => 'Karim Accepte']));
        Don::factory()->create(['user_id' => User::factory()->create(['name' => 'Sami Ailleurs'])->id]);

        $this->actingAs($this->responsable)->get(route('dons.espace.index'))
            ->assertOk()
            ->assertSeeText(['Amel Propose', 'Karim Accepte', 'Accepter', 'Refuser'])
            ->assertDontSeeText('Sami Ailleurs');

        $this->actingAs($this->responsable)->get(route('dons.espace.index', ['statut' => 'accepte']))
            ->assertOk()
            ->assertSeeText(['Karim Accepte', 'Planifier la remise', 'Marquer comme reçu'])
            ->assertDontSeeText('Amel Propose');

        $this->actingAs($this->responsable)->get(route('dons.espace.index', ['q' => 'Amel']))
            ->assertOk()
            ->assertSeeText('Amel Propose')
            ->assertDontSeeText('Karim Accepte');
    }

    public function test_responsable_only_gets_buttons_for_allowed_transitions(): void
    {
        $don = $this->don(DonStatut::Planifie);

        $this->actingAs($this->responsable)->get(route('dons.espace.show', $don))
            ->assertOk()
            ->assertSee('value="recu"', false)
            ->assertDontSee('value="accepte"', false)
            ->assertDontSee('value="refuse"', false)
            // Annuler reste réservé au donateur et à l'administrateur.
            ->assertDontSee('value="annule"', false);
    }

    public function test_responsable_accepts_plans_and_receives_a_donation(): void
    {
        $don = $this->don();

        foreach (['accepte', 'planifie', 'recu'] as $statut) {
            $this->actingAs($this->responsable)
                ->from(route('dons.espace.show', $don))
                ->patch(route('dons.espace.statut', $don), ['statut' => $statut, 'commentaire' => "Étape {$statut}"])
                ->assertRedirect(route('dons.espace.show', $don))
                ->assertSessionHas('success');
        }

        $this->assertSame(DonStatut::Recu, $don->fresh()->statut);
        $this->assertSame(4, $don->historiques()->count());
        $this->assertDatabaseHas('don_historiques', ['don_id' => $don->id, 'user_id' => $this->responsable->id, 'nouveau_statut' => 'planifie', 'commentaire' => 'Étape planifie']);
    }

    public function test_responsable_refuses_a_donation(): void
    {
        $don = $this->don();

        $this->actingAs($this->responsable)->patch(route('dons.espace.statut', $don), ['statut' => 'refuse'])->assertRedirect();

        $this->assertSame(DonStatut::Refuse, $don->fresh()->statut);
    }

    public function test_responsable_cannot_cancel_a_donation(): void
    {
        $don = $this->don();

        $this->actingAs($this->responsable)->patch(route('dons.espace.statut', $don), ['statut' => 'annule'])->assertForbidden();
        $this->assertSame(DonStatut::Propose, $don->fresh()->statut);
    }

    public function test_forbidden_transition_is_rejected_with_a_message(): void
    {
        $don = $this->don(DonStatut::Refuse);

        $this->actingAs($this->responsable)
            ->from(route('dons.espace.show', $don))
            ->patch(route('dons.espace.statut', $don), ['statut' => 'recu'])
            ->assertRedirect(route('dons.espace.show', $don))
            ->assertSessionHas('error', 'Un don « Refusé » ne peut pas passer au statut « Reçu ».');

        $this->assertSame(DonStatut::Refuse, $don->fresh()->statut);
    }

    public function test_responsable_cannot_touch_donations_of_another_association(): void
    {
        $autre = Don::factory()->propose()->create();

        $this->actingAs($this->responsable)->get(route('dons.espace.show', $autre))->assertForbidden();
        $this->actingAs($this->responsable)->patch(route('dons.espace.statut', $autre), ['statut' => 'accepte'])->assertForbidden();
        $this->assertSame(DonStatut::Propose, $autre->fresh()->statut);
    }

    public function test_espace_is_closed_to_accounts_without_an_association(): void
    {
        $this->get(route('dons.espace.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('dons.espace.index'))->assertForbidden();
    }

    public function test_mes_dons_links_responsables_to_their_espace(): void
    {
        $this->actingAs($this->responsable)->get(route('dons.mes-dons'))->assertSee(route('dons.espace.index'));
        $this->actingAs(User::factory()->create())->get(route('dons.mes-dons'))->assertDontSee(route('dons.espace.index'));
    }

    // Administrateur

    public function test_admin_changes_the_statut_and_sees_the_timeline(): void
    {
        $admin = User::factory()->admin()->create();
        $don = $this->don();

        $this->actingAs($admin)
            ->from(route('admin.dons.dons.show', $don))
            ->patch(route('admin.dons.dons.statut', $don), ['statut' => 'annule', 'commentaire' => 'Doublon'])
            ->assertRedirect(route('admin.dons.dons.show', $don));

        $this->assertSame(DonStatut::Annule, $don->fresh()->statut);
        $this->actingAs($admin)->get(route('admin.dons.dons.show', $don))
            ->assertOk()
            ->assertSeeText(['Historique', 'Doublon', $admin->name])
            ->assertSeeText('statut final');
    }

    public function test_back_office_pages_render(): void
    {
        $admin = User::factory()->admin()->create();
        $don = $this->don(DonStatut::Accepte);
        $final = $this->don(DonStatut::Refuse);

        $this->actingAs($admin)->get(route('admin.dons.dons.index'))->assertOk()
            ->assertSee('value="planifie"', false)
            ->assertSee(route('admin.dons.dons.show', $final));
        $this->actingAs($admin)->get(route('admin.dons.dons.index', ['statut' => 'accepte', 'type_article' => 'chaussures']))->assertOk();
        $this->actingAs($admin)->get(route('admin.dons.dons.create'))->assertOk()->assertSeeText('Statut initial');
        $this->actingAs($admin)->get(route('admin.dons.dons.edit', $don))->assertOk()->assertDontSeeText('Statut initial')->assertSeeText('Accepté');
        $this->actingAs($admin)->get(route('admin.dons.associations.index'))->assertOk()->assertSeeText('Croissant Rouge');
        $this->actingAs($admin)->get(route('admin.dons.associations.create'))->assertOk()->assertSeeText(['Responsable', 'Capacité mensuelle', 'Latitude']);
        $this->actingAs($admin)->get(route('admin.dons.associations.edit', $this->association))->assertOk()
            ->assertSee('<option value="'.$this->responsable->id.'" selected', false);
        $this->actingAs($admin)->get(route('admin.dons.associations.show', $this->association))->assertOk()
            ->assertSeeText([$this->responsable->name, 'Capacité mensuelle']);
    }

    public function test_admin_is_still_bound_by_the_state_machine(): void
    {
        $don = $this->don(DonStatut::Distribue);

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.dons.dons.statut', $don), ['statut' => 'propose'])
            ->assertSessionHas('error');

        $this->assertSame(DonStatut::Distribue, $don->fresh()->statut);
    }

    public function test_clients_and_responsables_cannot_use_the_back_office(): void
    {
        $don = $this->don();

        foreach ([User::factory()->create(), $this->responsable] as $user) {
            $this->actingAs($user)->get(route('admin.dons.dons.index'))->assertForbidden();
            $this->actingAs($user)->patch(route('admin.dons.dons.statut', $don), ['statut' => 'accepte'])->assertForbidden();
            $this->actingAs($user)->delete(route('admin.dons.associations.destroy', $this->association))->assertForbidden();
        }

        $this->assertSame(DonStatut::Propose, $don->fresh()->statut);
    }

    public function test_policies_directly(): void
    {
        $admin = User::factory()->admin()->create();
        $donateur = User::factory()->create();
        $inconnu = User::factory()->create();
        $don = $this->don(donateur: $donateur);

        $this->assertTrue($admin->can('update', $don));
        $this->assertTrue($admin->can('delete', $this->association));
        $this->assertTrue($donateur->can('view', $don));
        $this->assertTrue($donateur->can('cancel', $don));
        $this->assertFalse($donateur->can('transition', [$don, DonStatut::Accepte]));
        $this->assertFalse($donateur->can('update', $don));
        $this->assertTrue($this->responsable->can('view', $don));
        $this->assertTrue($this->responsable->can('transition', [$don, DonStatut::Accepte]));
        $this->assertFalse($this->responsable->can('cancel', $don));
        $this->assertFalse($this->responsable->can('update', $this->association));
        $this->assertTrue($this->responsable->can('view', $this->association));
        $this->assertFalse($inconnu->can('view', $don));
        $this->assertFalse($inconnu->can('view', $this->association));
        $this->assertFalse($inconnu->can('accederEspace', Association::class));
    }

    // Archivage

    public function test_archiving_an_association_keeps_its_donations_readable(): void
    {
        $donateur = User::factory()->create();
        $don = $this->don(donateur: $donateur);

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.dons.associations.destroy', $this->association))
            ->assertRedirect(route('admin.dons.associations.index'));

        $this->assertSoftDeleted($this->association);
        $this->assertNotSoftDeleted($don);
        $this->get(route('dons.associations.show', $this->association))->assertNotFound();
        $this->actingAs($donateur)->get(route('dons.mes-dons'))->assertOk()->assertSeeText('Croissant Rouge');
        $this->actingAs($donateur)->get(route('dons.mes-dons.show', $don))->assertOk();
    }

    public function test_deleting_a_donation_is_a_soft_delete(): void
    {
        $don = $this->don();

        $this->actingAs(User::factory()->admin()->create())->delete(route('admin.dons.dons.destroy', $don))->assertRedirect();

        $this->assertSoftDeleted($don);
        $this->assertDatabaseHas('don_historiques', ['don_id' => $don->id]);
    }
}
