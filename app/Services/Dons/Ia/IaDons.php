<?php

namespace App\Services\Dons\Ia;

use App\Contracts\AiClient;
use App\Exceptions\AiUnavailableException;
use App\Models\AiLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

/**
 * Point de passage unique des fonctionnalités IA du module :
 *  1. appelle le client d'IA (AiClient) ;
 *  2. valide la réponse avec des règles Validator (types, bornes, listes autorisées) ;
 *  3. enregistre l'appel dans ai_logs ;
 *  4. renvoie null si quoi que ce soit échoue : l'appelant utilise alors sa version sans IA.
 * Aucune exception ne remonte jusqu'à l'utilisateur.
 */
class IaDons
{
    public function __construct(private AiClient $client) {}

    /**
     * @param  array<string, mixed>  $schema  schéma demandé à l'IA
     * @param  array<string, mixed>  $regles  règles Validator appliquées à la réponse
     * @return array<string, mixed>|null réponse validée (seulement les clés couvertes par les règles), ou null
     */
    public function demander(string $fonctionnalite, string $prompt, array $schema, array $regles, ?string $image = null, ?string $systeme = null, ?int $userId = null): ?array
    {
        $debut = microtime(true);
        $erreur = null;
        $resultat = null;

        try {
            $reponse = $this->client->generateJson($prompt, $schema, $image, $systeme);
            $validation = Validator::make($reponse, $regles);

            if ($validation->fails()) {
                $erreur = 'Réponse invalide : '.Str::limit(implode(' ', $validation->errors()->all()), 200);
            } else {
                $resultat = $validation->validated();
            }
        } catch (AiUnavailableException $e) {
            $erreur = Str::limit($e->getMessage(), 250);
        } catch (Throwable $e) {
            // Filet de sécurité : une erreur inattendue ne doit jamais casser la page.
            Log::channel('ai')->error('ia.erreur_inattendue', ['fonctionnalite' => $fonctionnalite, 'erreur' => $e->getMessage()]);
            $erreur = 'Erreur inattendue : '.Str::limit($e->getMessage(), 200);
        }

        AiLog::query()->create([
            'user_id' => $userId ?? auth()->id(),
            'fonctionnalite' => $fonctionnalite,
            'duree_ms' => (int) round((microtime(true) - $debut) * 1000),
            'succes' => $resultat !== null,
            'erreur' => $erreur,
        ]);

        return $resultat;
    }
}
