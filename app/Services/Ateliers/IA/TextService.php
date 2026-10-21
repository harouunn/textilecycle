<?php

namespace App\Services\Ateliers\IA;

use App\Models\Atelier;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TextService implements AnalyseurInterface
{
    public function analyser(string $description): ?array
    {
        $url = config('ia.ollama.url');
        $model = config('ia.ollama.model');

        if (!$url || !$model) {
            Log::warning('Ollama non configuré');
            return null;
        }

        $ateliers = Atelier::where('actif', true)
            ->get(['id', 'ville', 'specialites'])
            ->map(fn($a) => "ID:{$a->id} {$a->ville} - {$a->specialites}")
            ->join(', ');

        $prompt = "Description: {$description}\nAteliers: {$ateliers}\nRéponds JSON: {\"resume\":\"court\",\"atelier_recommande_id\":nombre|null,\"priorite\":\"basse|normale|haute\"}";

        try {
            $response = Http::timeout(config('ia.ollama.timeout'))
                ->retry(1, 100)
                ->post("{$url}/api/generate", [
                    'model' => $model,
                    'prompt' => $prompt,
                    'stream' => false,
                    'options' => [
                        'temperature' => 0.1,
                        'num_predict' => 300,
                    ],
                ]);

            if (!$response->successful()) {
                Log::error('Ollama erreur', ['status' => $response->status()]);
                return null;
            }

            $result = $response->json();
            $text = $result['response'] ?? null;

            if (!$text) {
                return null;
            }

            $text = trim($text, " \n\r\t\v\0`");
            if (str_starts_with($text, '```json')) {
                $text = trim(substr($text, 7));
            }
            if (str_ends_with($text, '```')) {
                $text = trim(substr($text, 0, -3));
            }

            $data = json_decode($text, true);

            return $this->valider($data);
        } catch (\Exception $e) {
            Log::error('TextService exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    private function valider(?array $data): ?array
    {
        if (!$data || !is_array($data)) {
            return ['resume' => 'Analyse du texte indisponible', 'atelier_recommande_id' => null, 'priorite' => 'normale'];
        }

        $priorites = ['basse', 'normale', 'haute'];
        $atelierId = $data['atelier_recommande_id'] ?? null;

        if ($atelierId && !Atelier::where('id', $atelierId)->where('actif', true)->exists()) {
            $atelierId = null;
        }

        return [
            'resume' => is_string($data['resume'] ?? null) ? substr($data['resume'], 0, 200) : 'Résumé non disponible',
            'atelier_recommande_id' => $atelierId,
            'priorite' => in_array($data['priorite'] ?? '', $priorites) ? $data['priorite'] : 'normale',
        ];
    }
}
