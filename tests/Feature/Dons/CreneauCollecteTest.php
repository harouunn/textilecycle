<?php

namespace Tests\Feature\Dons;

use App\Enums\Dons\DonStatut;
use App\Models\Association;
use App\Models\CreneauCollecte;
use App\Models\Don;
use App\Models\User;
use App\Services\Dons\DonWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreneauCollecteTest extends TestCase
{
    use RefreshDatabase;

    private Association $association;

    protected function setUp(): void
    {
        parent::setUp();

        $this->association = Association::factory()->create();
    }

    private function collecte(CreneauCollecte $creneau, array $surcharge = []): array
    {
        return [
            'type_article' => 'linge_maison',
            'quantite' => 3,
            'poids_kg' => 2,
            'etat_general' => 'bon',
            'mode_remise' => 'collecte_a_domicile',
            'adresse_collecte' => '5 rue de Rome, Tunis',
            'creneau_id' => $creneau->id,
            ...$surcharge,
        ];
    }

    // Surréservation

    public function test_a_full_slot_cannot_be_booked(): void
    {
        $creneau = CreneauCollecte::factory()->create(['association_id' => $this->association->id, 'capacite_max' => 2]);

        foreach (range(1, 2) as $i) {
            $this->actingAs(User::factory()->create())
                ->post(route('dons.store', $this->association), $this->collecte($creneau))
                ->assertSessionHasNoErrors();
        }

        $this->actingAs(User::factory()->create())
            ->post(route('dons.store', $this->association), $this->collecte($creneau))
            ->assertSessionHasErrors('creneau_id');

        $this->assertSame(2, $creneau->dons()->count());
        $this->assertSame(0, $creneau->fresh()->placesRestantes());
    }

    public function test_the_workflow_refuses_overbooking_even_without_form_validation(): void
    {
        // Simule deux donateurs qui ont validé le formulaire en même temps : le second arrive après la dernière place.
        $creneau = CreneauCollecte::factory()->create(['association_id' => $this->association->id, 'capacite_max' => 1]);
        $workflow = app(DonWorkflowService::class);
        $workflow->proposer($this->association, $this->collecte($creneau), User::factory()->create());

        try {
            $workflow->proposer($this->association, $this->collecte($creneau), User::factory()->create());
            $this->fail('Le créneau complet aurait dû être refusé.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('creneau_id', $e->errors());
        }

        $this->assertSame(1, Don::query()->count());
    }

    public function test_a_slot_of_another_association_is_rejected(): void
    {
        $autre = CreneauCollecte::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('dons.store', $this->association), $this->collecte($autre))
            ->assertSessionHasErrors('creneau_id');

        $this->expectException(ValidationException::class);
        app(DonWorkflowService::class)->proposer($this->association, $this->collecte($autre), User::factory()->create());
    }

    public function test_past_slots_are_not_offered(): void
    {
        $passe = CreneauCollecte::factory()->create(['association_id' => $this->association->id, 'date' => today()->subDay()]);

        $this->actingAs(User::factory()->create())
            ->post(route('dons.store', $this->association), $this->collecte($passe))
            ->assertSessionHasErrors('creneau_id');
    }

    public function test_cancelled_or_refused_donations_free_their_place(): void
    {
        $creneau = CreneauCollecte::factory()->create(['association_id' => $this->association->id, 'capacite_max' => 1]);
        $donateur = User::factory()->create();
        $this->actingAs($donateur)->post(route('dons.store', $this->association), $this->collecte($creneau))->assertSessionHasNoErrors();

        $this->assertFalse(CreneauCollecte::query()->disponibles()->whereKey($creneau->id)->exists());

        $this->actingAs($donateur)->patch(route('dons.cancel', Don::query()->sole()));

        $this->assertTrue(CreneauCollecte::query()->disponibles()->whereKey($creneau->id)->exists());
        $this->actingAs(User::factory()->create())
            ->post(route('dons.store', $this->association), $this->collecte($creneau))
            ->assertSessionHasNoErrors();
    }

    public function test_drop_off_ignores_any_slot_sent(): void
    {
        $creneau = CreneauCollecte::factory()->create(['association_id' => $this->association->id, 'capacite_max' => 1]);

        $this->actingAs(User::factory()->create())->post(route('dons.store', $this->association), $this->collecte($creneau, [
            'mode_remise' => 'depot_sur_place',
            'date_remise' => today()->addDay()->toDateString(),
        ]))->assertSessionHasNoErrors();

        $this->assertNull(Don::query()->sole()->creneau_id);
        $this->assertSame(1, $creneau->fresh()->placesRestantes());
    }

    public function test_the_form_lists_only_available_slots(): void
    {
        $libre = CreneauCollecte::factory()->create(['association_id' => $this->association->id, 'capacite_max' => 2, 'date' => today()->addDays(3)]);
        $complet = CreneauCollecte::factory()->create(['association_id' => $this->association->id, 'capacite_max' => 1, 'date' => today()->addDays(4)]);
        Don::factory()->statut(DonStatut::Accepte)->create(['association_id' => $this->association->id, 'creneau_id' => $complet->id]);

        $this->actingAs(User::factory()->create())->get(route('dons.create', $this->association))
            ->assertOk()
            ->assertSee('value="'.$libre->id.'"', false)
            ->assertDontSee('value="'.$complet->id.'"', false)
            ->assertSeeText('2 place(s)');
    }

    // Gestion par le responsable

    public function test_responsable_opens_and_deletes_slots(): void
    {
        $responsable = User::factory()->create();
        $this->association->update(['responsable_id' => $responsable->id]);

        $this->actingAs($responsable)->post(route('dons.espace.creneaux.store'), [
            'association_id' => $this->association->id,
            'date' => today()->addDays(2)->toDateString(),
            'heure_debut' => '09:00',
            'heure_fin' => '12:00',
            'capacite_max' => 4,
        ])->assertRedirect(route('dons.espace.creneaux.index'));

        $creneau = CreneauCollecte::query()->sole();
        $this->assertSame(4, $creneau->capacite_max);
        $this->actingAs($responsable)->get(route('dons.espace.creneaux.index'))->assertOk()->assertSeeText('0 / 4');

        $this->actingAs($responsable)->delete(route('dons.espace.creneaux.destroy', $creneau))->assertRedirect();
        $this->assertModelMissing($creneau);
    }

    public function test_a_booked_slot_cannot_be_deleted(): void
    {
        $responsable = User::factory()->create();
        $this->association->update(['responsable_id' => $responsable->id]);
        $creneau = CreneauCollecte::factory()->create(['association_id' => $this->association->id]);
        Don::factory()->propose()->create(['association_id' => $this->association->id, 'creneau_id' => $creneau->id]);

        $this->actingAs($responsable)->delete(route('dons.espace.creneaux.destroy', $creneau))->assertForbidden();
        $this->assertModelExists($creneau);
    }

    public function test_only_the_responsable_manages_the_slots(): void
    {
        $creneau = CreneauCollecte::factory()->create(['association_id' => $this->association->id]);
        $autre = User::factory()->create();
        Association::factory()->gereePar($autre)->create();

        $this->actingAs($autre)->post(route('dons.espace.creneaux.store'), [
            'association_id' => $this->association->id, 'date' => today()->addDay()->toDateString(),
            'heure_debut' => '09:00', 'heure_fin' => '10:00', 'capacite_max' => 2,
        ])->assertForbidden();
        $this->actingAs($autre)->delete(route('dons.espace.creneaux.destroy', $creneau))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('dons.espace.creneaux.index'))->assertForbidden();
        $this->assertSame(1, CreneauCollecte::query()->count());
    }

    public function test_slot_validation(): void
    {
        $responsable = User::factory()->create();
        $this->association->update(['responsable_id' => $responsable->id]);

        $this->actingAs($responsable)->post(route('dons.espace.creneaux.store'), [
            'association_id' => $this->association->id,
            'date' => today()->subDay()->toDateString(),
            'heure_debut' => '14:00',
            'heure_fin' => '10:00',
            'capacite_max' => 0,
        ])->assertSessionHasErrors(['date', 'heure_fin', 'capacite_max']);

        $this->assertSame(0, CreneauCollecte::query()->count());
    }
}
