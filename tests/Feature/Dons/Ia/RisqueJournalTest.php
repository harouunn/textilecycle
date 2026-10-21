<?php

namespace Tests\Feature\Dons\Ia;

use App\Jobs\Dons\AnalyserRisqueDon;
use App\Models\AiLog;
use App\Models\Association;
use App\Models\Don;
use App\Models\User;
use App\Services\Dons\Ia\DetecteurRisque;
use App\Services\Dons\Ia\IaDons;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RisqueJournalTest extends TestCase
{
    use FauxGemini, RefreshDatabase;

    private Association $association;

    private User $donateur;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurerGemini();
        $this->association = Association::factory()->create();
        $this->donateur = User::factory()->create();
    }

    private function proposer(array $surcharge = []): void
    {
        $this->actingAs($this->donateur)->post(route('dons.store', $this->association), [
            'type_article' => 'chaussures', 'quantite' => 4, 'poids_kg' => 3, 'etat_general' => 'bon',
            'mode_remise' => 'depot_sur_place', 'date_remise' => today()->addDay()->toDateString(),
            'message' => 'Quatre paires en bon état.',
            ...$surcharge,
        ])->assertSessionHasNoErrors();
    }

    private function don(array $attributs = []): Don
    {
        return Don::factory()->propose()->create(['user_id' => $this->donateur->id, 'association_id' => $this->association->id,
            'type_article' => 'chaussures', 'quantite' => 4, 'poids_kg' => 3, 'message' => 'Quatre paires en bon état.', ...$attributs]);
    }

    // Règles

    public function test_a_normal_donation_has_a_low_score(): void
    {
        $this->assertSame(['score' => 0, 'raisons' => []], app(DetecteurRisque::class)->evaluer($this->don()));
    }

    /**
     * @return iterable<string, array{array<string, mixed>, int}>
     */
    public static function signaux(): iterable
    {
        yield 'quantité > 200' => [['quantite' => 250, 'poids_kg' => 100], 30];
        yield 'poids par article trop faible' => [['quantite' => 100, 'poids_kg' => 0.5], 25];
        yield 'poids par article trop élevé' => [['quantite' => 2, 'poids_kg' => 40], 25];
        yield 'message vide' => [['message' => null], 5];
        yield 'lien dans le message' => [['message' => 'Promo sur https://exemple.test'], 20];
        yield 'message en majuscules' => [['message' => 'URGENT DONNEZ MAINTENANT'], 20];
    }

    #[DataProvider('signaux')]
    public function test_each_rule_adds_its_weight(array $attributs, int $score): void
    {
        $this->assertSame($score, app(DetecteurRisque::class)->evaluer($this->don($attributs))['score']);
    }

    public function test_repeated_and_duplicate_donations_raise_the_score(): void
    {
        foreach (range(1, 3) as $i) {
            $this->don(['quantite' => $i]);
        }
        $this->don(['quantite' => 9]);
        $dernier = $this->don(['quantite' => 9]);

        $resultat = app(DetecteurRisque::class)->evaluer($dernier);
        $this->assertSame(25 + 30, $resultat['score']);
        $this->assertContains('Doublon probable d\'un don de la dernière heure.', $resultat['raisons']);
    }

    public function test_score_is_capped_at_100(): void
    {
        foreach (range(1, 4) as $i) {
            $this->don(['quantite' => 300, 'poids_kg' => 1, 'message' => 'https://spam.test']);
        }

        $this->assertSame(100, app(DetecteurRisque::class)->evaluer(Don::query()->latest('id')->first())['score']);
    }

    // Job

    public function test_the_job_is_queued_when_a_donor_proposes_a_donation(): void
    {
        Queue::fake();

        $this->proposer();

        Queue::assertPushed(AnalyserRisqueDon::class, fn (AnalyserRisqueDon $job) => $job->don->is(Don::query()->sole()));
    }

    public function test_low_risk_donations_are_scored_without_calling_the_ai(): void
    {
        Http::fake();

        $this->proposer();

        $don = Don::query()->sole();
        $this->assertSame(0, $don->score_risque);
        $this->assertSame('regles', $don->ia_analyse['risque']['source']);
        Http::assertNothingSent();
    }

    public function test_risky_donations_get_an_ai_explanation(): void
    {
        $this->geminiRepond(['explication' => 'La quantité et le poids déclarés semblent incohérents : à confirmer avec le donateur.']);

        $this->proposer(['quantite' => 300, 'poids_kg' => 1]);

        $don = Don::query()->sole();
        $this->assertSame(55, $don->score_risque);
        $this->assertTrue($don->estARisque());
        $this->assertSame('ia', $don->ia_analyse['risque']['source']);
        $this->assertSame('La quantité et le poids déclarés semblent incohérents : à confirmer avec le donateur.', $don->ia_analyse['risque']['explication']);
        $this->assertDatabaseHas('ai_logs', ['fonctionnalite' => 'risque', 'succes' => true, 'user_id' => $this->donateur->id]);
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
    public function test_without_ai_the_explanation_lists_the_rules(string $echec): void
    {
        $this->{$echec}();

        $this->proposer(['quantite' => 300, 'poids_kg' => 1]);

        $risque = Don::query()->sole()->ia_analyse['risque'];
        $this->assertSame('regles', $risque['source']);
        $this->assertStringContainsString('Quantité inhabituelle (300 articles).', $risque['explication']);
    }

    public function test_photo_analysis_is_kept_when_the_risk_is_added(): void
    {
        Http::fake();
        $don = $this->don();
        $don->forceFill(['ia_analyse' => ['photo' => ['est_textile' => true]]])->save();

        (new AnalyserRisqueDon($don))->handle(app(DetecteurRisque::class), app(IaDons::class));

        $this->assertTrue($don->fresh()->ia_analyse['photo']['est_textile']);
        $this->assertArrayHasKey('risque', $don->fresh()->ia_analyse);
    }

    // Back office

    public function test_admin_list_flags_and_filters_risky_donations(): void
    {
        $admin = User::factory()->admin()->create();
        $risque = $this->don(['message' => 'Don à risque']);
        $risque->forceFill(['score_risque' => 70, 'ia_analyse' => ['risque' => ['score' => 70, 'raisons' => ['Doublon'], 'explication' => 'À confirmer.', 'source' => 'ia']]])->save();
        $sur = $this->don();
        $sur->forceFill(['score_risque' => 5])->save();

        $this->actingAs($admin)->get(route('admin.dons.dons.index'))->assertOk()->assertSeeText('À vérifier');
        $this->actingAs($admin)->get(route('admin.dons.dons.index', ['risque' => 1]))->assertOk()
            ->assertSee(route('admin.dons.dons.show', $risque), false)
            ->assertDontSee(route('admin.dons.dons.show', $sur), false);
        $this->actingAs($admin)->get(route('admin.dons.dons.show', $risque))->assertOk()->assertSeeText(['Analyse automatique', 'Risque : 70/100', 'À confirmer.']);
    }

    public function test_ai_journal_shows_the_success_rate(): void
    {
        AiLog::query()->create(['fonctionnalite' => 'assistant', 'duree_ms' => 800, 'succes' => true]);
        AiLog::query()->create(['fonctionnalite' => 'assistant', 'duree_ms' => 1200, 'succes' => true]);
        AiLog::query()->create(['fonctionnalite' => 'matching', 'duree_ms' => 20000, 'succes' => false, 'erreur' => 'Gemini injoignable ou délai dépassé.']);
        AiLog::query()->create(['fonctionnalite' => 'analyse_photo', 'duree_ms' => 900, 'succes' => true]);

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.dons.journal-ia'))
            ->assertOk()
            ->assertSeeText(['Journal IA', '75,0 %', 'sur 4 appel(s)', 'Assistant donateur', '1 000 ms', 'Gemini injoignable ou délai dépassé.']);

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.dons.journal-ia', ['succes' => '0']))
            ->assertOk()->assertSee('<span class="text-error">Échec</span>', false)->assertDontSee('<span class="text-success">Réussi</span>', false);
    }

    public function test_ai_journal_is_reserved_to_admins(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.dons.journal-ia'))->assertForbidden();
    }
}
