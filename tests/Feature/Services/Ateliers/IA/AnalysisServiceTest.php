<?php

namespace Tests\Feature\Services\Ateliers\IA;

use App\Jobs\AnalyserDemandeJob;
use App\Models\DemandeReparation;
use App\Models\User;
use App\Services\Ateliers\IA\AnalysisService;
use App\Services\Ateliers\IA\TextService;
use App\Services\Ateliers\IA\VisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnalysisServiceTest extends TestCase
{
    use RefreshDatabase;

    private function creerImageTest(): string
    {
        Storage::fake('public');
        
        // Créer un fake JPEG (petit fichier binaire pour tester)
        // En production le VisionService détectera que c'est trop petit (<100 octets)
        // et utilisera directement le contenu
        $fakeJpeg = str_repeat("\xFF\xD8\xFF\xE0", 20); // Simule un JPEG
        
        Storage::disk('public')->put('test.jpg', $fakeJpeg);
        
        return 'test.jpg';
    }

    public function test_tout_ok_statut_ok(): void
    {
        config(['ia.gemini.api_key' => 'test_key']);
        config(['ia.ollama.url' => 'http://test.local']);
        config(['ia.ollama.model' => 'llama3']);

        $photoPath = $this->creerImageTest();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => '{"type_dommage":"trou","gravite":"elevee","temps_estime_min":45}']]],
                ]],
            ]),
            'test.local/*' => Http::response([
                'response' => '{"resume":"Jean troué","atelier_recommande_id":null,"priorite":"haute"}',
            ]),
        ]);

        $demande = DemandeReparation::factory()->create([
            'photo' => $photoPath,
            'description' => 'Mon jean a un trou',
        ]);

        $service = new AnalysisService(new VisionService(), new TextService());
        $service->analyser($demande);

        $demande->refresh();
        $this->assertEquals('ok', $demande->analyse_statut);
        $this->assertNotNull($demande->analyse_vision);
        $this->assertNotNull($demande->analyse_texte);
        $this->assertNotNull($demande->analysed_at);
        $this->assertEquals('trou', $demande->analyse_vision['type_dommage']);
        $this->assertEquals('haute', $demande->analyse_texte['priorite']);
    }

    public function test_un_modele_echoue_statut_erreur_avec_resultat_partiel(): void
    {
        config(['ia.gemini.api_key' => 'test_key']);
        config(['ia.ollama.url' => 'http://test.local']);
        config(['ia.ollama.model' => 'llama3']);

        $photoPath = $this->creerImageTest();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => '{"type_dommage":"bouton","gravite":"faible","temps_estime_min":15}']]],
                ]],
            ]),
            'test.local/*' => Http::response([], 500),
        ]);

        $demande = DemandeReparation::factory()->create([
            'photo' => $photoPath,
            'description' => 'Bouton manquant',
        ]);

        $service = new AnalysisService(new VisionService(), new TextService());
        $service->analyser($demande);

        $demande->refresh();
        $this->assertEquals('erreur', $demande->analyse_statut);
        $this->assertNotNull($demande->analyse_vision);
        $this->assertNull($demande->analyse_texte);
        $this->assertEquals('bouton', $demande->analyse_vision['type_dommage']);
    }

    public function test_pas_de_reanalyse_si_deja_ok(): void
    {
        $demande = DemandeReparation::factory()->create([
            'analyse_statut' => 'ok',
            'analyse_vision' => ['type_dommage' => 'trou', 'gravite' => 'elevee', 'temps_estime_min' => 45],
            'analyse_texte' => ['resume' => 'Test', 'atelier_recommande_id' => null, 'priorite' => 'normale'],
        ]);

        Http::fake();

        $service = new AnalysisService(new VisionService(), new TextService());
        $service->analyser($demande);

        Http::assertNothingSent();
    }

    public function test_job_dispatche_a_la_creation(): void
    {
        Queue::fake();

        $demande = DemandeReparation::factory()->create();

        Queue::assertPushed(AnalyserDemandeJob::class, function ($job) use ($demande) {
            return $job->demande->id === $demande->id && !$job->forceReanalyse;
        });
    }

    public function test_bouton_relancer_dispatch_job_avec_force(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $demande = DemandeReparation::factory()->create(['analyse_statut' => 'ok']);

        $this->actingAs($admin)
            ->post(route('admin.ateliers.demandes.reanalyse', $demande))
            ->assertRedirect();

        Queue::assertPushed(AnalyserDemandeJob::class, function ($job) use ($demande) {
            return $job->demande->id === $demande->id && $job->forceReanalyse;
        });
    }

    public function test_bouton_relancer_refuse_non_admin(): void
    {
        $user = User::factory()->create(['role' => 'client']);
        $demande = DemandeReparation::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.ateliers.demandes.reanalyse', $demande))
            ->assertForbidden();
    }
}
