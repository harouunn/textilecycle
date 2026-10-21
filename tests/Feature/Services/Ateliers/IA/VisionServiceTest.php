<?php

namespace Tests\Feature\Services\Ateliers\IA;

use App\Services\Ateliers\IA\VisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VisionServiceTest extends TestCase
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

    public function test_reponse_valide(): void
    {
        config(['ia.gemini.api_key' => 'test_key']);
        $photoPath = $this->creerImageTest();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => '{"type_dommage":"trou","gravite":"elevee","temps_estime_min":45}',
                        ]],
                    ],
                ]],
            ]),
        ]);

        $service = new VisionService();
        $result = $service->analyser($photoPath);

        $this->assertIsArray($result);
        $this->assertEquals('trou', $result['type_dommage']);
        $this->assertEquals('elevee', $result['gravite']);
        $this->assertEquals(45, $result['temps_estime_min']);
    }

    public function test_json_invalide(): void
    {
        config(['ia.gemini.api_key' => 'test_key']);
        $photoPath = $this->creerImageTest();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => 'Texte non JSON invalide',
                        ]],
                    ],
                ]],
            ]),
        ]);

        $service = new VisionService();
        $result = $service->analyser($photoPath);

        $this->assertIsArray($result);
        $this->assertEquals('autre', $result['type_dommage']);
        $this->assertEquals('moderee', $result['gravite']);
        $this->assertEquals(30, $result['temps_estime_min']);
    }

    public function test_timeout(): void
    {
        config(['ia.gemini.api_key' => 'test_key']);
        $photoPath = $this->creerImageTest();

        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
        });

        $service = new VisionService();
        $result = $service->analyser($photoPath);

        $this->assertNull($result);
    }

    public function test_sans_api_key_retourne_null(): void
    {
        config(['ia.gemini.api_key' => null]);

        $service = new VisionService();
        $result = $service->analyser('test.jpg');

        $this->assertNull($result);
    }
}
