<?php

namespace Tests\Feature\Dons;

use App\Enums\Dons\DonStatut;
use App\Enums\Dons\StatutCampagne;
use App\Models\Association;
use App\Models\CampagneCollecte;
use App\Models\Don;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CampagneCollecteTest extends TestCase
{
    use RefreshDatabase;

    private function donPour(CampagneCollecte $campagne, DonStatut $statut, float $kg): Don
    {
        return Don::factory()->statut($statut)->create([
            'association_id' => $campagne->association_id,
            'campagne_id' => $campagne->id,
            'poids_kg' => $kg,
        ]);
    }

    // Progression

    public function test_progress_counts_only_received_or_distributed_kilos(): void
    {
        $campagne = CampagneCollecte::factory()->create(['objectif_kg' => 40]);
        $this->donPour($campagne, DonStatut::Recu, 6);
        $this->donPour($campagne, DonStatut::Distribue, 4);
        $this->donPour($campagne, DonStatut::Propose, 50);
        $this->donPour($campagne, DonStatut::Accepte, 50);
        $this->donPour($campagne, DonStatut::Refuse, 50);
        Don::factory()->statut(DonStatut::Recu)->create(['poids_kg' => 99]); // sans campagne

        $this->assertSame(10.0, $campagne->kgCollectes());
        $this->assertSame(25.0, $campagne->progression());

        $chargee = CampagneCollecte::query()->avecProgression()->find($campagne->id);
        $this->assertSame(10.0, $chargee->kgCollectes());
        $this->assertSame(25.0, $chargee->progression());
    }

    public function test_progress_can_exceed_the_goal(): void
    {
        $campagne = CampagneCollecte::factory()->create(['objectif_kg' => 10]);
        $this->donPour($campagne, DonStatut::Recu, 15);

        $this->assertSame(150.0, $campagne->progression());
    }

    public function test_receiving_a_donation_moves_the_progress_bar(): void
    {
        Storage::fake('local');
        $campagne = CampagneCollecte::factory()->create(['objectif_kg' => 20]);
        $don = $this->donPour($campagne, DonStatut::Accepte, 5);

        $this->get(route('dons.campagnes.index'))->assertSeeText('0 %');

        $this->actingAs(User::factory()->admin()->create())->patch(route('admin.dons.dons.statut', $don), ['statut' => 'recu']);

        $this->get(route('dons.campagnes.index'))
            ->assertOk()
            ->assertSeeText([$campagne->titre, '5,0 kg', '25 %']);
    }

    // Visibilité et cycle de vie

    public function test_public_page_lists_only_running_campaigns_of_active_associations(): void
    {
        $active = CampagneCollecte::factory()->create(['titre' => 'Campagne visible']);
        CampagneCollecte::factory()->brouillon()->create(['titre' => 'Campagne brouillon']);
        CampagneCollecte::factory()->expiree()->create(['titre' => 'Campagne expirée']);
        CampagneCollecte::factory()->create(['titre' => 'Campagne future', 'date_debut' => today()->addWeek(), 'date_fin' => today()->addMonth()]);
        CampagneCollecte::factory()->create(['titre' => 'Campagne inactive', 'association_id' => Association::factory()->inactive()]);

        $this->get(route('dons.campagnes.index'))
            ->assertOk()
            ->assertSeeText('Campagne visible')
            ->assertDontSeeText(['Campagne brouillon', 'Campagne expirée', 'Campagne future', 'Campagne inactive']);

        $this->get(route('dons.associations.show', $active->association))->assertSeeText(['Campagnes en cours', 'Campagne visible']);
    }

    public function test_scheduled_command_closes_expired_campaigns(): void
    {
        $expiree = CampagneCollecte::factory()->expiree()->create();
        $finitAujourdhui = CampagneCollecte::factory()->create(['date_fin' => today()]);
        $brouillonExpire = CampagneCollecte::factory()->expiree()->brouillon()->create();

        $this->artisan('dons:cloturer-campagnes')->expectsOutput('1 campagne(s) terminée(s).')->assertSuccessful();

        $this->assertSame(StatutCampagne::Terminee, $expiree->fresh()->statut);
        $this->assertSame(StatutCampagne::Active, $finitAujourdhui->fresh()->statut);
        $this->assertSame(StatutCampagne::Brouillon, $brouillonExpire->fresh()->statut);
    }

    public function test_command_is_scheduled_daily(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('dons:cloturer-campagnes')->assertSuccessful();
    }

    // Rattacher un don

    public function test_donation_form_preselects_and_records_the_campaign(): void
    {
        $campagne = CampagneCollecte::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dons.create', [$campagne->association, 'campagne' => $campagne->id]))
            ->assertOk()
            ->assertSee('<option value="'.$campagne->id.'" selected', false);

        $this->actingAs($user)->post(route('dons.store', $campagne->association), [
            'type_article' => 'chaussures', 'quantite' => 2, 'poids_kg' => 3, 'etat_general' => 'bon',
            'mode_remise' => 'depot_sur_place', 'date_remise' => today()->addDay()->toDateString(), 'campagne_id' => $campagne->id,
        ])->assertSessionHasNoErrors();

        $this->assertTrue($campagne->is(Don::query()->sole()->campagne));
    }

    public function test_a_campaign_of_another_association_or_closed_is_rejected(): void
    {
        $association = Association::factory()->create();
        $autre = CampagneCollecte::factory()->create();
        $brouillon = CampagneCollecte::factory()->brouillon()->create(['association_id' => $association->id]);

        foreach ([$autre, $brouillon] as $campagne) {
            $this->actingAs(User::factory()->create())->post(route('dons.store', $association), [
                'type_article' => 'chaussures', 'quantite' => 2, 'etat_general' => 'bon',
                'mode_remise' => 'depot_sur_place', 'date_remise' => today()->addDay()->toDateString(), 'campagne_id' => $campagne->id,
            ])->assertSessionHasErrors('campagne_id');
        }

        $this->assertSame(0, Don::query()->count());
    }

    // Back office

    public function test_admin_crud(): void
    {
        $admin = User::factory()->admin()->create();
        $association = Association::factory()->create();

        $this->actingAs($admin)->get(route('admin.dons.campagnes.create'))->assertOk();
        $this->actingAs($admin)->post(route('admin.dons.campagnes.store'), [
            'association_id' => $association->id,
            'titre' => 'Grand froid 2026',
            'description' => 'Collecte de manteaux et de couvertures pour l\'hiver.',
            'type_articles_cibles' => ['vetements_homme', 'linge_maison'],
            'objectif_kg' => 200,
            'date_debut' => today()->toDateString(),
            'date_fin' => today()->addMonth()->toDateString(),
            'statut' => 'active',
        ])->assertSessionHasNoErrors();

        $campagne = CampagneCollecte::query()->sole();
        $this->assertSame(['vetements_homme', 'linge_maison'], $campagne->type_articles_cibles);

        $this->actingAs($admin)->get(route('admin.dons.campagnes.index'))->assertOk()->assertSeeText('Grand froid 2026');
        $this->actingAs($admin)->get(route('admin.dons.campagnes.show', $campagne))->assertOk()->assertSeeText(['Progression', 'Linge de maison']);
        $this->actingAs($admin)->get(route('admin.dons.campagnes.edit', $campagne))->assertOk();
        $this->actingAs($admin)->put(route('admin.dons.campagnes.update', $campagne), [
            ...$campagne->only(['association_id', 'titre', 'description', 'objectif_kg']),
            'date_debut' => $campagne->date_debut->toDateString(),
            'date_fin' => $campagne->date_fin->toDateString(),
            'statut' => 'terminee',
        ])->assertSessionHasNoErrors();
        $this->assertSame(StatutCampagne::Terminee, $campagne->fresh()->statut);
        $this->assertNull($campagne->fresh()->type_articles_cibles);

        $don = Don::factory()->create(['campagne_id' => $campagne->id, 'association_id' => $association->id]);
        $this->actingAs($admin)->delete(route('admin.dons.campagnes.destroy', $campagne))->assertRedirect(route('admin.dons.campagnes.index'));
        $this->assertModelMissing($campagne);
        $this->assertNull($don->fresh()->campagne_id);
    }

    public function test_admin_validation(): void
    {
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.dons.campagnes.store'), [
            'association_id' => Association::factory()->create()->id,
            'titre' => 'Hiver',
            'description' => 'Trop court',
            'type_articles_cibles' => ['chapeaux'],
            'objectif_kg' => 9,
            'date_debut' => '2026-12-01',
            'date_fin' => '2026-12-01',
            'statut' => 'publiee',
        ])->assertSessionHasErrors(['description', 'type_articles_cibles.0', 'objectif_kg', 'date_fin', 'statut'])
            ->assertSessionHasErrors(['objectif_kg' => 'L\'objectif doit être d\'au moins 10 kg.', 'date_fin' => 'La date de fin doit être postérieure à la date de début.']);

        $this->assertSame(0, CampagneCollecte::query()->count());
    }

    public function test_only_admins_manage_campaigns(): void
    {
        $campagne = CampagneCollecte::factory()->create();
        $responsable = User::factory()->create();
        $campagne->association->update(['responsable_id' => $responsable->id]);

        foreach ([User::factory()->create(), $responsable] as $user) {
            $this->actingAs($user)->get(route('admin.dons.campagnes.index'))->assertForbidden();
            $this->actingAs($user)->delete(route('admin.dons.campagnes.destroy', $campagne))->assertForbidden();
        }
        $this->assertModelExists($campagne);
    }
}
