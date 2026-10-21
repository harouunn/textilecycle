<?php

namespace App\Services\Dons;

use App\Enums\Dons\DonStatut;
use App\Enums\Dons\TypeArticle;
use App\Models\Association;
use App\Models\CampagneCollecte;
use App\Services\Dons\Ia\IaDons;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * IA 2 — associations recommandées pour un don.
 *  Étape 1, règles (0 à 60) : même ville +25, association active +10, capacité mensuelle restante +15,
 *          campagne en cours qui cible ce type d'article +10.
 *  Étape 2, IA (0 à 40) : les 8 meilleures sont envoyées à Gemini avec la description du don ;
 *          il renvoie un score sémantique et une justification courte en français.
 *  Score final = règles + sémantique. Sans IA : score des règles seul (« recommandation simplifiée »).
 * Seules les associations actives sont proposées : on ne peut pas donner à une association inactive.
 */
class MatchingService
{
    public const CANDIDATS_IA = 8;

    public function __construct(private IaDons $ia) {}

    /**
     * @return array{simplifiee: bool, recommandations: list<array<string, mixed>>}
     */
    public function recommander(TypeArticle $type, ?string $ville, ?string $description, ?float $poidsKg = null, int $limite = 3): array
    {
        $candidats = $this->scoresRegles($type, $ville, $poidsKg)
            ->sortByDesc('score_regles')
            ->take(self::CANDIDATS_IA)
            ->values();

        $semantique = $candidats->isEmpty() ? null : $this->scoresSemantiques($candidats, $type, $ville, $description);

        $recommandations = $candidats
            ->map(function (array $c) use ($semantique) {
                $ia = $semantique[$c['id']] ?? null;

                return [
                    ...$c,
                    'score_semantique' => $ia['score_semantique'] ?? null,
                    'justification' => $ia['justification'] ?? null,
                    'score' => $c['score_regles'] + ($ia['score_semantique'] ?? 0),
                ];
            })
            ->filter(fn (array $c) => $c['active'])
            ->sortByDesc('score')
            ->take($limite)
            ->values()
            ->map(fn (array $c) => [
                'id' => $c['id'],
                'nom' => $c['nom'],
                'ville' => $c['ville'],
                'score' => $c['score'],
                'score_regles' => $c['score_regles'],
                'score_semantique' => $c['score_semantique'],
                'justification' => $c['justification'] ?? implode(' · ', $c['raisons']) ?: null,
                'raisons' => $c['raisons'],
                'url' => route('dons.create', [$c['id'], 'type_article' => $type->value]),
            ])
            ->all();

        return ['simplifiee' => $semantique === null, 'recommandations' => $recommandations];
    }

    /**
     * @return Collection<int, array{id: int, nom: string, ville: string, besoins: string, active: bool, score_regles: int, raisons: list<string>}>
     */
    public function scoresRegles(TypeArticle $type, ?string $ville, ?float $poidsKg = null): Collection
    {
        $ville = $ville !== null ? Str::lower(Str::ascii(trim($ville))) : null;

        return Association::query()
            ->withSum(['dons as kg_ce_mois' => fn ($q) => $q
                ->whereNotIn('statut', [DonStatut::Refuse, DonStatut::Annule])
                ->where('created_at', '>=', now()->startOfMonth())], 'poids_kg')
            ->with(['campagnes' => fn ($q) => $q->enCours()])
            ->get()
            ->map(function (Association $a) use ($type, $ville, $poidsKg) {
                $score = 0;
                $raisons = [];

                if ($ville !== null && $ville !== '' && Str::lower(Str::ascii($a->ville)) === $ville) {
                    $score += 25;
                    $raisons[] = "à {$a->ville}";
                }
                if ($a->active) {
                    $score += 10;
                }
                $restant = $a->capacite_mensuelle_kg - (float) $a->kg_ce_mois;
                if ($a->capacite_mensuelle_kg > 0 && $restant > 0 && $restant >= (float) $poidsKg) {
                    $score += 15;
                    $raisons[] = 'peut encore accueillir des dons ce mois-ci';
                }
                if ($a->campagnes->contains(fn (CampagneCollecte $c) => $c->typesCibles() === [] || in_array($type, $c->typesCibles(), true))) {
                    $score += 10;
                    $raisons[] = 'campagne en cours pour ce type d\'article';
                }

                return [
                    'id' => $a->id,
                    'nom' => $a->nom,
                    'ville' => $a->ville,
                    'besoins' => $a->besoins,
                    'active' => $a->active,
                    'score_regles' => $score,
                    'raisons' => $raisons,
                ];
            });
    }

    /**
     * @return array<int, array{score_semantique: int, justification: string}>|null indexé par id, ou null si l'IA a échoué
     */
    private function scoresSemantiques(Collection $candidats, TypeArticle $type, ?string $ville, ?string $description): ?array
    {
        $ids = $candidats->pluck('id')->all();
        $liste = $candidats->map(fn (array $c) => ['id' => $c['id'], 'nom' => $c['nom'], 'ville' => $c['ville'], 'besoins' => Str::limit($c['besoins'], 400)])->all();

        $reponse = $this->ia->demander(
            'matching',
            "Un donateur souhaite donner : {$type->label()}".($ville ? ", il habite à {$ville}" : '').".\n"
            .'Description du don : '.(filled($description) ? Str::limit($description, 500) : 'non précisée')."\n\n"
            ."Associations candidates (JSON) :\n".json_encode($liste, JSON_UNESCAPED_UNICODE)."\n\n"
            .'Pour CHAQUE association, donne score_semantique (entier de 0 à 40) : à quel point ses besoins correspondent à ce don. '
            .'Ajoute une justification d\'une phrase en français, sans inventer d\'information absente des besoins.',
            [
                'type' => 'OBJECT',
                'properties' => [
                    'resultats' => [
                        'type' => 'ARRAY',
                        'items' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'id' => ['type' => 'INTEGER'],
                                'score_semantique' => ['type' => 'INTEGER'],
                                'justification' => ['type' => 'STRING'],
                            ],
                            'required' => ['id', 'score_semantique', 'justification'],
                        ],
                    ],
                ],
                'required' => ['resultats'],
            ],
            [
                'resultats' => ['required', 'array', 'min:1', 'max:'.self::CANDIDATS_IA],
                'resultats.*.id' => ['required', 'integer', 'distinct', 'in:'.implode(',', $ids)],
                'resultats.*.score_semantique' => ['required', 'integer', 'between:0,40'],
                'resultats.*.justification' => ['required', 'string', 'max:300'],
            ],
        );

        return $reponse === null ? null : collect($reponse['resultats'])->keyBy('id')->all();
    }
}
