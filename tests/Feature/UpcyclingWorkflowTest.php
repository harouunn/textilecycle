<?php

namespace Tests\Feature;

use App\Enums\Upcycling\StatutProjet;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Models\EtapeProjet;
use App\Models\ProjetUpcycling;
use App\Models\User;
use App\Services\Upcycling\ModerationProjet;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UpcyclingDatabase;
use Tests\TestCase;

class UpcyclingWorkflowTest extends TestCase
{
    use UpcyclingDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Http::preventStrayRequests();
    }

    private function projet(): ProjetUpcycling
    {
        Storage::disk('public')->put('test.png', 'photo');
        $p = ProjetUpcycling::factory()->brouillon()->create(['photo_avant' => 'test.png', 'photo_apres' => 'test.png']);
        EtapeProjet::factory()->for($p, 'projet')->create(['photo' => 'test.png']);

        return $p;
    }

    public function test_soumission_rejet_correction_resoumission_publication(): void
    {
        $p = $this->projet();
        $admin = User::factory()->admin()->create();
        $this->actingAs($p->user)->post(route('upcycling.projets.soumettre', $p))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(StatutProjet::Soumis, $p->fresh()->statut);
        $this->actingAs($admin)->post(route('admin.upcycling.projets.rejeter', $p), ['commentaire_refus' => '   '])->assertSessionHasErrors('commentaire_refus');
        $this->assertSame(StatutProjet::Soumis, $p->fresh()->statut);
        $this->post(route('admin.upcycling.projets.rejeter', $p), ['commentaire_refus' => 'Préciser la couture.'])->assertSessionHasNoErrors();
        $this->assertSame(StatutProjet::Rejete, $p->fresh()->statut);
        $this->actingAs($p->user)->get(route('upcycling.mes-projets'))->assertSeeText('Préciser la couture.');
        $this->put(route('upcycling.projets.update', $p), [
            'titre' => 'Un sac cabas corrigé', 'description' => 'Une description corrigée pour préciser la couture.',
            'vetement_origine' => 'Vieux jean', 'resultat' => 'Sac cabas', 'difficulte' => 'facile', 'duree_minutes' => 60, 'materiel_necessaire' => 'Fil et ciseaux',
        ])->assertSessionHasNoErrors();
        $this->post(route('upcycling.projets.soumettre', $p))->assertSessionHasNoErrors();
        $this->assertNull($p->fresh()->commentaire_refus);
        $this->actingAs($admin)->post(route('admin.upcycling.projets.publier', $p))->assertSessionHasNoErrors();
        $this->assertSame(StatutProjet::Publie, $p->fresh()->statut);
        $this->assertSame($admin->id, $p->fresh()->decision_par);
    }

    public function test_client_ne_peut_jamais_moderer_meme_son_projet(): void
    {
        $p = $this->projet();
        $p->update(['statut' => StatutProjet::Soumis]);
        $this->actingAs($p->user)->post(route('admin.upcycling.projets.publier', $p))->assertForbidden();
        $this->post(route('admin.upcycling.projets.rejeter', $p), ['commentaire_refus' => 'Non'])->assertForbidden();
        $this->assertFalse($p->user->can('moderate', $p));
        $this->assertSame(StatutProjet::Soumis, $p->fresh()->statut);
    }

    public function test_autre_auteur_ne_peut_soumettre_ni_modifier(): void
    {
        $p = $this->projet();
        $this->actingAs(User::factory()->create());
        $this->post(route('upcycling.projets.soumettre', $p))->assertForbidden();
        $this->put(route('upcycling.projets.update', $p), [])->assertForbidden();
    }

    public function test_publication_brouillon_et_soumission_incomplete_refusees(): void
    {
        $p = $this->projet();
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.upcycling.projets.publier', $p))->assertSessionHasErrors('statut');
        Storage::disk('public')->delete('test.png');
        $this->actingAs($p->user)->post(route('upcycling.projets.soumettre', $p))->assertSessionHasErrors('photo_avant');
        $this->assertSame(StatutProjet::Brouillon, $p->fresh()->statut);
    }

    public function test_modification_etape_publiee_exige_nouvelle_validation(): void
    {
        $p = $this->projet();
        $p->update(['statut' => StatutProjet::Publie]);
        $e = $p->etapes()->first();
        $this->actingAs($p->user)->put(route('upcycling.projets.etapes.update', [$p, $e]), ['numero' => 1, 'titre' => 'Nouvelle couture', 'contenu' => 'Coudre soigneusement les deux côtés.'])->assertSessionHasNoErrors();
        $this->assertSame(StatutProjet::Brouillon, $p->fresh()->statut);
    }

    public function test_galerie_exclut_tous_les_statuts_non_publies(): void
    {
        foreach (['brouillon', 'soumis', 'rejete'] as $s) {
            ProjetUpcycling::factory()->create(['statut' => $s, 'titre' => 'Secret '.$s]);
        }
        $r = $this->get(route('upcycling.index'));
        foreach (['brouillon', 'soumis', 'rejete'] as $s) {
            $r->assertDontSeeText('Secret '.$s);
        }
    }

    private function champs(): array
    {
        return ['titre' => 'Un sac cabas corrigé', 'description' => 'Un tutoriel complet pour transformer un jean en sac.', 'vetement_origine' => 'Vieux jean', 'resultat' => 'Sac cabas', 'difficulte' => 'facile', 'duree_minutes' => 60, 'materiel_necessaire' => 'Fil, aiguille et ciseaux'];
    }

    public function test_creation_client_brouillon_meme_si_publication_et_decision_injectees(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('upcycling.projets.store'), [...$this->champs(),
            'photo_avant' => UploadedFile::fake()->image('avant.png', 2, 2), 'photo_apres' => UploadedFile::fake()->image('apres.png', 2, 2),
            'statut' => 'publie', 'decision_par' => $user->id, 'commentaire_refus' => 'injecté', 'user_id' => 999,
        ])->assertSessionHasNoErrors()->assertRedirect();
        $p = ProjetUpcycling::firstOrFail();
        $this->assertSame(StatutProjet::Brouillon, $p->statut);
        $this->assertSame($user->id, $p->user_id);
        $this->assertNull($p->decision_par);
        $this->assertNull($p->commentaire_refus);
        $this->get(route('upcycling.index'))->assertDontSeeText($p->titre);
        Http::assertNothingSent();
    }

    public function test_soumission_brouillon_tracee_et_visible_dans_file_admin(): void
    {
        $p = $this->projet();
        $this->actingAs($p->user)->post(route('upcycling.projets.soumettre', $p))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(StatutProjet::Soumis, $p->fresh()->statut);
        $this->assertNotNull($p->fresh()->soumis_le);
        $this->assertNull($p->fresh()->decision_par);
        $this->get(route('upcycling.index'))->assertDontSeeText($p->titre);
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.upcycling.projets.index', ['statut' => 'soumis']))->assertOk()->assertSeeText($p->titre);
    }

    public function test_publication_admin_tracee_rend_projet_public(): void
    {
        $p = $this->projet();
        $admin = User::factory()->admin()->create();
        $this->actingAs($p->user)->post(route('upcycling.projets.soumettre', $p))->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.upcycling.projets.publier', $p))->assertSessionHasNoErrors();
        $this->assertSame(StatutProjet::Publie, $p->fresh()->statut);
        $this->assertSame($admin->id, $p->fresh()->decision_par);
        $this->assertNotNull($p->fresh()->decision_le);
        $this->app['auth']->forgetGuards();
        $this->get(route('upcycling.index'))->assertOk()->assertSeeText($p->titre);
        $this->get(route('upcycling.projets.show', $p))->assertOk();
    }

    public function test_refus_commentaire_obligatoire_et_erreurs_francaises(): void
    {
        $p = $this->projet();
        $p->update(['statut' => 'soumis']);
        $this->actingAs(User::factory()->admin()->create());
        foreach ([null, '', '   '] as $value) {
            $this->post(route('admin.upcycling.projets.rejeter', $p), ['commentaire_refus' => $value])->assertSessionHasErrors(['commentaire_refus' => 'Le commentaire de refus est obligatoire.']);
        }
        $this->post(route('admin.upcycling.projets.rejeter', $p), ['commentaire_refus' => ['array']])->assertSessionHasErrors(['commentaire_refus' => 'Le commentaire de refus doit être un texte.']);
        $this->post(route('admin.upcycling.projets.rejeter', $p), ['commentaire_refus' => str_repeat('a', 2001)])->assertSessionHasErrors(['commentaire_refus' => 'Le commentaire de refus ne peut pas dépasser 2000 caractères.']);
        $this->assertSame(StatutProjet::Soumis, $p->fresh()->statut);
        $this->assertNull($p->fresh()->commentaire_refus);
    }

    public function test_refus_avec_commentaire_visible_auteur_sur_liste_apercu_et_correction(): void
    {
        $p = $this->projet();
        $p->update(['statut' => 'soumis']);
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.upcycling.projets.rejeter', $p), ['commentaire_refus' => '   Préciser les coutures et ajouter les conseils.   '])->assertSessionHasNoErrors();
        $this->assertSame(StatutProjet::Rejete, $p->fresh()->statut);
        $this->assertSame('Préciser les coutures et ajouter les conseils.', $p->fresh()->commentaire_refus);
        $this->actingAs($p->user);
        foreach ([route('upcycling.mes-projets'), route('upcycling.projets.edit', $p), route('upcycling.projets.show', $p), route('upcycling.projets.etapes.index', $p)] as $url) {
            $this->get($url)->assertOk()->assertSeeText('Préciser les coutures et ajouter les conseils.');
        }
        $this->get(route('upcycling.mes-projets'))->assertSeeText('Resoumettre à validation');
        $this->get(route('upcycling.index'))->assertDontSeeText($p->titre)->assertDontSeeText('Préciser les coutures et ajouter les conseils.');
        $this->actingAs(User::factory()->create())->get(route('upcycling.projets.show', $p))->assertForbidden();
    }

    public function test_correction_et_resoumission_effacent_ancienne_decision_sans_publier(): void
    {
        $p = $this->projet();
        $admin = User::factory()->admin()->create();
        $p->update(['statut' => 'rejete', 'commentaire_refus' => 'Revoir les étapes.', 'decision_le' => now(), 'decision_par' => $admin->id]);
        $this->actingAs($p->user)->put(route('upcycling.projets.update', $p), $this->champs())->assertSessionHasNoErrors();
        $this->assertSame('Un sac cabas corrigé', $p->fresh()->titre);
        $this->post(route('upcycling.projets.soumettre', $p))->assertSessionHasNoErrors();
        $this->assertSame(StatutProjet::Soumis, $p->fresh()->statut);
        $this->assertNull($p->fresh()->commentaire_refus);
        $this->assertNull($p->fresh()->decision_par);
        $this->assertNull($p->fresh()->decision_le);
        $this->get(route('upcycling.index'))->assertDontSeeText($p->fresh()->titre);
    }

    public function test_boutons_decision_uniquement_admin_et_projet_soumis(): void
    {
        $p = $this->projet();
        $p->update(['statut' => 'soumis']);
        $publish = route('admin.upcycling.projets.publier', $p);
        $reject = route('admin.upcycling.projets.rejeter', $p);
        $this->actingAs($p->user)->get(route('upcycling.projets.show', $p))->assertOk()->assertDontSee($publish, false)->assertDontSee($reject, false);
        $this->get(route('admin.upcycling.projets.show', $p))->assertForbidden();
        $this->get(route('admin.upcycling.projets.index'))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.upcycling.projets.show', $p))->assertOk()->assertSee($publish, false)->assertSee($reject, false)->assertSee('name="commentaire_refus" required maxlength="2000"', false);
        $p->update(['statut' => 'brouillon']);
        $this->get(route('admin.upcycling.projets.show', $p))->assertDontSee($publish, false)->assertDontSee($reject, false);
    }

    public function test_utilisateur_interdit_publication_meme_sans_middleware_admin(): void
    {
        $p = $this->projet();
        $p->update(['statut' => 'soumis']);
        $this->withoutMiddleware(EnsureUserIsAdmin::class);
        $this->actingAs($p->user)->post(route('admin.upcycling.projets.publier', $p))->assertForbidden();
        $this->assertSame(StatutProjet::Soumis, $p->fresh()->statut);
    }

    public function test_utilisateur_interdit_refus_meme_sans_middleware_admin(): void
    {
        $p = $this->projet();
        $p->update(['statut' => 'soumis']);
        $this->withoutMiddleware(EnsureUserIsAdmin::class);
        $this->actingAs($p->user)->post(route('admin.upcycling.projets.rejeter', $p), ['commentaire_refus' => 'Tentative'])->assertForbidden();
        $this->assertSame(StatutProjet::Soumis, $p->fresh()->statut);
        $this->assertNull($p->fresh()->commentaire_refus);
    }

    public function test_service_refuse_decision_directe_client(): void
    {
        $p = $this->projet();
        $p->update(['statut' => 'soumis']);
        foreach ([true, false] as $publier) {
            try {
                app(ModerationProjet::class)->decider($p, $p->user, $publier, 'Tentative directe');
                $this->fail('La Policy doit refuser cette décision.');
            } catch (AuthorizationException) {
                $this->assertSame(StatutProjet::Soumis, $p->fresh()->statut);
            }
        }
    }

    public function test_autre_auteur_interdit_modification_projet_et_etape_avec_donnees_valides(): void
    {
        $p = $this->projet();
        $before = $p->titre;
        $step = $p->etapes()->first();
        $this->actingAs(User::factory()->create())->get(route('upcycling.projets.edit', $p))->assertForbidden();
        $this->put(route('upcycling.projets.update', $p), $this->champs())->assertForbidden();
        $this->put(route('upcycling.projets.etapes.update', [$p, $step]), ['numero' => 1, 'titre' => 'Couture modifiée', 'contenu' => 'Une modification interdite des coutures.'])->assertForbidden();
        $this->assertSame($before, $p->fresh()->titre);
    }

    public function test_modification_projet_publie_requiert_nouvelle_soumission_pour_auteur_et_admin(): void
    {
        $admin = User::factory()->admin()->create();
        foreach (['upcycling', 'admin.upcycling'] as $office) {
            $p = $this->projet();
            $p->update(['statut' => 'publie', 'decision_par' => $admin->id, 'decision_le' => now(), 'soumis_le' => now()]);
            $user = $office === 'upcycling' ? $p->user : $admin;
            $this->actingAs($user)->put(route($office.'.projets.update', $p), [...$this->champs(), 'statut' => 'publie'])->assertSessionHasNoErrors();
            $this->assertSame(StatutProjet::Brouillon, $p->fresh()->statut);
            $this->assertNull($p->fresh()->decision_par);
            $this->assertNull($p->fresh()->decision_le);
            $this->assertNull($p->fresh()->soumis_le);
            $this->get(route('upcycling.index'))->assertViewHas('projets', fn ($projets) => ! $projets->contains('id', $p->id))
                ->assertDontSee(route('upcycling.projets.show', $p), false);
        }
    }

    public function test_retrait_soumission_si_projet_soumis_modifie(): void
    {
        $p = $this->projet();
        $p->update(['statut' => 'soumis', 'soumis_le' => now()]);
        $this->actingAs($p->user)->put(route('upcycling.projets.update', $p), $this->champs())->assertSessionHasNoErrors();
        $this->assertSame(StatutProjet::Brouillon, $p->fresh()->statut);
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.upcycling.projets.publier', $p))->assertSessionHasErrors('statut');
    }

    public function test_ia_ne_peut_ni_publier_ni_creer_de_projet_automatiquement(): void
    {
        $p = $this->projet();
        config(['upcycling.provider' => 'ollama', 'upcycling.ollama_base_url' => 'http://127.0.0.1:11434', 'upcycling.ollama_model' => 'qwen3:8b', 'upcycling.timeout' => 180]);
        Http::fake(['127.0.0.1:11434/api/chat' => Http::response(['done' => true, 'message' => ['content' => json_encode([...$this->champs(), 'statut' => 'publie'])]])]);
        $this->actingAs($p->user)->postJson(route('upcycling.ia.projet'), ['idee' => 'Un sac facile avec mon vieux jean'])->assertOk()->assertJsonMissingPath('proposition.statut');
        $this->assertSame(1, ProjetUpcycling::count());
        $this->assertSame(StatutProjet::Brouillon, $p->fresh()->statut);
        Http::assertSentCount(1);
    }

    public function test_decision_sur_rejete_publie_ou_brouillon_refusee_et_double_soumission_refusee(): void
    {
        $p = $this->projet();
        $admin = User::factory()->admin()->create();
        foreach (['brouillon', 'rejete', 'publie'] as $statut) {
            $p->update(['statut' => $statut]);
            $this->actingAs($admin)->post(route('admin.upcycling.projets.publier', $p))->assertSessionHasErrors('statut');
            $this->post(route('admin.upcycling.projets.rejeter', $p), ['commentaire_refus' => 'Refus'])->assertSessionHasErrors('statut');
            $this->assertSame($statut, $p->fresh()->statut->value);
        }
        $p->update(['statut' => 'brouillon']);
        $this->actingAs($p->user)->post(route('upcycling.projets.soumettre', $p))->assertSessionHasNoErrors();
        $this->post(route('upcycling.projets.soumettre', $p))->assertForbidden();
    }
}
