<?php

namespace App\Contracts;

use App\Exceptions\AiUnavailableException;

/**
 * Client d'IA générative qui répond en JSON structuré.
 * Implémentation par défaut : App\Services\Ai\GeminiClient (liée dans AiServiceProvider, remplaçable en test).
 */
interface AiClient
{
    /**
     * Envoie la consigne (et éventuellement une image) et renvoie la réponse JSON décodée.
     * La réponse n'est PAS validée ici : l'appelant la valide avant de s'en servir.
     *
     * @param  array<string, mixed>  $schema  schéma de la réponse (format responseSchema de Gemini / OpenAPI)
     * @param  string|null  $imagePath  chemin d'une image locale à joindre
     * @param  string|null  $systemInstruction  consigne système (rôle, règles, langue)
     * @return array<mixed>
     *
     * @throws AiUnavailableException service non configuré, injoignable, saturé ou réponse illisible
     */
    public function generateJson(string $prompt, array $schema, ?string $imagePath = null, ?string $systemInstruction = null): array;
}
