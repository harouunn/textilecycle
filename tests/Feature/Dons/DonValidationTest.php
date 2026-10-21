<?php

namespace Tests\Feature\Dons;

use App\Enums\Dons\DonStatut;
use App\Enums\Dons\ModeRemise;
use App\Enums\Dons\TypeArticle;
use App\Models\Association;
use App\Models\CreneauCollecte;
use App\Models\Don;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DonValidationTest extends TestCase
{
    use RefreshDatabase;

    private function valide(array $surcharge = []): array
    {
        return [
            'type_article' => 'vetements_enfant',
            'quantite' => 12,
            'poids_kg' => '4.5',
            'etat_general' => 'bon',
            'mode_remise' => 'depot_sur_place',
            'date_remise' => now()->addDays(3)->toDateString(),
            'message' => 'Tailles 4 à 8 ans.',
            ...$surcharge,
        ];
    }

    public function test_valid_donation_is_proposed_with_its_enums(): void
    {
        $user = User::factory()->create();
        $association = Association::factory()->create();
        $creneau = CreneauCollecte::factory()->create(['association_id' => $association->id]);

        $this->actingAs($user)->post(route('dons.store', $association), $this->valide([
            'mode_remise' => 'collecte_a_domicile',
            'adresse_collecte' => '12 rue de Marseille, Tunis',
            'creneau_id' => $creneau->id,
            // Champs système : ignorés même s'ils sont envoyés.
            'statut' => 'recu',
            'score_risque' => 99,
        ]))->assertRedirect(route('dons.mes-dons'));

        $don = Don::query()->sole();
        $this->assertSame(DonStatut::Propose, $don->statut);
        $this->assertSame(TypeArticle::VetementsEnfant, $don->type_article);
        $this->assertSame(ModeRemise::CollecteADomicile, $don->mode_remise);
        $this->assertSame('12 rue de Marseille, Tunis', $don->adresse_collecte);
        // La date de remise est celle du créneau choisi.
        $this->assertTrue($creneau->is($don->creneau));
        $this->assertSame($creneau->date->toDateString(), $don->date_remise->toDateString());
        // Le 99 envoyé est ignoré : le score est calculé par AnalyserRisqueDon (règles), ici 0.
        $this->assertSame(0, $don->score_risque);
        $this->assertTrue($user->is($don->user));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function donneesInvalides(): iterable
    {
        yield 'type inconnu' => [['type_article' => 'chapeaux'], 'type_article'];
        yield 'type manquant' => [['type_article' => ''], 'type_article'];
        yield 'état inconnu' => [['etat_general' => 'neuf'], 'etat_general'];
        yield 'mode inconnu' => [['mode_remise' => 'poste'], 'mode_remise'];
        yield 'quantité nulle' => [['quantite' => 0], 'quantite'];
        yield 'quantité trop grande' => [['quantite' => 501], 'quantite'];
        yield 'quantité décimale' => [['quantite' => 2.5], 'quantite'];
        yield 'poids négatif' => [['poids_kg' => -1], 'poids_kg'];
        yield 'date passée' => [['date_remise' => '2020-01-01'], 'date_remise'];
        yield 'date invalide' => [['date_remise' => 'demain'], 'date_remise'];
        yield 'collecte sans adresse' => [['mode_remise' => 'collecte_a_domicile', 'adresse_collecte' => ''], 'adresse_collecte'];
        yield 'collecte sans créneau' => [['mode_remise' => 'collecte_a_domicile', 'adresse_collecte' => '1 rue de Rome'], 'creneau_id'];
        yield 'créneau inconnu' => [['mode_remise' => 'collecte_a_domicile', 'adresse_collecte' => '1 rue de Rome', 'creneau_id' => 999], 'creneau_id'];
        yield 'campagne inconnue' => [['campagne_id' => 999], 'campagne_id'];
        yield 'message trop long' => [['message' => str_repeat('a', 1001)], 'message'];
    }

    #[DataProvider('donneesInvalides')]
    public function test_invalid_donation_is_rejected(array $surcharge, string $champ): void
    {
        $association = Association::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('dons.store', $association), $this->valide($surcharge))
            ->assertSessionHasErrors($champ);

        $this->assertDatabaseCount('dons', 0);
    }

    public function test_drop_off_ignores_the_collection_address(): void
    {
        $association = Association::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('dons.store', $association), $this->valide(['adresse_collecte' => 'Inutile']))
            ->assertSessionHasNoErrors();

        $this->assertNull(Don::query()->sole()->adresse_collecte);
    }

    public function test_inactive_or_archived_associations_do_not_accept_donations(): void
    {
        $user = User::factory()->create();
        $inactive = Association::factory()->inactive()->create();
        $archivee = Association::factory()->create();
        $archivee->delete();

        $this->actingAs($user)->post(route('dons.store', $inactive), $this->valide())->assertNotFound();
        $this->actingAs($user)->post(route('dons.store', $archivee), $this->valide())->assertNotFound();
        $this->assertDatabaseCount('dons', 0);
    }

    public function test_donation_form_is_rate_limited_to_five_per_minute_per_user(): void
    {
        $association = Association::factory()->create();
        $user = User::factory()->create();

        foreach (range(1, 5) as $i) {
            $this->actingAs($user)->post(route('dons.store', $association), $this->valide())->assertRedirect();
        }
        $this->actingAs($user)->post(route('dons.store', $association), $this->valide())->assertTooManyRequests();
        $this->assertDatabaseCount('dons', 5);

        // Le compteur est propre à chaque compte.
        $this->actingAs(User::factory()->create())->post(route('dons.store', $association), $this->valide())->assertRedirect();

        // Et il se réinitialise après une minute.
        $this->travel(61)->seconds();
        $this->actingAs($user)->post(route('dons.store', $association), $this->valide())->assertRedirect();
        $this->assertDatabaseCount('dons', 7);
    }

    public function test_statut_change_requires_a_known_statut(): void
    {
        $don = Don::factory()->propose()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch(route('admin.dons.dons.statut', $don), ['statut' => 'perdu'])->assertSessionHasErrors('statut');
        $this->actingAs($admin)->patch(route('admin.dons.dons.statut', $don), [])->assertSessionHasErrors('statut');
        $this->actingAs($admin)->patch(route('admin.dons.dons.statut', $don), ['statut' => 'accepte', 'commentaire' => str_repeat('a', 1001)])
            ->assertSessionHasErrors('commentaire');

        $this->assertSame(DonStatut::Propose, $don->fresh()->statut);
    }

    public function test_admin_edit_cannot_change_the_statut_outside_the_workflow(): void
    {
        $don = Don::factory()->propose()->create();

        $this->actingAs(User::factory()->admin()->create())->put(route('admin.dons.dons.update', $don), [
            'association_id' => $don->association_id,
            'user_id' => $don->user_id,
            ...$this->valide(),
            'statut' => 'distribue',
        ])->assertRedirect(route('admin.dons.dons.show', $don));

        $this->assertSame(DonStatut::Propose, $don->fresh()->statut);
        $this->assertSame(12, $don->fresh()->quantite);
    }

    public function test_admin_chooses_the_initial_statut_when_creating(): void
    {
        $association = Association::factory()->create();

        $this->actingAs(User::factory()->admin()->create())->post(route('admin.dons.dons.store'), [
            'association_id' => $association->id,
            'user_id' => User::factory()->create()->id,
            ...$this->valide(),
            'statut' => 'accepte',
        ])->assertSessionHasNoErrors();

        $don = Don::query()->sole();
        $this->assertSame(DonStatut::Accepte, $don->statut);
        $this->assertSame(DonStatut::Accepte, $don->historiques()->sole()->nouveau_statut);
    }

    public function test_association_fields_are_validated(): void
    {
        $admin = User::factory()->admin()->create();
        $base = Association::factory()->make()->only(['nom', 'description', 'adresse', 'ville', 'telephone', 'email', 'besoins']);

        $this->actingAs($admin)->post(route('admin.dons.associations.store'), [...$base, 'latitude' => 91, 'longitude' => 10])
            ->assertSessionHasErrors('latitude');
        $this->actingAs($admin)->post(route('admin.dons.associations.store'), [...$base, 'latitude' => 36.8])
            ->assertSessionHasErrors('longitude');
        $this->actingAs($admin)->post(route('admin.dons.associations.store'), [...$base, 'capacite_mensuelle_kg' => -5])
            ->assertSessionHasErrors('capacite_mensuelle_kg');
        $this->actingAs($admin)->post(route('admin.dons.associations.store'), [...$base, 'responsable_id' => 999999])
            ->assertSessionHasErrors('responsable_id');
        $this->assertDatabaseCount('associations', 0);

        $responsable = User::factory()->create();
        $this->actingAs($admin)->post(route('admin.dons.associations.store'), [
            ...$base, 'latitude' => '36.8065', 'longitude' => '10.1815', 'capacite_mensuelle_kg' => 300, 'responsable_id' => $responsable->id, 'active' => '1',
        ])->assertSessionHasNoErrors();

        $association = Association::query()->sole();
        $this->assertSame('36.8065000', $association->latitude);
        $this->assertSame(300, $association->capacite_mensuelle_kg);
        $this->assertTrue($association->estGereePar($responsable));
    }

    public function test_empty_capacity_defaults_to_zero(): void
    {
        $base = Association::factory()->make()->only(['nom', 'description', 'adresse', 'ville', 'telephone', 'email', 'besoins']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.dons.associations.store'), [...$base, 'capacite_mensuelle_kg' => ''])
            ->assertSessionHasNoErrors();

        $this->assertSame(0, Association::query()->sole()->capacite_mensuelle_kg);
    }
}
