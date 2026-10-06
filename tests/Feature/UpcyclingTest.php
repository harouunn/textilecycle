<?php

namespace Tests\Feature;

use App\Enums\Upcycling\StatutProjet;
use App\Models\EtapeProjet;
use App\Models\ProjetUpcycling;
use App\Models\User;
use Database\Seeders\UpcyclingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UpcyclingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function donneesProjet(array $surcharge = []): array
    {
        return [
            'titre' => 'Sac cabas en jean',
            'description' => 'Un sac solide réalisé à partir d\'un vieux jean.',
            'vetement_origine' => 'vieux jean',
            'resultat' => 'sac cabas',
            'difficulte' => 'facile',
            'duree_minutes' => 90,
            'materiel_necessaire' => "Ciseaux\nFil",
            'statut' => 'publie',
            'photo_avant' => UploadedFile::fake()->image('avant.png', 2, 2),
            'photo_apres' => UploadedFile::fake()->image('apres.png', 2, 2),
            ...$surcharge,
        ];
    }

    public function test_seeder_cree_six_projets_avec_trois_a_cinq_etapes(): void
    {
        $this->seed(UpcyclingSeeder::class);

        $this->assertSame(6, ProjetUpcycling::count());
        ProjetUpcycling::withCount('etapes')->get()->each(
            fn ($projet) => $this->assertTrue($projet->etapes_count >= 3 && $projet->etapes_count <= 5)
        );
    }

    public function test_galerie_affiche_uniquement_les_projets_publies_et_filtre_par_difficulte(): void
    {
        ProjetUpcycling::factory()->create(['titre' => 'Projet facile publié', 'difficulte' => 'facile']);
        ProjetUpcycling::factory()->create(['titre' => 'Projet difficile publié', 'difficulte' => 'difficile']);
        ProjetUpcycling::factory()->brouillon()->create(['titre' => 'Projet en brouillon']);

        $this->get(route('upcycling.index'))
            ->assertOk()
            ->assertSeeText('Projet facile publié')
            ->assertSeeText('Projet difficile publié')
            ->assertDontSeeText('Projet en brouillon');

        $this->get(route('upcycling.index', ['difficulte' => 'facile']))
            ->assertSeeText('Projet facile publié')
            ->assertDontSeeText('Projet difficile publié');
    }

    public function test_page_projet_affiche_les_etapes_dans_l_ordre(): void
    {
        $projet = ProjetUpcycling::factory()->create();
        EtapeProjet::factory()->for($projet, 'projet')->create(['numero' => 2, 'titre' => 'Deuxième étape']);
        EtapeProjet::factory()->for($projet, 'projet')->create(['numero' => 1, 'titre' => 'Première étape']);

        $this->get(route('upcycling.projets.show', $projet))
            ->assertOk()
            ->assertSeeTextInOrder(['Première étape', 'Deuxième étape']);
    }

    public function test_brouillon_invisible_pour_les_autres_mais_visible_par_son_auteur(): void
    {
        $projet = ProjetUpcycling::factory()->brouillon()->create();

        $this->get(route('upcycling.projets.show', $projet))->assertForbidden();
        $this->actingAs($projet->user)->get(route('upcycling.projets.show', $projet))->assertOk();
    }

    public function test_utilisateur_propose_un_projet_en_brouillon_meme_s_il_envoie_publie(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('upcycling.projets.store'), $this->donneesProjet([
                // PNG 1x1 réel (évite la dépendance à l'extension GD)
                'photo_avant' => UploadedFile::fake()->createWithContent('avant.png', base64_decode(
                    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
                )),
            ]))
            ->assertRedirect();

        $projet = ProjetUpcycling::firstOrFail();
        $this->assertSame($user->id, $projet->user_id);
        $this->assertSame(StatutProjet::Brouillon, $projet->statut);
        Storage::disk('public')->assertExists($projet->photo_avant);
    }

    public function test_validation_en_francais(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('upcycling.projets.store'), $this->donneesProjet(['duree_minutes' => 2, 'titre' => '']))
            ->assertSessionHasErrorsIn('projet', [
                'duree_minutes' => 'La durée doit être d’au moins 5 minutes.',
                'titre' => 'Le titre est obligatoire.',
            ]);
    }

    public function test_utilisateur_ne_peut_modifier_ni_supprimer_le_projet_d_un_autre(): void
    {
        $projet = ProjetUpcycling::factory()->create();
        $autre = User::factory()->create();

        $this->actingAs($autre)->get(route('upcycling.projets.edit', $projet))->assertForbidden();
        $this->actingAs($autre)->put(route('upcycling.projets.update', $projet), $this->donneesProjet())->assertForbidden();
        $this->actingAs($autre)->delete(route('upcycling.projets.destroy', $projet))->assertForbidden();
        $this->actingAs($autre)->post(route('upcycling.projets.etapes.store', $projet), [])->assertForbidden();

        $this->assertModelExists($projet);
    }

    public function test_mes_projets_liste_uniquement_mes_projets(): void
    {
        $user = User::factory()->create();
        ProjetUpcycling::factory()->for($user)->create(['titre' => 'Mon projet à moi']);
        ProjetUpcycling::factory()->create(['titre' => 'Le projet du voisin']);

        $this->actingAs($user)->get(route('upcycling.mes-projets'))
            ->assertOk()
            ->assertSeeText('Mon projet à moi')
            ->assertDontSeeText('Le projet du voisin');
    }

    public function test_numero_d_etape_unique_par_projet(): void
    {
        $projet = ProjetUpcycling::factory()->create();
        EtapeProjet::factory()->for($projet, 'projet')->create(['numero' => 1]);
        $autreProjet = ProjetUpcycling::factory()->create();

        $etape = ['numero' => 1, 'titre' => 'Découper', 'contenu' => 'Découpez les jambes du jean.',
            'photo' => UploadedFile::fake()->image('etape.png', 2, 2)];

        $this->actingAs($projet->user)
            ->post(route('upcycling.projets.etapes.store', $projet), $etape)
            ->assertSessionHasErrorsIn('etape', ['numero' => 'Ce numéro est déjà utilisé pour une étape de ce projet.']);

        $this->actingAs($autreProjet->user)
            ->post(route('upcycling.projets.etapes.store', $autreProjet), $etape)
            ->assertSessionHasNoErrors();
    }

    public function test_admin_liste_recherche_et_filtres(): void
    {
        $admin = User::factory()->create();
        ProjetUpcycling::factory()->for(User::factory()->state(['name' => 'Auteur couture']))->create(['titre' => 'Tablier chemise', 'vetement_origine' => 'chemise', 'resultat' => 'tablier', 'statut' => 'publie', 'difficulte' => 'moyen']);
        ProjetUpcycling::factory()->for(User::factory()->state(['name' => 'Auteur laine']))->brouillon()->create(['titre' => 'Coussin pull', 'vetement_origine' => 'pull en laine', 'resultat' => 'coussin', 'difficulte' => 'facile']);

        $this->actingAs($admin)->get(route('admin.upcycling.projets.index'))
            ->assertOk()->assertSeeText('Tablier chemise')->assertSeeText('Coussin pull');

        $this->actingAs($admin)->get(route('admin.upcycling.projets.index', ['q' => 'tablier']))
            ->assertSeeText('Tablier chemise')->assertDontSeeText('Coussin pull');

        $this->actingAs($admin)->get(route('admin.upcycling.projets.index', ['statut' => 'brouillon', 'difficulte' => 'facile']))
            ->assertSeeText('Coussin pull')->assertDontSeeText('Tablier chemise');
    }

    public function test_admin_crud_projet_et_etapes(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.upcycling.projets.store'), $this->donneesProjet())->assertRedirect();
        $projet = ProjetUpcycling::firstOrFail();
        $this->assertSame(StatutProjet::Publie, $projet->statut);

        $this->actingAs($admin)->post(route('admin.upcycling.projets.etapes.store', $projet), [
            'numero' => 1, 'titre' => 'Découper', 'contenu' => 'Découpez les jambes du jean.',
            'photo' => UploadedFile::fake()->image('etape.png', 2, 2),
        ])->assertRedirect(route('admin.upcycling.projets.show', $projet));

        $etape = $projet->etapes()->firstOrFail();
        $this->actingAs($admin)->put(route('admin.upcycling.projets.etapes.update', [$projet, $etape]), [
            'numero' => 1, 'titre' => 'Découper le jean', 'contenu' => 'Découpez les jambes du jean.',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Découper le jean', $etape->fresh()->titre);

        $this->actingAs($admin)->get(route('admin.upcycling.projets.show', $projet))
            ->assertOk()->assertSeeText('Découper le jean');

        $this->actingAs($admin)->delete(route('admin.upcycling.projets.destroy', $projet))
            ->assertRedirect(route('admin.upcycling.projets.index'));
        $this->assertModelMissing($projet);
        $this->assertModelMissing($etape);
    }

    public function test_toutes_les_pages_de_formulaire_s_affichent(): void
    {
        $projet = ProjetUpcycling::factory()->create();
        $etape = EtapeProjet::factory()->for($projet, 'projet')->create();
        $user = $projet->user;

        foreach ([
            route('admin.upcycling.projets.create'),
            route('admin.upcycling.projets.edit', $projet),
            route('admin.upcycling.projets.etapes.edit', [$projet, $etape]),
            route('upcycling.projets.create'),
            route('upcycling.projets.edit', $projet),
            route('upcycling.projets.etapes.index', $projet),
            route('upcycling.projets.etapes.edit', [$projet, $etape]),
        ] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }

    public function test_admin_statut_obligatoire(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.upcycling.projets.store'), $this->donneesProjet(['statut' => '']))
            ->assertSessionHasErrorsIn('projet', ['statut' => 'Le statut est obligatoire.']);
    }
}
