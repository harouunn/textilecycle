<?php

namespace Tests\Feature;

use App\Enums\Upcycling\Difficulte;
use App\Models\ProjetUpcycling;
use App\Services\Upcycling\RecommandationProjets;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\UpcyclingDatabase;
use Tests\TestCase;

class UpcyclingRecommendationTest extends TestCase
{
    use UpcyclingDatabase;

    public function test_recommandation_classe_jean_sac_facile_avant_projet_sans_rapport(): void
    {
        $bon = ProjetUpcycling::factory()->create(['titre' => 'Sac cabas en jean', 'vetement_origine' => 'Jean denim', 'resultat' => 'Sac cabas', 'description' => 'Transformer un jean en sac facile.', 'materiel_necessaire' => 'Fil et ciseaux', 'difficulte' => 'facile']);
        ProjetUpcycling::factory()->create(['titre' => 'Echarpe chaude', 'vetement_origine' => 'Pull laine', 'resultat' => 'Echarpe', 'description' => 'Tricoter une écharpe chaude.', 'materiel_necessaire' => 'Laine et aiguilles', 'difficulte' => 'difficile']);
        Http::preventStrayRequests();
        $r = app(RecommandationProjets::class)->chercher('J’ai un vieux jean et je veux créer un sac facile.');
        $this->assertSame($bon->id, $r->first()->id);
        $this->assertGreaterThan(0, $r->first()->similarite);
        $this->assertLessThanOrEqual(1.000001, $r->first()->similarite);
        Http::assertNothingSent();
        $this->get(route('upcycling.index', ['idee' => 'jean sac facile']))->assertOk()->assertSeeText('Sac cabas en jean');
    }

    public function test_tous_les_champs_sont_utilises_et_non_publies_exclus(): void
    {
        foreach (['titre', 'vetement_origine', 'resultat', 'description', 'materiel_necessaire'] as $champ) {
            $p = ProjetUpcycling::factory()->create([$champ => 'motuniquerecommandation']);
            $ids = app(RecommandationProjets::class)->chercher('motuniquerecommandation')->pluck('id')->all();
            $this->assertContains($p->id, $ids);
        }
        foreach (['brouillon', 'soumis', 'rejete'] as $statut) {
            ProjetUpcycling::factory()->create(['statut' => $statut, 'titre' => 'motsecretpublicationunique']);
        }
        $this->assertCount(0, app(RecommandationProjets::class)->chercher('motsecretpublicationunique'));
        $this->get(route('upcycling.index', ['idee' => 'xyzzyquux']))->assertDontSeeText('motsecretpublicationunique');
    }

    public function test_recherche_vide_inconnue_et_filtres(): void
    {
        ProjetUpcycling::factory()->create(['titre' => 'Un sac en jean', 'difficulte' => 'facile']);
        $service = app(RecommandationProjets::class);
        $this->assertCount(0, $service->chercher(''));
        $this->assertCount(0, $service->chercher('xyzzyquux'));
        $this->assertCount(0, $service->chercher('jean', Difficulte::Difficile));
        $this->get(route('upcycling.index', ['idee' => 'xyzzyquux']))->assertOk()->assertSeeText('Aucune correspondance.');
        $this->getJson(route('upcycling.index', ['idee' => ['bad']]))->assertUnprocessable();
    }
}
