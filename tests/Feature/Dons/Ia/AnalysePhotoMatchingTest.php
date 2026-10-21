<?php

namespace Tests\Feature\Dons\Ia;

use App\Enums\Dons\DonStatut;
use App\Enums\Dons\TypeArticle;
use App\Models\AiLog;
use App\Models\Association;
use App\Models\CampagneCollecte;
use App\Models\Don;
use App\Models\User;
use App\Services\Dons\MatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AnalysePhotoMatchingTest extends TestCase
{
    use FauxGemini, RefreshDatabase;

    private const ANALYSE = [
        'est_textile' => true,
        'type_article' => 'vetements_enfant',
        'etat_general' => 'bon',
        'quantite_estimee' => 6,
        'poids_estime_kg' => 2.4,
        'description_courte' => 'Six pulls d\'enfant en laine.',
        'confiance' => 0.86,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurerGemini();
    }

    private function analyser(User $user)
    {
        return $this->actingAs($user)->post(route('dons.ia.analyser-photo'), ['photo' => $this->photo()], ['Accept' => 'application/json']);
    }

    // IA 1 — analyse photo

    public function test_photo_analysis_returns_prefill_values_and_a_token(): void
    {
        $this->geminiRepond(self::ANALYSE);

        $this->analyser(User::factory()->create())
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('analyse.type_article', 'vetements_enfant')
            ->assertJsonPath('analyse.quantite_estimee', 6)
            ->assertJsonStructure(['jeton', 'message']);

        Http::assertSent(fn (Request $r) => isset($r['contents'][0]['parts'][1]['inline_data']['data']));
        $this->assertDatabaseHas('ai_logs', ['fonctionnalite' => 'analyse_photo', 'succes' => true]);
    }

    public function test_the_validated_analysis_is_saved_with_the_donation(): void
    {
        $this->geminiRepond(self::ANALYSE);
        $user = User::factory()->create();
        $jeton = $this->analyser($user)->json('jeton');

        $this->actingAs($user)->post(route('dons.store', Association::factory()->create()), [
            'type_article' => 'vetements_enfant', 'quantite' => 5, 'poids_kg' => 2, 'etat_general' => 'bon',
            'mode_remise' => 'depot_sur_place', 'date_remise' => today()->addDay()->toDateString(), 'ia_jeton' => $jeton,
        ])->assertSessionHasNoErrors();

        $analyse = Don::query()->sole()->ia_analyse['photo'];
        $this->assertSame('vetements_enfant', $analyse['type_article']);
        $this->assertSame(0.86, $analyse['confiance']);
    }

    public function test_a_photo_without_textile_blocks_the_donation(): void
    {
        $this->geminiRepond(['est_textile' => false, 'confiance' => 0.95]);
        $user = User::factory()->create();

        $reponse = $this->analyser($user)->assertOk()->assertJsonPath('ok', true)->assertJsonPath('analyse.est_textile', false);
        $this->assertStringContainsString('ne semble pas montrer de textiles', $reponse->json('message'));

        $this->actingAs($user)->post(route('dons.store', Association::factory()->create()), [
            'type_article' => 'chaussures', 'quantite' => 1, 'etat_general' => 'bon',
            'mode_remise' => 'depot_sur_place', 'date_remise' => today()->addDay()->toDateString(), 'ia_jeton' => $reponse->json('jeton'),
        ])->assertSessionHasErrors('photo');
        $this->assertSame(0, Don::query()->count());
    }

    public function test_a_token_from_another_account_is_ignored(): void
    {
        $this->geminiRepond(self::ANALYSE);
        $jeton = $this->analyser(User::factory()->create())->json('jeton');
        $autre = User::factory()->create();

        $this->actingAs($autre)->post(route('dons.store', Association::factory()->create()), [
            'type_article' => 'chaussures', 'quantite' => 1, 'etat_general' => 'bon',
            'mode_remise' => 'depot_sur_place', 'date_remise' => today()->addDay()->toDateString(), 'ia_jeton' => $jeton,
        ])->assertSessionHasNoErrors();

        $this->assertNull(data_get(Don::query()->sole()->ia_analyse, 'photo'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function echecs(): iterable
    {
        yield 'JSON invalide' => ['geminiJsonInvalide'];
        yield 'délai dépassé' => ['geminiDelaiDepasse'];
        yield 'quota 429' => ['geminiQuotaDepasse'];
    }

    #[DataProvider('echecs')]
    public function test_photo_analysis_falls_back_gracefully(string $echec): void
    {
        $this->{$echec}();

        $this->analyser(User::factory()->create())
            ->assertOk()
            ->assertJsonPath('ok', false)
            ->assertJsonMissingPath('jeton')
            ->assertJsonPath('message', 'L\'analyse automatique est indisponible pour le moment : remplissez le formulaire vous-même.');

        $this->assertDatabaseHas('ai_logs', ['fonctionnalite' => 'analyse_photo', 'succes' => false]);
    }

    public function test_an_out_of_range_ai_answer_is_rejected(): void
    {
        $this->geminiRepond([...self::ANALYSE, 'type_article' => 'chapeaux', 'confiance' => 7]);

        $this->analyser(User::factory()->create())->assertOk()->assertJsonPath('ok', false);
        $this->assertStringContainsString('Réponse invalide', AiLog::query()->sole()->erreur);
    }

    public function test_photo_must_be_an_image(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('dons.ia.analyser-photo'), ['photo' => UploadedFile::fake()->createWithContent('note.txt', 'bonjour')], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('photo');
        Http::assertNothingSent();
    }

    public function test_ai_endpoints_are_rate_limited_to_ten_per_minute(): void
    {
        $this->geminiRepond(self::ANALYSE);
        $user = User::factory()->create();

        foreach (range(1, 10) as $i) {
            $this->analyser($user)->assertOk();
        }
        $this->analyser($user)->assertTooManyRequests();
        $this->actingAs($user)->postJson(route('dons.ia.recommandations'), ['type_article' => 'chaussures'])->assertTooManyRequests();
    }

    public function test_guests_cannot_analyse_photos(): void
    {
        $this->post(route('dons.ia.analyser-photo'), ['photo' => $this->photo()], ['Accept' => 'application/json'])->assertUnauthorized();
    }

    // IA 2 — associations recommandées

    private function associations(): array
    {
        $ariana = Association::factory()->create(['nom' => 'Enfance Ariana', 'ville' => 'Ariana', 'capacite_mensuelle_kg' => 100, 'besoins' => 'Vêtements enfants, cartables.']);
        CampagneCollecte::factory()->create(['association_id' => $ariana->id, 'type_articles_cibles' => ['vetements_enfant']]);
        $tunis = Association::factory()->create(['nom' => 'Solidarité Tunis', 'ville' => 'Tunis', 'capacite_mensuelle_kg' => 0, 'besoins' => 'Manteaux pour adultes.']);
        $pleine = Association::factory()->create(['nom' => 'Pleine Ariana', 'ville' => 'Ariana', 'capacite_mensuelle_kg' => 10, 'besoins' => 'Linge de maison.']);
        Don::factory()->statut(DonStatut::Accepte)->create(['association_id' => $pleine->id, 'poids_kg' => 12]);
        $inactive = Association::factory()->inactive()->create(['nom' => 'Fermée Ariana', 'ville' => 'Ariana', 'capacite_mensuelle_kg' => 0]);

        return compact('ariana', 'tunis', 'pleine', 'inactive');
    }

    public function test_rule_scores_follow_the_specification(): void
    {
        ['ariana' => $ariana, 'tunis' => $tunis, 'pleine' => $pleine, 'inactive' => $inactive] = $this->associations();

        $scores = app(MatchingService::class)
            ->scoresRegles(TypeArticle::VetementsEnfant, 'ariana', 2.0)
            ->keyBy('id');

        $this->assertSame(25 + 10 + 15 + 10, $scores[$ariana->id]['score_regles']); // ville, active, capacité, campagne
        $this->assertSame(10, $scores[$tunis->id]['score_regles']);                 // active seulement
        $this->assertSame(25 + 10, $scores[$pleine->id]['score_regles']);           // capacité du mois épuisée
        $this->assertSame(25, $scores[$inactive->id]['score_regles']);
    }

    public function test_recommendations_add_the_semantic_score_and_justification(): void
    {
        ['ariana' => $ariana, 'tunis' => $tunis, 'pleine' => $pleine] = $this->associations();
        $this->geminiRepond(['resultats' => [
            ['id' => $ariana->id, 'score_semantique' => 38, 'justification' => 'Recherche justement des vêtements enfants.'],
            ['id' => $tunis->id, 'score_semantique' => 5, 'justification' => 'Besoins surtout pour adultes.'],
            ['id' => $pleine->id, 'score_semantique' => 2, 'justification' => 'Recherche du linge de maison.'],
        ]]);

        $reponse = $this->actingAs(User::factory()->create())->postJson(route('dons.ia.recommandations'), [
            'type_article' => 'vetements_enfant', 'ville' => 'Ariana', 'description' => 'Pulls de 4 à 8 ans', 'poids_kg' => 2,
        ])->assertOk()->assertJsonPath('simplifiee', false)->assertJsonCount(3, 'recommandations');

        $this->assertSame([$ariana->id, $pleine->id, $tunis->id], array_column($reponse->json('recommandations'), 'id'));
        $this->assertSame(98, $reponse->json('recommandations.0.score'));
        $this->assertSame('Recherche justement des vêtements enfants.', $reponse->json('recommandations.0.justification'));
        $this->assertNotContains('Fermée Ariana', array_column($reponse->json('recommandations'), 'nom'));
        Http::assertSent(fn (Request $r) => str_contains($r['contents'][0]['parts'][0]['text'], 'Pulls de 4 à 8 ans'));
    }

    #[DataProvider('echecs')]
    public function test_recommendations_fall_back_to_rules_only(string $echec): void
    {
        ['ariana' => $ariana] = $this->associations();
        $this->{$echec}();

        $this->actingAs(User::factory()->create())->postJson(route('dons.ia.recommandations'), ['type_article' => 'vetements_enfant', 'ville' => 'Ariana'])
            ->assertOk()
            ->assertJsonPath('simplifiee', true)
            ->assertJsonPath('recommandations.0.id', $ariana->id)
            ->assertJsonPath('recommandations.0.score', 60)
            ->assertJsonPath('recommandations.0.score_semantique', null)
            ->assertJsonPath('message', 'Recommandation simplifiée (sans IA) : basée sur la ville, la capacité et les campagnes.');
    }

    public function test_recommendations_reject_ids_invented_by_the_ai(): void
    {
        $this->associations();
        $this->geminiRepond(['resultats' => [['id' => 999, 'score_semantique' => 40, 'justification' => 'Inventée.']]]);

        $this->actingAs(User::factory()->create())->postJson(route('dons.ia.recommandations'), ['type_article' => 'chaussures'])
            ->assertOk()
            ->assertJsonPath('simplifiee', true);
    }

    public function test_donation_form_shows_the_ai_features(): void
    {
        $association = Association::factory()->create();

        $this->actingAs(User::factory()->create())->get(route('dons.create', [$association, 'type_article' => 'chaussures']))
            ->assertOk()
            ->assertSee('id="photo-ia"', false)
            ->assertSee(route('dons.ia.analyser-photo'), false)
            ->assertSee('id="ia-recommandations"', false)
            ->assertSee('<option value="chaussures" selected', false)
            ->assertSeeText('suggéré par l\'IA');
    }
}
