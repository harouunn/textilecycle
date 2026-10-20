<?php

namespace App\Services\Upcycling;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use JsonException;

class ClientOllama implements FournisseurIA
{
    public function generer(string $consigne, array $entree, array $schema): array
    {
        $base = config('upcycling.ollama_base_url');
        $model = config('upcycling.ollama_model');
        $timeout = config('upcycling.timeout');
        // Adresse et modèle strictement locaux : aucun renvoi ni fournisseur cloud.
        if ($base !== 'http://127.0.0.1:11434' || $model !== 'qwen3:8b' || ! is_numeric($timeout) || $timeout < 1 || $timeout > 300) {
            throw new AssistantIndisponible('La configuration Ollama locale est invalide. Vérifiez OLLAMA_BASE_URL et OLLAMA_MODEL.', 'invalid_configuration');
        }
        try {
            $response = Http::acceptJson()->connectTimeout(3)->timeout((float) $timeout)
                ->withOptions(['allow_redirects' => false, 'proxy' => ''])
                ->post($base.'/api/chat', [
                    'model' => $model, 'stream' => false, 'think' => false,
                    'messages' => [
                        ['role' => 'system', 'content' => $consigne],
                        ['role' => 'user', 'content' => json_encode($entree, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)],
                    ],
                    'format' => $schema,
                    'options' => ['temperature' => 0.2, 'num_predict' => 4096],
                ]);
            if (! $response->successful()) {
                [$reason, $message] = match ($response->status()) {
                    404 => ['model_unavailable', 'Le modèle qwen3:8b est absent. Installez-le dans Ollama.'],
                    429 => ['rate_limited', 'Ollama est occupé. Réessayez plus tard ou continuez manuellement.'],
                    default => ['service_unavailable', 'Ollama est temporairement indisponible. Continuez manuellement.'],
                };
                $retryAfter = $response->header('Retry-After');
                throw new AssistantIndisponible($message, $reason, ctype_digit($retryAfter ?? '') ? min((int) $retryAfter, 86400) : null);
            }
            if (! is_array($response->json()) || ! is_string($response->json('message.content'))) {
                throw new JsonException('Structure invalide');
            }
            if ($response->json('done') !== true || $response->json('done_reason') === 'length') {
                throw new AssistantIndisponible('La réponse Ollama est incomplète. Continuez manuellement ou reformulez.', 'incomplete_response');
            }
            $content = $response->json('message.content');
            // Tolérance réservée à la traduction ; générations projet/étapes inchangées.
            if (isset($schema['properties']['project'], $schema['properties']['steps'])) {
                $content = trim($content);
                if (preg_match('/\A```(?:json)?\s*([\s\S]*?)\s*```\z/i', $content, $matches)) {
                    $content = trim($matches[1]);
                }
            }
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($data) || array_is_list($data)) {
                throw new JsonException('Objet JSON attendu');
            }

            return $data;
        } catch (ConnectionException $e) {
            if (str_contains($e->getMessage(), 'cURL error 28')) {
                throw new AssistantIndisponible('Le délai de réponse Ollama est dépassé. Le formulaire manuel reste disponible.', 'timeout');
            }
            throw new AssistantIndisponible('Ollama ne répond pas. Lancez Ollama sur cet ordinateur. Le formulaire manuel reste disponible.', 'network_error');
        } catch (JsonException $e) {
            throw new AssistantIndisponible('La réponse JSON d’Ollama est invalide. Le formulaire manuel reste disponible.', 'invalid_response');
        }
    }
}
