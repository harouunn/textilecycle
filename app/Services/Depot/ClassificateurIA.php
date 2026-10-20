<?php

namespace App\Services\Depot;

use App\Enums\Depot\Etat;
use App\Enums\Depot\Genre;
use App\Enums\Depot\Taille;
use App\Models\Categorie;
use App\Models\Vetement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Classement d'un vêtement par une vraie IA de vision qui tourne en local avec Ollama (modèle gemma3:4b
 * par défaut, voir config/depot.php). Le modèle regarde la photo et renvoie catégorie, type, matière,
 * état, genre, taille, couleur, défauts visibles et une description, au format JSON imposé par un schéma :
 * Ollama contraint la génération, les valeurs sont donc forcément choisies dans les listes du projet.
 *
 * Si l'IA est désactivée, si Ollama est arrêté, trop lent ou renvoie une réponse invalide, classer()
 * renvoie null et l'analyse locale sans IA (ClassificateurVetement) prend le relais : le dépôt marche toujours.
 */
class ClassificateurIA
{
    private const CONSIGNES = <<<'TXT'
        Tu es l'assistant de tri d'une plateforme solidaire de dépôt de vêtements d'occasion (TexTileCycle).
        Regarde attentivement la photo et le titre saisi par le déposant, puis classe le vêtement :
        - choisis la catégorie la plus adaptée dans la liste fournie (ou « Autre » si aucune ne convient) ;
        - estime l'état réel d'après la photo (taches, trous, bouloches, couleurs passées) ;
        - liste uniquement les défauts réels (tache, trou, accroc, bouloches, couleur passée, usure) ;
          les motifs, coutures et détails de style ne sont pas des défauts ; liste vide s'il n'y en a pas ;
        - indique « inconnue » pour la taille si elle n'est ni visible ni dans le titre ;
        - rédige une description de 2 à 3 phrases, en français, factuelle et engageante, sans inventer de détail invisible ;
        - donne ta confiance globale de 0 à 100.
        Réponds uniquement avec l'objet JSON demandé.
        TXT;

    public function disponible(): bool
    {
        return (bool) config('depot.ia.active') && filled(config('depot.ia.modele'));
    }

    /**
     * Vérifie qu'Ollama répond et que le modèle est installé (utilisé par la page de statut et la commande depot:ia).
     *
     * @return array{en_ligne: bool, modele_installe: bool, modele: string}
     */
    public function etat(): array
    {
        $modele = (string) config('depot.ia.modele');

        try {
            $modeles = Http::connectTimeout(1)->timeout(3)->withOptions(['proxy' => ''])
                ->get(config('depot.ia.url').'/api/tags')->json('models.*.name') ?? [];
        } catch (Throwable) {
            return ['en_ligne' => false, 'modele_installe' => false, 'modele' => $modele];
        }

        $installe = collect($modeles)->contains(fn ($nom) => $nom === $modele || $nom === $modele.':latest');

        return ['en_ligne' => true, 'modele_installe' => $installe, 'modele' => $modele];
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
        } catch (Throwable $e) {
            Log::warning('IA Ollama indisponible, analyse locale utilisée.', ['erreur' => $e->getMessage()]);

            return null;
        }

        return $reponse ? $this->convertir($reponse, $categories) : null;
    }

    /**
     * Appel à Ollama (POST /api/chat) avec la photo en base64. Renvoie le JSON décodé.
     *
     * @param  list<string>  $nomsCategories
     * @return array<string, mixed>|null
     */
    protected function appeler(string $cheminPhoto, ?string $titre, array $nomsCategories): ?array
    {
        $reponse = Http::acceptJson()
            ->connectTimeout(2)
            ->timeout(config('depot.ia.delai'))
            ->withOptions(['proxy' => '', 'allow_redirects' => false])
            ->post(config('depot.ia.url').'/api/chat', [
                'model' => config('depot.ia.modele'),
                'stream' => false,
                'messages' => [
                    ['role' => 'system', 'content' => self::CONSIGNES],
                    [
                        'role' => 'user',
                        'content' => 'Titre saisi par le déposant : '.($titre ?: '(aucun)')
                            ."\nCatégories du catalogue : ".implode(', ', $nomsCategories),
                        'images' => [base64_encode((string) file_get_contents($cheminPhoto))],
                    ],
                ],
                'format' => $this->schema($nomsCategories),
                // Température basse : on veut un classement stable, pas de créativité.
                'options' => ['temperature' => 0.1, 'num_predict' => 800],
                'keep_alive' => '30m',
            ]);

        if (! $reponse->successful()) {
            throw new RuntimeException('Ollama a répondu '.$reponse->status().' : '.$reponse->json('error', ''));
        }

        $json = json_decode((string) $reponse->json('message.content'), true);

        return is_array($json) && ! array_is_list($json) ? $json : null;
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
                'type_vetement' => ['type' => 'string'],
                'matiere' => ['type' => 'string'],
                'etat' => ['type' => 'string', 'enum' => array_column(Etat::cases(), 'value')],
                'genre' => ['type' => 'string', 'enum' => array_column(Genre::cases(), 'value')],
                'taille' => ['type' => 'string', 'enum' => [...array_column(Taille::cases(), 'value'), 'inconnue']],
                'couleur' => ['type' => 'string'],
                'uni' => ['type' => 'boolean'],
                'defauts' => ['type' => 'array', 'items' => ['type' => 'string']],
                'description' => ['type' => 'string'],
                'confiance' => ['type' => 'integer'],
            ],
            'required' => ['categorie', 'type_vetement', 'matiere', 'etat', 'genre', 'taille', 'couleur', 'uni', 'defauts', 'description', 'confiance'],
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
        $modele = config('depot.ia.modele');

        $indices = [
            "Analyse par l'IA locale Ollama ({$modele}, confiance {$confiance} %) : ".($ia['type_vetement'] ?? 'vêtement').', '.($ia['couleur'] ?? '').'.',
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

    /**
     * Image envoyée à l'IA : la miniature (600 px) suffit et l'analyse est bien plus rapide qu'avec l'original.
     */
    public static function imageAEnvoyer(string $cheminPublic): string
    {
        $miniature = Vetement::cheminMiniature($cheminPublic);

        return Storage::disk('public')->path(Storage::disk('public')->exists($miniature) ? $miniature : $cheminPublic);
    }
}
