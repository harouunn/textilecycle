<?php

namespace App\Services\Depot;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIException;
use App\Enums\Depot\Etat;
use App\Enums\Depot\Genre;
use App\Enums\Depot\Taille;
use App\Models\Categorie;
use App\Models\Vetement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Classement d'un vêtement par l'IA Claude (vision) : Claude regarde vraiment la photo et renvoie
 * catégorie, matière, état, genre, taille, défauts visibles et une description, au format JSON imposé
 * (structured outputs) : les valeurs sont forcément choisies dans les listes du projet.
 *
 * Utilisé seulement si une clé ANTHROPIC_API_KEY est configurée. Sans clé, en cas d'erreur réseau
 * ou si le modèle refuse, classer() renvoie null et l'analyse locale (ClassificateurVetement) prend le relais.
 */
class ClassificateurIA
{
    private const CONSIGNES = <<<'TXT'
        Tu es l'assistant de tri d'une plateforme solidaire de dépôt de vêtements d'occasion (TexTileCycle).
        À partir de la photo et du titre saisi par le déposant, classe le vêtement :
        - choisis la catégorie la plus adaptée dans la liste fournie (ou « Autre » si aucune ne convient) ;
        - estime l'état réel d'après la photo (taches, trous, bouloches, couleurs passées) ;
        - liste les défauts visibles, en quelques mots chacun (liste vide s'il n'y en a pas) ;
        - indique « inconnue » pour la taille si elle n'est ni visible ni dans le titre ;
        - rédige une description de 2 à 3 phrases, en français, factuelle et engageante, sans inventer de détail invisible ;
        - donne ta confiance globale de 0 à 100.
        TXT;

    public function disponible(): bool
    {
        return filled(config('services.anthropic.key'));
    }

    /**
     * @param  Collection<int, Categorie>  $categories
     * @return array{categorie_id: ?int, matiere: ?string, etat: Etat, genre: Genre, taille: ?Taille, couleur: string, uni: bool, description: string, indices: list<string>}|null
     */
    public function classer(string $cheminPhoto, ?string $titre, Collection $categories): ?array
    {
        if (! $this->disponible()) {
            return null;
        }

        try {
            $reponse = $this->appeler($cheminPhoto, $titre, $categories->pluck('nom')->all());
        } catch (APIException|Throwable $e) {
            Log::warning('Classement IA indisponible, analyse locale utilisée.', ['erreur' => $e->getMessage()]);

            return null;
        }

        return $reponse ? $this->convertir($reponse, $categories) : null;
    }

    /**
     * Appel à l'API Claude. Renvoie le JSON décodé, ou null si le modèle a refusé de répondre.
     *
     * @param  list<string>  $nomsCategories
     * @return array<string, mixed>|null
     */
    protected function appeler(string $cheminPhoto, ?string $titre, array $nomsCategories): ?array
    {
        $client = new Client(apiKey: config('services.anthropic.key'));

        $message = $client->beta->messages->create(
            model: config('services.anthropic.model'),
            maxTokens: 4000,
            betas: ['server-side-fallback-2026-07-01'],
            fallbacks: 'default',
            system: self::CONSIGNES,
            messages: [[
                'role' => 'user',
                'content' => [
                    ['type' => 'image', 'source' => [
                        'type' => 'base64',
                        'mediaType' => $this->typeMime($cheminPhoto),
                        'data' => base64_encode((string) file_get_contents($cheminPhoto)),
                    ]],
                    ['type' => 'text', 'text' => 'Titre saisi par le déposant : '.($titre ?: '(aucun)')
                        ."\nCatégories du catalogue : ".implode(', ', $nomsCategories)],
                ],
            ]],
            outputConfig: [
                // Tâche de classement simple : un effort bas suffit et limite le coût.
                'effort' => 'low',
                'format' => ['type' => 'json_schema', 'schema' => $this->schema($nomsCategories)],
            ],
        );

        if ($message->stopReason === 'refusal') {
            return null;
        }

        foreach ($message->content as $bloc) {
            if ($bloc->type === 'text') {
                return json_decode($bloc->text, true);
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $nomsCategories
     * @return array<string, mixed>
     */
    private function schema(array $nomsCategories): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'categorie' => ['type' => 'string', 'enum' => [...$nomsCategories, 'Autre']],
                'type_vetement' => ['type' => 'string', 'description' => 'ex. chemise, jean, robe, pull'],
                'matiere' => ['type' => 'string', 'description' => 'matière principale probable, ex. Coton, Denim'],
                'etat' => ['type' => 'string', 'enum' => array_column(Etat::cases(), 'value')],
                'genre' => ['type' => 'string', 'enum' => array_column(Genre::cases(), 'value')],
                'taille' => ['type' => 'string', 'enum' => [...array_column(Taille::cases(), 'value'), 'inconnue']],
                'couleur' => ['type' => 'string', 'description' => 'couleur dominante en français'],
                'uni' => ['type' => 'boolean'],
                'defauts' => ['type' => 'array', 'items' => ['type' => 'string']],
                'description' => ['type' => 'string'],
                'confiance' => ['type' => 'integer'],
            ],
            'required' => ['categorie', 'type_vetement', 'matiere', 'etat', 'genre', 'taille', 'couleur', 'uni', 'defauts', 'description', 'confiance'],
            'additionalProperties' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $ia
     * @param  Collection<int, Categorie>  $categories
     * @return array<string, mixed>
     */
    private function convertir(array $ia, Collection $categories): array
    {
        $categorie = $categories->firstWhere('nom', $ia['categorie'] ?? null);
        $defauts = array_values(array_filter((array) ($ia['defauts'] ?? [])));
        $confiance = max(0, min(100, (int) ($ia['confiance'] ?? 0)));

        $indices = [
            "Analyse par l'IA Claude (confiance {$confiance} %) : ".($ia['type_vetement'] ?? 'vêtement').', '.($ia['couleur'] ?? '').'.',
            $categorie ? "Catégorie proposée : {$categorie->nom}." : 'Aucune catégorie ne correspond : choisissez-la.',
            $defauts ? 'Défauts repérés sur la photo : '.implode(', ', $defauts).'.' : 'Aucun défaut visible repéré sur la photo.',
        ];

        return [
            'categorie_id' => $categorie?->id,
            'matiere' => filled($ia['matiere'] ?? null) ? ucfirst((string) $ia['matiere']) : null,
            'etat' => Etat::tryFrom((string) ($ia['etat'] ?? '')) ?? Etat::Bon,
            'genre' => Genre::tryFrom((string) ($ia['genre'] ?? '')) ?? Genre::Unisexe,
            'taille' => Taille::tryFrom((string) ($ia['taille'] ?? '')),
            'couleur' => (string) ($ia['couleur'] ?? ''),
            'uni' => (bool) ($ia['uni'] ?? true),
            'description' => trim((string) ($ia['description'] ?? '')),
            'indices' => $indices,
        ];
    }

    private function typeMime(string $chemin): string
    {
        return match (strtolower(pathinfo($chemin, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }

    /**
     * Image envoyée à l'IA : la miniature (600 px) suffit et coûte moins de tokens que l'original.
     */
    public static function imageAEnvoyer(string $cheminPublic): string
    {
        $miniature = Vetement::cheminMiniature($cheminPublic);

        return Storage::disk('public')->path(Storage::disk('public')->exists($miniature) ? $miniature : $cheminPublic);
    }
}
