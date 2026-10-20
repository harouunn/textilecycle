<?php

namespace Tests\Feature;

use App\Enums\Upcycling\StatutProjet;
use App\Models\EtapeProjet;
use App\Models\ProjetUpcycling;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\Concerns\UpcyclingDatabase;
use Tests\TestCase;

class UpcyclingAdminListTest extends TestCase
{
    use UpcyclingDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Http::preventStrayRequests();
        $this->actingAs(User::factory()->admin()->create());
    }

    private function projets(): array
    {
        $projets = [];
        foreach (StatutProjet::cases() as $statut) {
            $projets[$statut->value] = ProjetUpcycling::factory()->create(['titre' => 'Projet '.$statut->value, 'statut' => $statut, 'created_at' => '2026-10-20 12:34:00']);
        }

        return $projets;
    }

    private function soumis(): ProjetUpcycling
    {
        Storage::disk('public')->put('fixture.png', 'photo');
        $p = ProjetUpcycling::factory()->create(['statut' => 'soumis', 'photo_avant' => 'fixture.png', 'photo_apres' => 'fixture.png']);
        EtapeProjet::factory()->for($p, 'projet')->create(['photo' => 'fixture.png']);

        return $p;
    }

    public function test_liste_affiche_tous_les_statuts_et_toutes_les_colonnes_par_defaut(): void
    {
        $projets = $this->projets();
        $r = $this->get(route('admin.upcycling.projets.index'))->assertOk()->assertViewHas('projets', fn ($p) => $p->total() === 4);
        foreach ($projets as $p) {
            $r->assertSeeText($p->titre)->assertSeeText($p->user->name);
        }
        foreach (['Titre', 'Auteur', 'Difficulté', 'Durée', 'Étapes', 'Statut', 'Date de création', 'Actions', '20/10/2026 12:34'] as $label) {
            $r->assertSeeText($label);
        }
        $r->assertSee('aria-label="Filtrer les projets par statut"', false);
        foreach (['Tous', 'Brouillons', 'Soumis', 'Publiés', 'Rejetés'] as $label) {
            $r->assertSeeText($label);
        }
        Http::assertNothingSent();
    }

    public function test_filtre_par_chaque_statut_et_retour_a_tous(): void
    {
        $projets = $this->projets();
        foreach ($projets as $statut => $p) {
            $this->get(route('admin.upcycling.projets.index', ['statut' => $statut]))->assertOk()
                ->assertViewHas('projets', fn ($liste) => $liste->total() === 1 && $liste->first()->id === $p->id);
        }
        $this->get(route('admin.upcycling.projets.index', ['statut' => '']))->assertOk()->assertViewHas('projets', fn ($p) => $p->total() === 4);
        $this->getJson(route('admin.upcycling.projets.index', ['statut' => 'archive']))->assertUnprocessable();
    }

    public function test_pagination_conserve_tous_les_statuts(): void
    {
        $this->projets();
        ProjetUpcycling::factory()->count(8)->brouillon()->create();
        $this->get(route('admin.upcycling.projets.index'))->assertOk()->assertViewHas('projets', fn ($p) => $p->total() === 12 && $p->count() === 10);
        $this->get(route('admin.upcycling.projets.index', ['page' => 2]))->assertOk()->assertViewHas('projets', fn ($p) => $p->total() === 12 && $p->count() === 2);
    }

    public function test_filtres_statut_preservent_recherche_et_difficulte_sans_page(): void
    {
        $p = ProjetUpcycling::factory()->create(['titre' => 'Projet denim facile', 'difficulte' => 'facile', 'statut' => 'brouillon']);
        $r = $this->get(route('admin.upcycling.projets.index', ['q' => 'denim', 'difficulte' => 'facile', 'page' => 2]))->assertOk();
        $r->assertSee(e(route('admin.upcycling.projets.index', ['q' => 'denim', 'difficulte' => 'facile', 'statut' => 'soumis'])), false);
        $this->get(route('admin.upcycling.projets.index', ['q' => 'denim', 'difficulte' => 'facile', 'statut' => 'brouillon']))->assertViewHas('projets', fn ($liste) => $liste->total() === 1 && $liste->first()->id === $p->id);
    }

    public function test_actions_publication_et_refus_uniquement_pour_projet_soumis(): void
    {
        $projets = $this->projets();
        $r = $this->get(route('admin.upcycling.projets.index'))->assertOk();
        foreach ($projets as $statut => $p) {
            $r->assertSee(route('admin.upcycling.projets.show', $p), false);
            if ($statut === 'soumis') {
                $r->assertSee('data-upcycling-publish="'.$p->id.'"', false)->assertSee(route('admin.upcycling.projets.publier', $p), false)
                    ->assertSee('data-upcycling-reject="'.$p->id.'"', false)->assertSee(route('admin.upcycling.projets.rejeter', $p), false);
            } else {
                $r->assertDontSee('data-upcycling-publish="'.$p->id.'"', false)->assertDontSee('data-upcycling-reject="'.$p->id.'"', false);
            }
        }
        $r->assertSee('onsubmit="return confirm(', false)->assertSee('name="_token"', false)->assertSee('name="commentaire_refus" required maxlength="2000"', false);
    }

    public function test_refus_exige_commentaire_puis_exclut_projet_de_galerie(): void
    {
        $p = $this->soumis();
        $this->postJson(route('admin.upcycling.projets.rejeter', $p), ['commentaire_refus' => '   '])->assertUnprocessable()->assertJsonValidationErrors('commentaire_refus');
        $this->assertSame(StatutProjet::Soumis, $p->fresh()->statut);
        $this->from(route('admin.upcycling.projets.index'))->post(route('admin.upcycling.projets.rejeter', $p), ['commentaire_refus' => 'Clarifier les coutures.'])->assertRedirect(route('admin.upcycling.projets.index'))->assertSessionHasNoErrors();
        $this->assertSame(StatutProjet::Rejete, $p->fresh()->statut);
        $this->assertSame('Clarifier les coutures.', $p->fresh()->commentaire_refus);
        $this->get(route('upcycling.index'))->assertViewHas('projets', fn ($liste) => ! $liste->contains('id', $p->id));
        Http::assertNothingSent();
    }

    public function test_erreur_refus_reouvre_uniquement_formulaire_concerne_et_conserve_saisie(): void
    {
        $p = $this->soumis();
        $autre = $this->soumis();
        $this->withSession(['_old_input' => ['moderation_projet_id' => (string) $p->id, 'commentaire_refus' => 'Mon commentaire conservé'],
            'errors' => (new ViewErrorBag)->put('default', new MessageBag(['commentaire_refus' => 'Erreur de validation.']))]);
        // Reproduire un vrai cycle de redirection avec la sérialisation JSON des sessions.
        session()->save();
        $r = $this->get(route('admin.upcycling.projets.index'))->assertOk();
        $r->assertSeeText('Mon commentaire conservé')->assertSeeText('Erreur de validation.');
        $dom = new \DOMDocument;
        @$dom->loadHTML($r->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//details[@data-upcycling-reject="'.$p->id.'" and @open]')->length);
        $this->assertSame(0, $xpath->query('//details[@data-upcycling-reject="'.$autre->id.'" and @open]')->length);
    }

    public function test_client_interdit_liste_detail_publication_et_refus(): void
    {
        $p = $this->soumis();
        $this->actingAs($p->user);
        $this->get(route('admin.upcycling.projets.index'))->assertForbidden();
        $this->get(route('admin.upcycling.projets.show', $p))->assertForbidden();
        $this->post(route('admin.upcycling.projets.publier', $p))->assertForbidden();
        $this->post(route('admin.upcycling.projets.rejeter', $p), ['commentaire_refus' => 'Non'])->assertForbidden();
        $this->assertSame(StatutProjet::Soumis, $p->fresh()->statut);
    }

    public function test_publication_depuis_liste_rend_projet_visible_galerie(): void
    {
        $p = $this->soumis();
        $this->get(route('upcycling.index'))->assertViewHas('projets', fn ($liste) => ! $liste->contains('id', $p->id));
        $this->from(route('admin.upcycling.projets.index'))->post(route('admin.upcycling.projets.publier', $p))->assertRedirect(route('admin.upcycling.projets.index'))->assertSessionHasNoErrors();
        $this->assertSame(StatutProjet::Publie, $p->fresh()->statut);
        $this->app['auth']->forgetGuards();
        $this->get(route('upcycling.index'))->assertOk()->assertViewHas('projets', fn ($liste) => $liste->contains('id', $p->id));
        Http::assertNothingSent();
    }

    public function test_fiche_complete_et_lien_menu_upcycling(): void
    {
        $p = $this->soumis();
        $p->update(['statut' => 'rejete', 'commentaire_refus' => 'Vérifier la couture avant publication.']);
        EtapeProjet::factory()->for($p, 'projet')->create(['numero' => 2]);
        $r = $this->get(route('admin.upcycling.projets.show', $p))->assertOk();
        foreach ([$p->titre, $p->description, $p->materiel_necessaire, $p->commentaire_refus, 'Rejeté'] as $text) {
            $r->assertSeeText($text);
        }
        $r->assertSee($p->photoAvantUrl(), false)->assertSee($p->photoApresUrl(), false);
        foreach ($p->etapes as $step) {
            $r->assertSeeText($step->titre)->assertSeeText($step->contenu);
        }
        $r->assertSeeInOrder($p->etapes->pluck('titre')->all());
        $r->assertSee(route('admin.upcycling.projets.index'), false)->assertSeeText('Upcycling');
        $this->get(route('admin.upcycling.projets.index'))->assertSee(route('admin.upcycling.projets.index'), false);
    }
}
