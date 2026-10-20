<?php

namespace Tests\Feature;

use App\Models\EtapeProjet;
use App\Models\ProjetUpcycling;
use App\Models\User;
use App\Services\Upcycling\AssistantIA;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\UpcyclingDatabase;
use Tests\TestCase;

class UpcyclingTranslationTest extends TestCase
{
    use UpcyclingDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['upcycling.provider' => 'ollama', 'upcycling.ollama_base_url' => 'http://127.0.0.1:11434', 'upcycling.ollama_model' => 'qwen3:8b', 'upcycling.timeout' => 180]);
        Http::preventStrayRequests();
        $this->actingAs(User::factory()->create());
    }

    private function projet(): ProjetUpcycling
    {
        $p = ProjetUpcycling::factory()->for(auth()->user())->brouillon()->create();
        // Numéros discontinus et IDs différents des numéros : ne pas confondre ces deux notions.
        foreach ([2, 7] as $numero) {
            EtapeProjet::factory()->for($p, 'projet')->create(['numero' => $numero]);
        }

        return $p;
    }

    private function traduction(): array
    {
        return ['project' => ['titre' => 'A denim bag', 'description' => 'A complete tutorial for sewing a bag from denim.', 'vetement_origine' => 'Old jeans', 'resultat' => 'Tote bag', 'materiel_necessaire' => 'Thread, needle and scissors'],
            'steps' => [['numero' => 7, 'titre' => 'Sew the handles', 'contenu' => 'Sew both handles firmly to the bag.'], ['numero' => 2, 'titre' => 'Cut the denim', 'contenu' => 'Cut the denim carefully along the seams.']]];
    }

    private function fake(mixed $content): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['127.0.0.1:11434/api/chat' => Http::response(['done' => true, 'done_reason' => 'stop', 'message' => ['content' => is_string($content) ? $content : json_encode($content)]])]);
    }

    public function test_project_et_plusieurs_etapes_traduits_et_apercu_sans_sauvegarde(): void
    {
        $p = $this->projet();
        $before = AssistantIA::source($p);
        $this->fake($this->traduction());
        $r = $this->postJson(route('upcycling.ia.traduction', $p), ['langue' => 'en'])->assertOk()
            ->assertJsonPath('proposition.texte.titre', 'A denim bag')->assertJsonCount(2, 'proposition.texte.etapes')
            ->assertJsonPath('proposition.texte.etapes.0.numero', 2)->assertJsonPath('proposition.texte.etapes.1.numero', 7);
        $this->assertSame($p->etapes()->first()->id, $r->json('proposition.texte.etapes.0.id'));
        $this->assertSame($before, AssistantIA::source($p->fresh()));
        $this->assertNull($p->fresh()->traductions);
        $this->assertSame(0, $p->etapes()->whereNotNull('traductions')->count());
        Http::assertSentCount(1);
        Http::assertSent(function ($r) {
            $input = json_decode($r['messages'][1]['content'], true);

            return $r->url() === 'http://127.0.0.1:11434/api/chat' && isset($input['project'], $input['steps'])
                && count($input['steps']) === 2 && ! isset($input['steps'][0]['id'], $input['steps'][0]['photo'])
                && $r['format']['properties']['steps']['minItems'] === 2 && $r['format']['properties']['steps']['maxItems'] === 2
                && $r['format']['properties']['steps']['items']['properties']['numero']['enum'] === [2, 7];
        });
    }

    public function test_confirmation_modifiable_conserve_originaux_et_relations(): void
    {
        $p = $this->projet();
        $before = AssistantIA::source($p);
        $this->fake($this->traduction());
        $r = $this->postJson(route('upcycling.ia.traduction', $p), ['langue' => 'en'])->assertOk();
        $texte = $r->json('proposition.texte');
        $texte['titre'] = 'Corrected title';
        $texte['etapes'][0]['contenu'] = 'Corrected step after preview.';
        $this->put(route('upcycling.ia.traduction.confirmer', [$p, 'en']), ['jeton' => $r->json('proposition.jeton'), 'texte' => $texte])->assertSessionHasNoErrors();
        $this->assertSame($before, AssistantIA::source($p->fresh()));
        $this->assertSame('Corrected title', $p->fresh()->traductions['en']['texte']['titre']);
        $this->assertSame('Corrected step after preview.', $p->etapes()->first()->traductions['en']['contenu']);
        $this->assertSame('brouillon', $p->fresh()->statut->value);
        Http::assertSentCount(1);
    }

    public function test_nombre_incomplet_message_precis_conserve_apercu_existant(): void
    {
        $p = $this->projet();
        $this->fake($this->traduction());
        $r = $this->postJson(route('upcycling.ia.traduction', $p), ['langue' => 'en'])->assertOk();
        $originalPreview = session('upcycling.traductions.'.$p->id);
        $bad = $this->traduction();
        array_pop($bad['steps']);
        $this->fake($bad);
        $this->postJson(route('upcycling.ia.traduction', $p), ['langue' => 'en'])->assertStatus(503)->assertJsonPath('code', 'incomplete_translation')->assertSee('2')->assertSee('1')->assertJsonMissingPath('proposition');
        $this->assertSame($originalPreview, session('upcycling.traductions.'.$p->id));
        $this->assertNull($p->fresh()->traductions);
    }

    public function test_champs_project_absents_et_steps_non_tableau_refuses(): void
    {
        $p = $this->projet();
        $cases = [];
        $bad = $this->traduction();
        unset($bad['project']);
        $cases[] = $bad;
        $bad = $this->traduction();
        unset($bad['project']['titre']);
        $cases[] = $bad;
        $bad = $this->traduction();
        unset($bad['steps']);
        $cases[] = $bad;
        $bad = $this->traduction();
        $bad['steps'] = 'not an array';
        $cases[] = $bad;
        $bad = $this->traduction();
        unset($bad['steps'][0]['contenu']);
        $cases[] = $bad;
        $bad = $this->traduction();
        unset($bad['steps'][0]['numero']);
        $cases[] = $bad;
        foreach ($cases as $bad) {
            $this->fake($bad);
            $this->postJson(route('upcycling.ia.traduction', $p), ['langue' => 'en'])->assertStatus(503)->assertJsonMissingPath('proposition');
        }
        $this->assertNull($p->fresh()->traductions);
        $this->assertNull(session('upcycling.traductions.'.$p->id));
    }

    public function test_numeros_dupliques_ou_inconnus_refuses(): void
    {
        $p = $this->projet();
        foreach ([2, 99] as $badNumber) {
            $bad = $this->traduction();
            $bad['steps'][0]['numero'] = $badNumber;
            $this->fake($bad);
            $this->postJson(route('upcycling.ia.traduction', $p), ['langue' => 'en'])->assertStatus(503)->assertJsonPath('code', 'incomplete_translation');
        }
        $this->assertNull($p->fresh()->traductions);
    }

    public function test_aliases_et_numeros_en_chaines_normalises(): void
    {
        $p = $this->projet();
        $tr = $this->traduction();
        $tr['project']['title'] = $tr['project']['titre'];
        unset($tr['project']['titre']);
        $steps = array_map(fn ($s) => ['number' => (string) $s['numero'], 'title' => $s['titre'], 'content' => $s['contenu']], $tr['steps']);
        $this->fake(['projet' => $tr['project'], 'etapes' => $steps]);
        $this->postJson(route('upcycling.ia.traduction', $p), ['langue' => 'en'])->assertOk()->assertJsonPath('proposition.texte.etapes.0.numero', 2);
    }

    public function test_anciens_identifiants_numeriques_en_chaines_ne_declenchent_pas_faux_incomplet(): void
    {
        $p = $this->projet();
        $tr = $this->traduction();
        $steps = [];
        foreach ($p->etapes as $e) {
            $steps[] = ['id' => (string) $e->id, 'titre' => 'Translated step', 'contenu' => 'Complete translated content.'];
        }
        $this->fake([...$tr['project'], 'etapes' => $steps]);
        $this->postJson(route('upcycling.ia.traduction', $p), ['langue' => 'en'])->assertOk()->assertJsonCount(2, 'proposition.texte.etapes');
    }

    public function test_json_encadre_nettoye_uniquement_pour_traduction(): void
    {
        $p = $this->projet();
        $this->fake("```json\n".json_encode($this->traduction())."\n```");
        $this->postJson(route('upcycling.ia.traduction', $p), ['langue' => 'en'])->assertOk();
        $this->fake('Texte supplémentaire '.json_encode($this->traduction()));
        $this->postJson(route('upcycling.ia.traduction', $p), ['langue' => 'en'])->assertStatus(503)->assertJsonPath('code', 'invalid_response');
    }

    public function test_json_invalide_message_content_exclusif(): void
    {
        $p = $this->projet();
        foreach (['{not JSON', '[]', 'null'] as $bad) {
            $this->fake($bad);
            $this->postJson(route('upcycling.ia.traduction', $p), ['langue' => 'en'])->assertStatus(503)->assertJsonPath('code', 'invalid_response');
        }
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response(['done' => true, 'response' => json_encode($this->traduction()), 'message' => ['content' => 'bad JSON']])]);
        $this->postJson(route('upcycling.ia.traduction', $p), ['langue' => 'en'])->assertStatus(503)->assertJsonPath('code', 'invalid_response');
        $this->assertNull($p->fresh()->traductions);
    }

    public function test_ollama_indisponible_et_langue_invalide(): void
    {
        $p = $this->projet();
        Http::fake(['*' => Http::failedConnection()]);
        $this->postJson(route('upcycling.ia.traduction', $p), ['langue' => 'en'])->assertStatus(503)->assertJsonPath('code', 'network_error')->assertJsonStructure(['fallback_url']);
        Http::assertSentCount(1);
        $this->postJson(route('upcycling.ia.traduction', $p), ['langue' => 'de'])->assertUnprocessable();
        Http::assertSentCount(1);
        $this->assertNull($p->fresh()->traductions);
    }

    public function test_projet_sans_etapes_steps_vide_et_trois_langues(): void
    {
        $p = ProjetUpcycling::factory()->for(auth()->user())->create();
        $tr = $this->traduction();
        $tr['steps'] = [];
        foreach (['fr', 'ar', 'en'] as $langue) {
            $this->fake($tr);
            $this->postJson(route('upcycling.ia.traduction', $p), ['langue' => $langue])->assertOk()->assertJsonCount(0,'proposition.texte.etapes');
        }
    }
}
