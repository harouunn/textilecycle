<?php

namespace Tests\Feature\Services\Ateliers\IA;

use App\Models\Atelier;
use App\Services\Ateliers\IA\TextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TextServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_analyse_valide(): void
    {
        $atelier = Atelier::factory()->create(['actif' => true]);

        Http::fake([
            '*/api/generate' => Http::response([
                'response' => json_encode([
                    'resume' => 'Jean troué au genou',
                    'atelier_recommande_id' => $atelier->id,
                    'priorite' => 'haute',
                ]),
            ]),
        ]);

        $service = new TextService();
        $result = $service->analyser('Mon jean a un trou au genou');

        $this->assertIsArray($result);
        $this->assertEquals('Jean troué au genou', $result['resume']);
        $this->assertEquals($atelier->id, $result['atelier_recommande_id']);
        $this->assertEquals('haute', $result['priorite']);
    }

    public function test_analyse_json_invalide(): void
    {
        Http::fake([
            '*/api/generate' => Http::response([
                'response' => 'texte non json',
            ]),
        ]);

        $service = new TextService();
        $result = $service->analyser('Description test');

        $this->assertIsArray($result);
        $this->assertStringContainsString('indisponible', $result['resume']);
        $this->assertEquals('normale', $result['priorite']);
    }

    public function test_analyse_ollama_eteint(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
        });

        $service = new TextService();
        $result = $service->analyser('Description test');

        $this->assertNull($result);
    }
}
