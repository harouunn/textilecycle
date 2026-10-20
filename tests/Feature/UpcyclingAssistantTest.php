<?php

namespace Tests\Feature;

use App\Models\EtapeProjet;
use App\Models\ProjetUpcycling;
use App\Models\User;
use App\Services\Upcycling\AssistantIA;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Tests\Concerns\UpcyclingDatabase;
use Tests\TestCase;

class UpcyclingAssistantTest extends TestCase
{
    use UpcyclingDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['upcycling.provider' => 'ollama', 'upcycling.ollama_base_url' => 'http://127.0.0.1:11434', 'upcycling.ollama_model' => 'qwen3:8b', 'upcycling.timeout' => 180, 'upcycling.openai_key' => '']);
        Sleep::fake();
        Http::preventStrayRequests();
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
    }

    private function resetHttp(): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
    }

    private function fake(array $data): void
    {
        $this->resetHttp();
        Http::fake(['127.0.0.1:11434/api/chat' => Http::response(['done' => true, 'done_reason' => 'stop', 'message' => ['content' => json_encode($data)]])]);
    }

    private function fields(): array
    {
        return ['titre' => 'Un sac cabas en jean', 'description' => 'Découper puis assembler un jean pour obtenir un sac solide.', 'vetement_origine' => 'Vieux jean', 'resultat' => 'Sac cabas', 'materiel_necessaire' => 'Ciseaux, fil et aiguille', 'difficulte' => 'facile', 'duree_minutes' => 60];
    }

    private function step(): array
    {
        return ['numero' => 1, 'titre' => 'Découper le jean', 'contenu' => 'Découper les jambes en suivant les coutures.', 'outils_necessaires' => 'Ciseaux', 'duree_minutes' => 10, 'conseils_securite' => 'Garder les doigts loin des lames.'];
    }

    private function projet(): ProjetUpcycling
    {
        return ProjetUpcycling::factory()->for(auth()->user())->brouillon()->create();
    }

    public function test_generation_champs_sans_sauvegarde_ni_photo(): void
    {
        $this->fake($this->fields());
        $this->postJson(route('upcycling.ia.projet'), ['idee' => 'Transformer mon vieux jean en sac facile.'])->assertOk()->assertJsonPath('proposition.difficulte', 'facile');
        $this->assertSame(0, ProjetUpcycling::count());
        Http::assertSent(fn ($r) => $r['model'] === 'qwen3:8b' && $r['stream'] === false && $r['format']['type'] === 'object' && ! str_contains($r['messages'][1]['content'], 'photo') && ! $r->hasHeader('Authorization'));
    }

    public function test_generation_etapes_reste_un_apercu(): void
    {
        $p = $this->projet();
        $this->fake(['etapes' => [$this->step()]]);
        $this->postJson(route('upcycling.ia.etapes', $p))->assertOk()->assertJsonPath('proposition.0.numero', 1);
        $this->assertSame(0, $p->etapes()->count());
    }

    public function test_etapes_finales_exigent_photos_et_conservent_relation(): void
    {
        $p = $this->projet();
        $data = ['etapes' => [$this->step()]];
        $this->postJson(route('upcycling.ia.etapes.enregistrer', $p), $data)->assertUnprocessable()->assertJsonValidationErrors('etapes.0.photo');
        $data['etapes'][0]['photo'] = UploadedFile::fake()->image('etape.png', 2, 2);
        $this->post(route('upcycling.ia.etapes.enregistrer', $p), $data)->assertSessionHasNoErrors()->assertRedirect();
        $e = $p->etapes()->firstOrFail();
        $this->assertSame('Ciseaux', $e->outils_necessaires);
        Storage::disk('public')->assertExists($e->photo);
    }

    public function test_traduction_apercu_puis_confirmation_sans_ecraser_originaux(): void
    {
        $p = $this->projet();
        $e = EtapeProjet::factory()->for($p, 'projet')->create();
        $titre = $p->titre;
        $tr = array_intersect_key($this->fields(), array_flip(AssistantIA::CHAMPS));
        $tr['titre'] = 'A denim tote bag';
        $tr['etapes'] = [['id' => $e->id, 'titre' => 'Cut the denim', 'contenu' => 'Cut along the seams carefully.', 'outils_necessaires' => 'Scissors', 'conseils_securite' => 'Keep fingers away.']];
        $this->fake($tr);
        $r = $this->postJson(route('upcycling.ia.traduction', $p), ['langue' => 'en'])->assertOk();
        $this->assertSame($titre, $p->fresh()->titre);
        $this->assertNull($p->fresh()->traductions);
        $this->put(route('upcycling.ia.traduction.confirmer', [$p, 'en']), ['jeton' => $r->json('proposition.jeton')])->assertSessionHasNoErrors();
        $this->assertSame($titre, $p->fresh()->titre);
        $this->assertSame('A denim tote bag', $p->fresh()->traductions['en']['texte']['titre']);
        $this->assertSame('Cut the denim', $e->fresh()->traductions['en']['titre']);
    }

    public function test_traduction_modifiee_depuis_apercu_est_refusee(): void
    {
        $p = $this->projet();
        $tr = array_intersect_key($this->fields(), array_flip(AssistantIA::CHAMPS));
        $tr['etapes'] = [];
        $this->fake($tr);
        $r = $this->postJson(route('upcycling.ia.traduction', $p), ['langue' => 'fr'])->assertOk();
        $p->update(['titre' => 'Projet changé depuis aperçu']);
        $this->put(route('upcycling.ia.traduction.confirmer', [$p, 'fr']), ['jeton' => $r->json('proposition.jeton')])->assertSessionHasErrors('traduction');
        $this->assertNull($p->fresh()->traductions);
    }

    public function test_generation_fonctionne_sans_cle_api(): void
    {
        foreach ([null, '', 'invalid-key'] as $key) {
            config(['upcycling.openai_key' => $key]);
            $this->fake($this->fields());
            $this->postJson(route('upcycling.ia.projet'), ['idee' => 'Un sac à partir de mon jean'])->assertOk();
            Http::assertSentCount(1);
            Http::assertSent(fn ($r) => ! $r->hasHeader('Authorization') && $r->url() === 'http://127.0.0.1:11434/api/chat');
        }
    }

    public function test_erreur_http_et_reseau_gerees(): void
    {
        $this->resetHttp();
        Http::fake(['*' => Http::response([], 500)]);
        $this->postJson(route('upcycling.ia.projet'), ['idee' => 'Un sac à partir de mon jean'])->assertStatus(503);
        $this->resetHttp();
        Http::fake(['*' => Http::failedConnection()]);
        $this->postJson(route('upcycling.ia.projet'), ['idee' => 'Un sac à partir de mon jean'])->assertStatus(503);
    }

    public function test_sortie_invalide_et_valeurs_hors_limites_refusees(): void
    {
        $data = $this->fields();
        $data['duree_minutes'] = 1441;
        $this->fake($data);
        $this->postJson(route('upcycling.ia.projet'), ['idee' => 'Un sac à partir de mon jean'])->assertStatus(503);
        $this->resetHttp();
        Http::fake(['*' => Http::response(['status' => 'completed', 'output' => [['content' => [['type' => 'output_text', 'text' => 'not JSON']]]]])]);
        $this->postJson(route('upcycling.ia.projet'), ['idee' => 'Un sac à partir de mon jean'])->assertStatus(503);
    }

    public function test_permissions_ia_projet_d_autrui_et_langues_invalides(): void
    {
        $p = ProjetUpcycling::factory()->create();
        $this->postJson(route('upcycling.ia.etapes', $p))->assertForbidden();
        $this->postJson(route('upcycling.ia.traduction', $p), ['langue' => 'en'])->assertForbidden();
        $this->putJson(route('upcycling.ia.traduction.confirmer', [$p, 'en']), ['jeton' => 'faux'])->assertForbidden();
        $this->postJson(route('upcycling.ia.traduction', $this->projet()), ['langue' => 'de'])->assertUnprocessable();
        Http::assertNothingSent();
    }

    public function test_langues_fr_ar_en_et_traduction_incomplete(): void
    {
        $p = $this->projet();
        foreach (['fr', 'ar', 'en'] as $langue) {
            $tr = array_intersect_key($this->fields(), array_flip(AssistantIA::CHAMPS));
            $tr['etapes'] = [];
            $this->fake($tr);
            $this->postJson(route('upcycling.ia.traduction', $p), ['langue' => $langue])->assertOk();
        }
        EtapeProjet::factory()->for($p, 'projet')->create();
        $this->fake($tr);
        $this->postJson(route('upcycling.ia.traduction', $p), ['langue' => 'en'])->assertStatus(503);
    }

    public function test_refus_reponse_incomplete_et_structure_invalide(): void
    {
        foreach ([['status' => 'incomplete', 'output' => []], ['status' => 'completed', 'output' => null], ['status' => 'completed', 'output' => [['content' => [['type' => 'refusal', 'refusal' => 'refus']]]]]] as $reponse) {
            $this->resetHttp();
            Http::fake(['*' => Http::response($reponse)]);
            $this->postJson(route('upcycling.ia.projet'), ['idee' => 'Transformer un jean en sac facile'])->assertStatus(503);
        }
    }

    public function test_confirmation_expiree_ou_sans_apercu_refusee(): void
    {
        $p = $this->projet();
        $this->putJson(route('upcycling.ia.traduction.confirmer', [$p, 'en']), ['jeton' => 'test'])->assertUnprocessable();
        $this->withSession(['upcycling.traductions.'.$p->id => ['jeton' => 'test', 'user' => auth()->id(), 'langue' => 'en', 'expire' => time() - 1]])
            ->putJson(route('upcycling.ia.traduction.confirmer', [$p, 'en']), ['jeton' => 'test'])->assertUnprocessable();
        $this->assertNull($p->fresh()->traductions);
    }

    public function test_traduction_publique_arabe_preserve_original_et_cache_variantes_obsoletes(): void
    {
        $p = $this->projet();
        $p->update(['statut' => 'publie']);
        $original = $p->titre;
        $texte = array_intersect_key($this->fields(), array_flip(AssistantIA::CHAMPS));
        $texte['titre'] = 'حقيبة من الجينز';
        $texte['etapes'] = [];
        $p->forceFill(['traductions' => ['ar' => ['empreinte' => AssistantIA::empreinte($p), 'texte' => $texte]]])->save();
        $this->get(route('upcycling.projets.show', [$p, 'langue' => 'ar']))->assertOk()->assertSeeText('حقيبة من الجينز')->assertSee('dir="rtl"', false);
        $this->assertSame($original, $p->fresh()->titre);
        $p->update(['titre' => 'Texte original révisé']);
        $this->get(route('upcycling.projets.show', [$p, 'langue' => 'ar']))->assertOk()->assertSeeText('Texte original révisé')->assertDontSeeText('حقيبة من الجينز');
    }

    public function test_crud_manuel_fonctionne_sans_cle_api(): void
    {
        config(['upcycling.openai_key' => '']);
        $data = $this->fields();
        $data['photo_avant'] = UploadedFile::fake()->image('avant.png', 2, 2);
        $data['photo_apres'] = UploadedFile::fake()->image('apres.png', 2, 2);
        $this->post(route('upcycling.projets.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('brouillon', ProjetUpcycling::firstOrFail()->statut->value);
        Http::assertNothingSent();
    }

    public function test_etapes_injectees_ou_photos_invalides_ne_creent_rien(): void
    {
        $p = $this->projet();
        $data = $this->step();
        $data['photo'] = UploadedFile::fake()->create('faux.jpg', 1, 'text/plain');
        $this->postJson(route('upcycling.ia.etapes.enregistrer', $p), ['etapes' => [$data]])->assertUnprocessable()->assertJsonValidationErrors('etapes.0.photo');
        $data['photo'] = UploadedFile::fake()->image('photo.png', 2, 2);
        $data['projet_upcycling_id'] = 999;
        $this->postJson(route('upcycling.ia.etapes.enregistrer', $p), ['etapes' => [$data]])->assertUnprocessable();
        $this->assertSame(0, EtapeProjet::count());
    }

    public function test_actions_ia_exigent_authentification_et_verification_email(): void
    {
        $this->app['auth']->forgetGuards();
        $this->postJson(route('upcycling.ia.projet'), ['idee' => 'Transformer un jean en sac'])->assertUnauthorized();
        $this->actingAs(User::factory()->unverified()->create())->postJson(route('upcycling.ia.projet'), ['idee' => 'Transformer un jean en sac'])->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_appels_ia_limites_en_frequence(): void
    {
        $this->fake($this->fields());
        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('upcycling.ia.projet'), ['idee' => 'Transformer un jean en sac'])->assertOk();
        }
        $this->postJson(route('upcycling.ia.projet'), ['idee' => 'Transformer un jean en sac'])->assertStatus(429);
    }

    public function test_diagnostics_http_sans_exposer_le_message_fournisseur(): void
    {
        foreach ([[401, null, 'service_unavailable'], [403, null, 'service_unavailable'], [404, null, 'model_unavailable'], [429, null, 'rate_limited'], [400, null, 'service_unavailable'], [503, null, 'service_unavailable']] as [$status,$code,$expected]) {
            $this->resetHttp();
            Http::fake(['*' => Http::response(['error' => ['code' => $code, 'message' => 'MESSAGE_FOURNISSEUR_NE_PAS_AFFICHER']], $status)]);
            $r = $this->postJson(route('upcycling.ia.projet'), ['idee' => 'Transformer un jean en sac facile']);
            $r->assertStatus(503)->assertJsonPath('code', $expected)->assertDontSee('MESSAGE_FOURNISSEUR_NE_PAS_AFFICHER');
        }
    }

    public function test_timeout_et_configuration_invalide_sont_distingues(): void
    {
        $this->resetHttp();
        Http::fake(fn () => throw new ConnectionException('cURL error 28: timeout'));
        $this->postJson(route('upcycling.ia.projet'), ['idee' => 'Transformer un jean en sac facile'])->assertStatus(503)->assertJsonPath('code', 'timeout');
        $this->resetHttp();
        Http::fake();
        config(['upcycling.ollama_model' => '']);
        $this->postJson(route('upcycling.ia.projet'), ['idee' => 'Transformer un jean en sac facile'])->assertStatus(503)->assertJsonPath('code', 'invalid_configuration');
        Http::assertNothingSent();
    }

    public function test_url_modele_et_format_sont_ceux_attendus(): void
    {
        $this->fake($this->fields());
        $this->postJson(route('upcycling.ia.projet'), ['idee' => 'Transformer un jean en sac facile'])->assertOk();
        Http::assertSent(fn ($r) => $r->url() === 'http://127.0.0.1:11434/api/chat' && $r['model'] === 'qwen3:8b' && $r['think'] === false && $r['format']['type'] === 'object' && $r['format']['additionalProperties'] === false);
    }

    public function test_reponse_malformee_est_geree_sans_exception_non_capturee(): void
    {
        $responses = [
            'pas JSON',
            ['status' => 'completed', 'output' => [['content' => ['texte brut']]]],
            ['status' => 'completed', 'output' => [['content' => [['type' => 'output_text', 'text' => ['mauvais type']]]]]],
        ];
        foreach ($responses as $data) {
            $this->resetHttp();
            Http::fake(['*' => Http::response($data)]);
            $this->postJson(route('upcycling.ia.projet'), ['idee' => 'Transformer un jean en sac facile'])->assertStatus(503)->assertJsonPath('code', 'invalid_response');
        }
    }

    public function test_ollama_occupe_ne_relance_jamais_et_preserve_le_fallback(): void
    {
        $this->resetHttp();
        Http::fake(['*' => Http::response(['error' => 'private detail'], 429, ['Retry-After' => '20'])]);
        $r = $this->postJson(route('upcycling.ia.projet'), ['idee' => 'Un sac facile en vieux jean'])
            ->assertStatus(503)->assertHeader('Retry-After', '20')->assertJsonPath('code', 'rate_limited')->assertJsonStructure(['fallback_url']);
        Http::assertSentCount(1);
        Sleep::assertNeverSlept();
        ProjetUpcycling::factory()->create(['titre' => 'Sac cabas de secours', 'vetement_origine' => 'vieux jean', 'resultat' => 'sac facile']);
        $this->get($r->json('fallback_url'))->assertOk()->assertSeeText('Sac cabas de secours');
        Http::assertSentCount(1);
    }

    public function test_ollama_arrete_message_clair_fallback_et_aucune_ecriture(): void
    {
        $this->resetHttp();
        Http::fake(['*' => Http::failedConnection()]);
        $this->postJson(route('upcycling.ia.projet'), ['idee' => 'Un sac facile en vieux jean'])
            ->assertStatus(503)->assertJsonPath('code', 'network_error')->assertSee('Ollama')->assertJsonStructure(['fallback_url'])->assertJsonMissingPath('proposition');
        Http::assertSentCount(1);
        $this->assertSame(0, ProjetUpcycling::count());
    }

    public function test_reponse_incomplete_ollama_n_est_jamais_enregistree(): void
    {
        $this->resetHttp();
        foreach ([['done' => false], ['done' => true, 'done_reason' => 'length']] as $status) {
            $this->resetHttp();
            Http::fake(['*' => Http::response([...$status, 'message' => ['content' => json_encode($this->fields())]])]);
            $this->postJson(route('upcycling.ia.projet'), ['idee' => 'Un sac facile en vieux jean'])->assertStatus(503)->assertJsonPath('code', 'incomplete_response')->assertJsonMissingPath('proposition');
            Http::assertSentCount(1);
        }
        $this->assertSame(0, ProjetUpcycling::count());
        $this->assertSame(0, EtapeProjet::count());
    }

    public function test_json_interne_invalide_ou_liste_est_refuse(): void
    {
        foreach (['pas JSON', '[]', 'null', '{}'] as $texte) {
            $this->resetHttp();
            Http::fake(['*' => Http::response(['done' => true, 'message' => ['content' => $texte]])]);
            $this->postJson(route('upcycling.ia.projet'), ['idee' => 'Un sac facile en vieux jean'])->assertStatus(503)->assertJsonPath('code', 'invalid_response');
        }
    }

    public function test_url_externe_fournisseur_cloud_ou_modele_cloud_refuses_avant_envoi(): void
    {
        foreach ([['upcycling.ollama_base_url' => 'https://example.com'], ['upcycling.provider' => 'openai'], ['upcycling.ollama_model' => 'qwen3:8b-cloud']] as $bad) {
            config(['upcycling.provider' => 'ollama', 'upcycling.ollama_base_url' => 'http://127.0.0.1:11434', 'upcycling.ollama_model' => 'qwen3:8b']);
            config($bad);
            $this->resetHttp();
            Http::fake();
            $this->postJson(route('upcycling.ia.projet'), ['idee' => 'Un sac facile en vieux jean'])->assertStatus(503)->assertJsonPath('code', 'invalid_configuration');
            Http::assertNothingSent();
        }
    }

    public function test_traduction_modifiable_validee_sans_ecraser_original(): void
    {
        $p = $this->projet();
        $titre = $p->titre;
        $tr = array_intersect_key($this->fields(), array_flip(AssistantIA::CHAMPS));
        $tr['etapes'] = [];
        $this->fake($tr);
        $r = $this->postJson(route('upcycling.ia.traduction', $p), ['langue' => 'en'])->assertOk();
        $tr['titre'] = 'My corrected denim bag';
        $this->put(route('upcycling.ia.traduction.confirmer', [$p, 'en']), ['jeton' => $r->json('proposition.jeton'), 'texte' => $tr])->assertSessionHasNoErrors();
        $this->assertSame($titre, $p->fresh()->titre);
        $this->assertSame('My corrected denim bag', $p->fresh()->traductions['en']['texte']['titre']);
        $this->assertSame('brouillon', $p->fresh()->statut->value);
    }

    public function test_modification_traduction_invalide_ou_identifiant_injecte_refusee(): void
    {
        $p = $this->projet();
        $tr = array_intersect_key($this->fields(), array_flip(AssistantIA::CHAMPS));
        $tr['etapes'] = [];
        $this->fake($tr);
        $r = $this->postJson(route('upcycling.ia.traduction', $p), ['langue' => 'en'])->assertOk();
        $bad = $tr;
        $bad['titre'] = '';
        $this->putJson(route('upcycling.ia.traduction.confirmer', [$p, 'en']), ['jeton' => $r->json('proposition.jeton'), 'texte' => $bad])->assertUnprocessable();
        $bad = $tr;
        $bad['etapes'] = [['id' => 999, 'titre' => 'Step', 'contenu' => 'Private content', 'outils_necessaires' => '', 'conseils_securite' => '']];
        $this->putJson(route('upcycling.ia.traduction.confirmer', [$p, 'en']), ['jeton' => $r->json('proposition.jeton'), 'texte' => $bad])->assertUnprocessable();
        $this->assertNull($p->fresh()->traductions);
    }

    public function test_etapes_non_consecutives_et_photos_hors_contexte_refusees(): void
    {
        $p = $this->projet();
        $step = $this->step();
        $step['numero'] = 2;
        $this->fake(['etapes' => [$step]]);
        $this->postJson(route('upcycling.ia.etapes', $p))->assertStatus(503)->assertJsonPath('code', 'invalid_response');
        Http::assertSent(fn ($r) => ! str_contains($r['messages'][1]['content'], 'photo'));
        $step = $this->step();
        $step['photo'] = 'not-allowed.png';
        $this->fake(['etapes' => [$step]]);
        $this->postJson(route('upcycling.ia.etapes', $p))->assertStatus(503)->assertJsonPath('code', 'invalid_response');
        $this->assertSame(0, $p->etapes()->count());
    }

    public function test_redirection_ollama_non_suivie(): void
    {
        $this->resetHttp();
        Http::fake(['127.0.0.1:11434/api/chat' => Http::response('', 302, ['Location' => 'https://example.com'])]);
        $this->postJson(route('upcycling.ia.projet'), ['idee' => 'Un sac facile en vieux jean'])->assertStatus(503)->assertJsonPath('code', 'service_unavailable');
        Http::assertSentCount(1);
    }

    public function test_confirmation_traduction_formulaire_sans_etapes(): void
    {
        $p = $this->projet();
        $tr = array_intersect_key($this->fields(), array_flip(AssistantIA::CHAMPS));
        $this->fake([...$tr, 'etapes' => []]);
        $r = $this->postJson(route('upcycling.ia.traduction', $p), ['langue' => 'fr'])->assertOk();
        $this->put(route('upcycling.ia.traduction.confirmer', [$p, 'fr']), ['jeton' => $r->json('proposition.jeton'), 'texte' => $tr, 'sans_etapes' => '1'])->assertSessionHasNoErrors();
        $this->assertSame([], $p->fresh()->traductions['fr']['texte']['etapes']);
    }
}
