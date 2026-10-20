<?php

namespace Tests\Feature;

use App\Models\ProjetUpcycling;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UpcyclingDatabase;
use Tests\TestCase;

class UpcyclingFavoritesTest extends TestCase
{
    use UpcyclingDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Http::preventStrayRequests();
    }

    public function test_utilisateur_connecte_ajoute_un_projet_publie(): void
    {
        $user = User::factory()->unverified()->create();
        $projet = ProjetUpcycling::factory()->create();
        $this->actingAs($user)->from(route('upcycling.projets.show', $projet))
            ->post(route('upcycling.favoris.store', $projet))->assertRedirect(route('upcycling.projets.show', $projet))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('projet_upcycling_favoris', ['user_id' => $user->id, 'projet_upcycling_id' => $projet->id]);
        $favori = $user->projetsFavoris()->firstOrFail();
        $this->assertNotNull($favori->pivot->created_at);
        $this->assertNotNull($favori->pivot->updated_at);
        $this->assertTrue($projet->favorisPar()->whereKey($user->id)->exists());
        Http::assertNothingSent();
    }

    public function test_utilisateur_retire_favori_et_retrait_repete_sans_erreur(): void
    {
        $user = User::factory()->create();
        $projet = ProjetUpcycling::factory()->create();
        $user->projetsFavoris()->attach($projet);
        foreach ([1, 2] as $essai) {
            $this->actingAs($user)->from(route('upcycling.mes-favoris'))
                ->delete(route('upcycling.favoris.destroy', $projet))->assertRedirect(route('upcycling.mes-favoris'))->assertSessionHasNoErrors();
            $this->assertDatabaseMissing('projet_upcycling_favoris', ['user_id' => $user->id, 'projet_upcycling_id' => $projet->id]);
        }
    }

    public function test_ajout_repete_ne_cree_pas_de_doublon(): void
    {
        $user = User::factory()->create();
        $projet = ProjetUpcycling::factory()->create();
        $this->actingAs($user);
        foreach ([1, 2] as $essai) {
            $this->post(route('upcycling.favoris.store', $projet))->assertRedirect()->assertSessionHasNoErrors();
        }
        $this->assertDatabaseCount('projet_upcycling_favoris', 1);
    }

    public function test_contrainte_unique_en_base(): void
    {
        $user = User::factory()->create();
        $projet = ProjetUpcycling::factory()->create();
        $user->projetsFavoris()->attach($projet);
        $this->expectException(QueryException::class);
        $user->projetsFavoris()->attach($projet);
    }

    public function test_visiteur_redirige_vers_connexion_pour_les_trois_routes(): void
    {
        $projet = ProjetUpcycling::factory()->create();
        $this->get(route('upcycling.mes-favoris'))->assertRedirect(route('login'));
        $this->post(route('upcycling.favoris.store', $projet))->assertRedirect(route('login'));
        $this->delete(route('upcycling.favoris.destroy', $projet))->assertRedirect(route('login'));
        $this->assertDatabaseCount('projet_upcycling_favoris', 0);
        $this->get(route('upcycling.projets.show', $projet))->assertOk()->assertSeeText('Se connecter pour ajouter aux favoris');
    }

    public function test_tous_statuts_non_publies_refuses_meme_pour_auteur_et_admin(): void
    {
        foreach (['brouillon', 'soumis', 'rejete'] as $statut) {
            $projet = ProjetUpcycling::factory()->create(['statut' => $statut]);
            foreach ([$projet->user, User::factory()->admin()->create()] as $user) {
                $this->actingAs($user)->post(route('upcycling.favoris.store', $projet))->assertForbidden();
                $this->get(route('upcycling.projets.show', $projet))->assertOk()->assertDontSeeText('Ajouter aux favoris')->assertDontSeeText('Retirer des favoris');
            }
        }
        $this->assertDatabaseCount('projet_upcycling_favoris', 0);
    }

    public function test_projet_inexistant_retourne_404(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('upcycling.favoris.store', 999999))->assertNotFound();
        $this->delete(route('upcycling.favoris.destroy', 999999))->assertNotFound();
        $this->assertDatabaseCount('projet_upcycling_favoris', 0);
    }

    public function test_page_favoris_affiche_titre_difficulte_photo_lien_et_retrait(): void
    {
        $user = User::factory()->create();
        $projet = ProjetUpcycling::factory()->create(['photo_apres' => 'upcycling/favori.png']);
        $user->projetsFavoris()->attach($projet);
        $this->actingAs($user)->get(route('upcycling.mes-favoris'))->assertOk()
            ->assertSeeText($projet->titre)->assertSeeText($projet->difficulte->label())
            ->assertSee($projet->photoApresUrl(), false)->assertSee(route('upcycling.projets.show', $projet), false)
            ->assertSee(route('upcycling.favoris.destroy', $projet), false)->assertSeeText('Retirer des favoris')
            ->assertSee('name="_method" value="DELETE"', false)->assertSee('name="_token"', false);
    }

    public function test_utilisateur_a_ne_voit_pas_et_ne_modifie_pas_favoris_de_b(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $projetA = ProjetUpcycling::factory()->create(['titre' => 'Favori personnel utilisateur A']);
        $projetB = ProjetUpcycling::factory()->create(['titre' => 'Favori personnel utilisateur B']);
        $a->projetsFavoris()->attach($projetA);
        $b->projetsFavoris()->attach($projetB);
        $this->actingAs($a)->get(route('upcycling.mes-favoris', ['user_id' => $b->id]))->assertOk()
            ->assertSeeText($projetA->titre)->assertDontSeeText($projetB->titre);
        $this->delete(route('upcycling.favoris.destroy', $projetB), ['user_id' => $b->id])->assertRedirect();
        $this->assertDatabaseHas('projet_upcycling_favoris', ['user_id' => $b->id, 'projet_upcycling_id' => $projetB->id]);
        $this->post(route('upcycling.favoris.store', $projetA), ['user_id' => $b->id])->assertRedirect();
        $this->assertDatabaseMissing('projet_upcycling_favoris', ['user_id' => $b->id, 'projet_upcycling_id' => $projetA->id]);
    }

    public function test_suppression_projet_supprime_favoris_en_cascade(): void
    {
        $projet = ProjetUpcycling::factory()->create();
        User::factory()->count(2)->create()->each(fn ($user) => $user->projetsFavoris()->attach($projet));
        // Suppression SQL directe pour vérifier la contrainte, sans événement Eloquent.
        DB::table('projets_upcycling')->where('id', $projet->id)->delete();
        $this->assertDatabaseCount('projet_upcycling_favoris', 0);
    }

    public function test_suppression_utilisateur_supprime_uniquement_ses_favoris(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $projet = ProjetUpcycling::factory()->create();
        $a->projetsFavoris()->attach($projet);
        $b->projetsFavoris()->attach($projet);
        DB::table('users')->where('id', $a->id)->delete();
        $this->assertDatabaseCount('projet_upcycling_favoris', 1);
        $this->assertDatabaseHas('projet_upcycling_favoris', ['user_id' => $b->id, 'projet_upcycling_id' => $projet->id]);
    }

    public function test_projet_retire_de_publication_masque_et_peut_etre_retire_des_favoris(): void
    {
        $user = User::factory()->create();
        $projet = ProjetUpcycling::factory()->create(['titre' => 'Projet desormais prive']);
        $user->projetsFavoris()->attach($projet);
        $projet->update(['statut' => 'brouillon']);
        $this->actingAs($user)->get(route('upcycling.mes-favoris'))->assertOk()->assertDontSeeText($projet->titre)
            ->assertViewHas('projets', fn ($liste) => $liste->isEmpty());
        $this->post(route('upcycling.favoris.store', $projet))->assertForbidden();
        $this->delete(route('upcycling.favoris.destroy', $projet))->assertRedirect();
        $this->assertDatabaseCount('projet_upcycling_favoris', 0);
    }

    public function test_bouton_fiche_bascule_apres_ajout_et_retrait(): void
    {
        $projet = ProjetUpcycling::factory()->create();
        $this->actingAs(User::factory()->create());
        $url = route('upcycling.projets.show', $projet);
        $this->get($url)->assertOk()->assertSeeText('Ajouter aux favoris')->assertDontSeeText('Retirer des favoris');
        $this->post(route('upcycling.favoris.store', $projet))->assertRedirect();
        $this->get($url)->assertOk()->assertSeeText('Retirer des favoris')->assertDontSeeText('Ajouter aux favoris');
        $this->delete(route('upcycling.favoris.destroy', $projet))->assertRedirect();
        $this->get($url)->assertOk()->assertSeeText('Ajouter aux favoris');
    }

    public function test_page_favoris_vide_et_pagination(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('upcycling.mes-favoris'))->assertOk()->assertSeeText('Vous n’avez aucun projet publié dans vos favoris.');
        $user->projetsFavoris()->attach(ProjetUpcycling::factory()->count(10)->create()->modelKeys());
        $this->get(route('upcycling.mes-favoris'))->assertOk()->assertViewHas('projets', fn ($liste) => $liste->total() === 10 && $liste->count() === 9);
        $this->get(route('upcycling.mes-favoris', ['page' => 2]))->assertOk()->assertViewHas('projets', fn ($liste) => $liste->count() === 1);
    }

    public function test_actions_favoris_protegees_par_csrf(): void
    {
        $user = User::factory()->create();
        $projet = ProjetUpcycling::factory()->create();
        $user->projetsFavoris()->attach($projet);
        $this->actingAs($user);
        $this->app->bind(PreventRequestForgery::class,
            fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
            {
                protected function runningUnitTests()
                {
                    return false;
                }
            });
        $this->post(route('upcycling.favoris.store', $projet))->assertStatus(419);
        $this->delete(route('upcycling.favoris.destroy', $projet))->assertStatus(419);
        $this->assertDatabaseCount('projet_upcycling_favoris', 1);
        $this->withSession(['_token' => 'favoris-token'])->delete(route('upcycling.favoris.destroy', $projet), ['_token' => 'favoris-token'])->assertRedirect();
        $this->assertDatabaseCount('projet_upcycling_favoris', 0);
    }
}
