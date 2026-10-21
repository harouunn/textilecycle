<?php

namespace Tests\Feature\Dons\Ia;

use App\Enums\Dons\DonStatut;
use App\Models\Association;
use App\Models\Don;
use App\Models\User;
use App\Services\Dons\DonWorkflowService;
use App\Services\Dons\Ia\AssistantDonateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AssistantRedactionTest extends TestCase
{
    use FauxGemini, RefreshDatabase;

    private Association $ariana;

    private Association $tunis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurerGemini();

        $this->ariana = Association::factory()->create(['nom' => 'Chaleur Ariana', 'ville' => 'Ariana', 'besoins' => 'Manteaux et couvertures pour l\'hiver.']);
        $this->tunis = Association::factory()->create(['nom' => 'Rentrée Tunis', 'ville' => 'Tunis', 'besoins' => 'Cartables et chaussures enfants.']);
        Association::factory()->inactive()->create(['nom' => 'Fermée Ariana', 'ville' => 'Ariana', 'besoins' => 'Manteaux.']);
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

    // IA 3 — assistant

    public function test_context_is_built_from_the_question_keywords_and_city(): void
    {
        [$associations] = app(AssistantDonateur::class)->contexte('Qui a besoin de manteaux à Ariana ?');

        $this->assertSame(['Chaleur Ariana'], $associations->pluck('nom')->all());
        $this->assertLessThanOrEqual(AssistantDonateur::LIGNES_CONTEXTE, $associations->count());
    }

    public function test_assistant_answers_from_the_context_with_links(): void
    {
        $this->geminiRepond(['reponse' => 'Chaleur Ariana recherche des manteaux.', 'association_ids' => [$this->ariana->id], 'hors_sujet' => false]);

        $this->postJson(route('dons.ia.assistant'), ['question' => 'Qui a besoin de manteaux à Ariana ?'])
            ->assertOk()
            ->assertJsonPath('reponse', 'Chaleur Ariana recherche des manteaux.')
            ->assertJsonPath('associations.0.nom', 'Chaleur Ariana')
            ->assertJsonPath('associations.0.url', route('dons.associations.show', $this->ariana))
            ->assertJsonPath('simplifiee', false);

        Http::assertSent(function (Request $r) {
            $texte = $r['contents'][0]['parts'][0]['text'];

            return str_contains($texte, 'Chaleur Ariana') && ! str_contains($texte, 'Fermée Ariana')
                && str_contains($r['systemInstruction']['parts'][0]['text'], 'UNIQUEMENT à partir du CONTEXTE');
        });
    }

    public function test_an_association_outside_the_context_is_never_linked(): void
    {
        // L'IA cite une association qui n'était pas dans le contexte : la réponse est rejetée, on passe au mode simplifié.
        $this->geminiRepond(['reponse' => 'Allez voir Rentrée Tunis.', 'association_ids' => [$this->tunis->id], 'hors_sujet' => false]);

        $this->postJson(route('dons.ia.assistant'), ['question' => 'Qui a besoin de manteaux à Ariana ?'])
            ->assertOk()
            ->assertJsonPath('simplifiee', true)
            ->assertJsonPath('associations.0.id', $this->ariana->id)
            ->assertJsonMissing(['nom' => 'Rentrée Tunis']);
    }

    public function test_off_topic_questions_are_declined_without_links(): void
    {
        $this->geminiRepond(['reponse' => 'Je ne peux vous aider que pour les dons de textiles.', 'association_ids' => [$this->ariana->id], 'hors_sujet' => true]);

        $this->postJson(route('dons.ia.assistant'), ['question' => 'Quel temps fera-t-il demain ?'])
            ->assertOk()
            ->assertJsonPath('hors_sujet', true)
            ->assertJsonCount(0, 'associations');
    }

    #[DataProvider('echecs')]
    public function test_assistant_falls_back_to_a_list_of_associations(string $echec): void
    {
        $this->{$echec}();

        $this->postJson(route('dons.ia.assistant'), ['question' => 'Qui a besoin de manteaux à Ariana ?'])
            ->assertOk()
            ->assertJsonPath('simplifiee', true)
            ->assertJsonPath('associations.0.nom', 'Chaleur Ariana');
    }

    public function test_only_the_last_six_messages_are_kept(): void
    {
        Http::fake([self::URL_GEMINI => Http::sequence()
            ->push(...[$this->reponseGeminiBrut('Réponse 1')])
            ->push(...[$this->reponseGeminiBrut('Réponse 2')])
            ->push(...[$this->reponseGeminiBrut('Réponse 3')])
            ->push(...[$this->reponseGeminiBrut('Réponse 4')])]);

        foreach (range(1, 4) as $i) {
            $this->postJson(route('dons.ia.assistant'), ['question' => "Question numéro {$i}"])->assertOk();
        }

        $historique = session(AssistantDonateur::CLE_SESSION);
        $this->assertCount(6, $historique);
        $this->assertSame('Question numéro 2', $historique[0]['texte']);
        $this->assertSame('Réponse 4', $historique[5]['texte']);
        Http::assertSent(fn (Request $r) => str_contains($r['contents'][0]['parts'][0]['text'], 'Donateur : Question numéro 3'));

        $this->deleteJson(route('dons.ia.assistant.oublier'))->assertOk();
        $this->assertNull(session(AssistantDonateur::CLE_SESSION));
    }

    public function test_assistant_widget_is_on_the_module_pages(): void
    {
        $this->get(route('dons.index'))->assertOk()->assertSee('id="assistant-dons"', false)->assertSee(route('dons.ia.assistant'), false);
        $this->get(route('dons.campagnes.index'))->assertOk()->assertSee('id="assistant-dons"', false);
    }

    public function test_question_is_validated(): void
    {
        $this->postJson(route('dons.ia.assistant'), ['question' => str_repeat('a', 501)])->assertJsonValidationErrors('question');
        Http::assertNothingSent();
    }

    /**
     * @return array<mixed>
     */
    private function reponseGeminiBrut(string $texte): array
    {
        return ['candidates' => [['content' => ['parts' => [['text' => json_encode(['reponse' => $texte, 'association_ids' => [], 'hors_sujet' => false])]]]]]];
    }

    // IA 4 — rédaction

    public function test_admin_generates_an_association_description_without_saving_it(): void
    {
        $texte = str_repeat('Chaleur Ariana accompagne les familles de la région. ', 3);
        $this->geminiRepond(['texte' => $texte]);
        $avant = $this->ariana->description;

        $this->actingAs(User::factory()->admin()->create())->postJson(route('dons.ia.rediger'), [
            'type' => 'description', 'association_id' => $this->ariana->id, 'nom' => 'Chaleur Ariana', 'ville' => 'Ariana', 'besoins' => 'Manteaux',
        ])->assertOk()->assertJsonPath('ok', true)->assertJsonPath('ia', true)->assertJsonPath('texte', trim($texte));

        $this->assertSame($avant, $this->ariana->fresh()->description);
    }

    public function test_responsable_generates_and_saves_their_presentation(): void
    {
        $responsable = User::factory()->create();
        $this->ariana->update(['responsable_id' => $responsable->id]);
        $this->geminiRepond(['texte' => str_repeat('Une présentation écrite par l\'IA pour Chaleur Ariana. ', 3)]);

        $this->actingAs($responsable)->get(route('dons.espace.presentation.edit', $this->ariana))->assertOk()->assertSeeText('Générer avec l\'IA');
        $this->actingAs($responsable)->postJson(route('dons.ia.rediger'), [
            'type' => 'description', 'association_id' => $this->ariana->id, 'nom' => 'Chaleur Ariana', 'ville' => 'Ariana',
        ])->assertOk()->assertJsonPath('ia', true);

        $this->actingAs($responsable)->put(route('dons.espace.presentation.update', $this->ariana), [
            'description' => 'Texte relu et corrigé par le responsable.', 'besoins' => 'Manteaux, couvertures.',
        ])->assertRedirect();
        $this->assertSame('Texte relu et corrigé par le responsable.', $this->ariana->fresh()->description);
    }

    public function test_writing_is_authorised_per_association(): void
    {
        $responsable = User::factory()->create();
        $this->tunis->update(['responsable_id' => $responsable->id]);
        Http::fake();

        $this->actingAs($responsable)->postJson(route('dons.ia.rediger'), ['type' => 'description', 'association_id' => $this->ariana->id, 'nom' => 'X', 'ville' => 'Y'])->assertForbidden();
        $this->actingAs($responsable)->postJson(route('dons.ia.rediger'), ['type' => 'campagne', 'titre' => 'Hiver', 'objectif_kg' => 50])->assertForbidden();
        $this->actingAs($responsable)->postJson(route('dons.ia.rediger'), ['type' => 'rapport', 'association_id' => $this->ariana->id, 'mois' => now()->format('Y-m')])->assertForbidden();
        $this->actingAs(User::factory()->create())->postJson(route('dons.ia.rediger'), ['type' => 'description', 'nom' => 'X', 'ville' => 'Y'])->assertForbidden();
        $this->actingAs($responsable)->get(route('dons.espace.presentation.edit', $this->ariana))->assertForbidden();
        Http::assertNothingSent();
    }

    #[DataProvider('echecs')]
    public function test_description_writing_failure_returns_a_message(string $echec): void
    {
        $this->{$echec}();

        $this->actingAs(User::factory()->admin()->create())->postJson(route('dons.ia.rediger'), ['type' => 'description', 'nom' => 'Nouvelle', 'ville' => 'Tunis'])
            ->assertOk()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('texte', null);
    }

    public function test_campaign_text_uses_title_goal_and_types_and_falls_back_to_a_template(): void
    {
        $admin = User::factory()->admin()->create();
        $this->geminiRepond(['texte' => str_repeat('Rejoignez la campagne Grand froid. ', 3)]);
        $this->actingAs($admin)->postJson(route('dons.ia.rediger'), [
            'type' => 'campagne', 'titre' => 'Grand froid', 'objectif_kg' => 300, 'type_articles_cibles' => ['linge_maison'], 'association_id' => $this->ariana->id,
        ])->assertOk()->assertJsonPath('ia', true);
        Http::assertSent(fn (Request $r) => str_contains($r['contents'][0]['parts'][0]['text'], 'Objectif : 300 kg. Articles recherchés : Linge de maison'));

        $this->geminiQuotaDepasse();
        $this->actingAs($admin)->postJson(route('dons.ia.rediger'), ['type' => 'campagne', 'titre' => 'Grand froid', 'objectif_kg' => 300])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('ia', false)
            ->assertJsonFragment(['message' => 'Rédaction IA indisponible : voici un modèle à compléter.']);
    }

    public function test_monthly_report_uses_real_statistics(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        foreach ([4.5, 8] as $kg) {
            $don = Don::factory()->statut(DonStatut::Accepte)->create(['association_id' => $this->ariana->id, 'poids_kg' => $kg, 'type_article' => 'linge_maison']);
            app(DonWorkflowService::class)->transition($don, DonStatut::Recu, null, $admin);
        }
        Don::factory()->statut(DonStatut::Accepte)->create(['association_id' => $this->ariana->id, 'poids_kg' => 50]); // pas reçu
        $this->geminiRepond(['texte' => str_repeat('Ce mois-ci, merci à tous les donateurs de Chaleur Ariana ! ', 3)]);

        $this->actingAs($admin)->postJson(route('dons.ia.rediger'), ['type' => 'rapport', 'association_id' => $this->ariana->id, 'mois' => now()->format('Y-m')])
            ->assertOk()
            ->assertJsonPath('ia', true)
            ->assertJsonPath('stats.dons', 2)
            ->assertJsonPath('stats.kg', 12.5);
        Http::assertSent(fn (Request $r) => str_contains($r['contents'][0]['parts'][0]['text'], 'Dons reçus : 2. Textiles reçus : 12.5 kg.'));

        // La même demande serait servie par le cache de 24 h : on le vide pour simuler une panne de Gemini.
        Cache::flush();
        $this->geminiDelaiDepasse();
        $reponse = $this->actingAs($admin)->postJson(route('dons.ia.rediger'), ['type' => 'rapport', 'association_id' => $this->ariana->id, 'mois' => now()->format('Y-m')])
            ->assertOk()->assertJsonPath('ia', false);
        $this->assertStringContainsString('a reçu 2 don(s) via TexTileCycle, soit 12.5 kg', $reponse->json('texte'));

        $this->actingAs($admin)->get(route('dons.espace.rapport', $this->ariana))->assertOk()->assertSeeText('12,5 kg');
    }

    public function test_admin_forms_show_the_generate_buttons(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.dons.associations.edit', $this->ariana))->assertOk()->assertSee('data-ia-rediger', false)->assertSee('data-type="description"', false);
        $this->actingAs($admin)->get(route('admin.dons.campagnes.create'))->assertOk()->assertSee('data-type="campagne"', false);
        $this->actingAs($admin)->get(route('admin.dons.associations.show', $this->ariana))->assertOk()->assertSee('data-type="rapport"', false);
    }
}
