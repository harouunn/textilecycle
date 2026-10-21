<?php

namespace App\Services\Ai;

use App\Contracts\AiClient;
use App\Exceptions\AiUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;
use Throwable;

/**
 * Google Gemini via l'API REST generateContent, en sortie JSON contrainte (responseMimeType + responseSchema).
 *  - délai : services.gemini.timeout (20 s) ;
 *  - nouvelles tentatives : 2, espacées de 500 ms, seulement sur 429 et 5xx ;
 *  - réponses identiques mises en cache 24 h (empreinte de la consigne, du schéma et de l'image) ;
 *  - journal dédié « ai » (storage/logs/ai.log) : jamais la clé, la consigne ni l'image.
 */
class GeminiClient implements AiClient
{
    private const CACHE_TTL = 86400;

    private const NOUVELLES_TENTATIVES = 2;

    public function generateJson(string $prompt, array $schema, ?string $imagePath = null, ?string $systemInstruction = null): array
    {
        $key = (string) config('services.gemini.key');
        if ($key === '') {
            throw AiUnavailableException::nonConfigure();
        }

        $model = (string) config('services.gemini.model');
        $image = $imagePath !== null ? $this->image($imagePath) : null;
        $empreinte = 'ai:gemini:'.hash('sha256', json_encode([$model, $prompt, $schema, $systemInstruction, $image ? hash('sha256', $image['data']) : null]));

        if (is_array($cache = Cache::get($empreinte))) {
            Log::channel('ai')->info('gemini.cache', ['model' => $model]);

            return $cache;
        }

        // PHP coupe une page après 30 s par défaut : une analyse de photo peut en prendre davantage (tentatives comprises).
        set_time_limit((1 + self::NOUVELLES_TENTATIVES) * (int) config('services.gemini.timeout', 20) + 30);

        $debut = microtime(true);
        try {
            $reponse = Http::baseUrl((string) config('services.gemini.base_url'))
                ->withHeaders(['x-goog-api-key' => $key])
                ->acceptJson()
                ->timeout((int) config('services.gemini.timeout', 20))
                ->retry(1 + self::NOUVELLES_TENTATIVES, 500, fn (Throwable $e) => $this->doitReessayer($e), throw: false)
                ->post("models/{$model}:generateContent", $this->corps($prompt, $schema, $image, $systemInstruction));
        } catch (ConnectionException $e) {
            $this->journaliser('gemini.injoignable', $model, $debut, null, $e->getMessage());
            throw new AiUnavailableException('Gemini injoignable ou délai dépassé.', null, $e);
        }

        if ($reponse->failed()) {
            $this->journaliser('gemini.erreur', $model, $debut, $reponse->status(), $reponse->json('error.message'));
            throw new AiUnavailableException("Gemini a répondu HTTP {$reponse->status()}.", $reponse->status());
        }

        $donnees = $this->extraireJson($reponse);
        $this->journaliser('gemini.succes', $model, $debut, $reponse->status());
        Cache::put($empreinte, $donnees, self::CACHE_TTL);

        return $donnees;
    }

    /**
     * Seuls les quotas (429) et les erreurs serveur (5xx) méritent une nouvelle tentative.
     */
    private function doitReessayer(Throwable $e): bool
    {
        return $e instanceof RequestException && ($e->response->status() === 429 || $e->response->serverError());
    }

    /**
     * @param  array{mime: string, data: string}|null  $image
     * @return array<string, mixed>
     */
    private function corps(string $prompt, array $schema, ?array $image, ?string $systemInstruction): array
    {
        $parts = [['text' => $prompt]];
        if ($image) {
            $parts[] = ['inline_data' => ['mime_type' => $image['mime'], 'data' => base64_encode($image['data'])]];
        }

        return array_filter([
            'contents' => [['role' => 'user', 'parts' => $parts]],
            'systemInstruction' => $systemInstruction ? ['parts' => [['text' => $systemInstruction]]] : null,
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => $schema,
                'temperature' => 0.2,
            ],
        ]);
    }

    /**
     * @return array{mime: string, data: string}
     */
    private function image(string $chemin): array
    {
        $data = is_readable($chemin) ? file_get_contents($chemin) : false;
        if ($data === false || $data === '') {
            throw new AiUnavailableException('Image illisible.');
        }

        return ['mime' => mime_content_type($chemin) ?: 'image/jpeg', 'data' => $data];
    }

    /**
     * @return array<mixed>
     */
    private function extraireJson(Response $reponse): array
    {
        $texte = $reponse->json('candidates.0.content.parts.0.text');
        if (! is_string($texte) || $texte === '') {
            $raison = $reponse->json('promptFeedback.blockReason') ?? $reponse->json('candidates.0.finishReason') ?? 'réponse vide';
            Log::channel('ai')->warning('gemini.vide', ['raison' => $raison]);
            throw new AiUnavailableException("Gemini n'a pas renvoyé de contenu ({$raison}).");
        }

        try {
            $donnees = json_decode($texte, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            Log::channel('ai')->warning('gemini.json_invalide', ['erreur' => $e->getMessage()]);
            throw new AiUnavailableException('Réponse de Gemini qui n\'est pas du JSON valide.', null, $e);
        }

        if (! is_array($donnees)) {
            throw new AiUnavailableException('Réponse de Gemini qui n\'est pas un objet JSON.');
        }

        return $donnees;
    }

    private function journaliser(string $evenement, string $model, float $debut, ?int $status, ?string $erreur = null): void
    {
        Log::channel('ai')->log($erreur ? 'warning' : 'info', $evenement, array_filter([
            'model' => $model,
            'duree_ms' => (int) round((microtime(true) - $debut) * 1000),
            'status' => $status,
            'erreur' => $erreur,
        ], fn ($v) => $v !== null));
    }
}
