<?php

namespace App\Services\Dons\Ia;

use App\Models\Association;
use App\Models\CampagneCollecte;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * IA 3 — assistant des donateurs, qui répond UNIQUEMENT à partir des données du site (associations actives,
 * leurs besoins, leur ville, les campagnes en cours) : le contexte est extrait de la base selon les mots-clés
 * et la ville de la question (15 lignes au plus). Les 6 derniers messages sont gardés en session.
 * Sans IA : la réponse liste les associations qui correspondent à la question.
 */
class AssistantDonateur
{
    public const CLE_SESSION = 'dons.assistant.historique';

    public const MESSAGES_GARDES = 6;

    public const LIGNES_CONTEXTE = 15;

    private const MOTS_VIDES = ['alors', 'aussi', 'autre', 'avec', 'avez', 'avoir', 'bonjour', 'cette', 'comment', 'dans', 'donc', 'donne', 'donner',
        'elle', 'est-ce', 'faire', 'merci', 'mais', 'mes', 'mon', 'nous', 'pour', 'pourquoi', 'puis', 'quel', 'quelle', 'quelles', 'quels', 'quoi',
        'sont', 'suis', 'tout', 'tous', 'très', 'vers', 'votre', 'vous', 'association', 'associations', 'peux', 'peut', 'veux', 'voudrais', 'chez'];

    private const CONSIGNE = <<<'TXT'
        Tu es l'assistant du module « Associations & dons » de TexTileCycle, une plateforme tunisienne de dons de textiles à des associations.
        Règles strictes :
        - Réponds toujours en français, en 2 à 5 phrases, sur un ton chaleureux.
        - Réponds UNIQUEMENT à partir du CONTEXTE fourni (associations, besoins, villes, campagnes). N'invente aucune association, adresse, date ni besoin.
        - Si le contexte ne permet pas de répondre, dis simplement que tu ne sais pas et propose de consulter la liste des associations.
        - Cite le nom exact des associations dont tu parles et mets leurs identifiants dans association_ids.
        - Si la question n'a rien à voir avec les dons de vêtements ou de textiles aux associations, mets hors_sujet à true
          et réponds poliment que tu ne peux aider que pour les dons.
        TXT;

    public function __construct(private IaDons $ia) {}

    /**
     * @return array{reponse: string, associations: list<array{id: int, nom: string, ville: string, url: string}>, simplifiee: bool, hors_sujet: bool}
     */
    public function repondre(string $question, Session $session): array
    {
        $question = trim($question);
        $historique = $session->get(self::CLE_SESSION, []);
        [$associations, $campagnes] = $this->contexte($question);
        $ids = $associations->pluck('id')->all();

        $reponse = $this->ia->demander(
            'assistant',
            "CONTEXTE (JSON) :\n".json_encode($this->contexteJson($associations, $campagnes), JSON_UNESCAPED_UNICODE)."\n\n"
            ."HISTORIQUE RÉCENT :\n".($historique ? collect($historique)->map(fn ($m) => ($m['role'] === 'user' ? 'Donateur' : 'Assistant').' : '.$m['texte'])->join("\n") : '(aucun)')."\n\n"
            ."QUESTION DU DONATEUR :\n".$question,
            [
                'type' => 'OBJECT',
                'properties' => [
                    'reponse' => ['type' => 'STRING'],
                    'association_ids' => ['type' => 'ARRAY', 'items' => ['type' => 'INTEGER']],
                    'hors_sujet' => ['type' => 'BOOLEAN'],
                ],
                'required' => ['reponse', 'association_ids', 'hors_sujet'],
            ],
            [
                'reponse' => ['required', 'string', 'min:2', 'max:2000'],
                'association_ids' => ['present', 'array', 'max:'.self::LIGNES_CONTEXTE],
                // Une association citée doit venir du contexte : l'IA ne peut pas faire apparaître un lien inventé.
                'association_ids.*' => ['integer', 'in:'.(implode(',', $ids) ?: '0')],
                'hors_sujet' => ['required', 'boolean'],
            ],
            systeme: self::CONSIGNE,
        );

        $resultat = $reponse !== null
            ? [
                'reponse' => $reponse['reponse'],
                'associations' => $reponse['hors_sujet'] ? [] : $this->liens($associations->whereIn('id', $reponse['association_ids'])),
                'simplifiee' => false,
                'hors_sujet' => (bool) $reponse['hors_sujet'],
            ]
            : $this->sansIa($associations);

        $historique[] = ['role' => 'user', 'texte' => Str::limit($question, 500)];
        $historique[] = ['role' => 'assistant', 'texte' => Str::limit($resultat['reponse'], 1000)];
        $session->put(self::CLE_SESSION, array_slice($historique, -self::MESSAGES_GARDES));

        return $resultat;
    }

    /**
     * Associations et campagnes en lien avec la question (ville citée, mots-clés dans le nom, les besoins ou la description).
     * Sans correspondance, les associations actives les plus récentes servent de contexte général.
     *
     * @return array{0: Collection<int, Association>, 1: Collection<int, CampagneCollecte>}
     */
    public function contexte(string $question): array
    {
        $texte = Str::lower(Str::ascii($question));
        $ville = Association::query()->active()->distinct()->pluck('ville')
            ->first(fn (string $v) => Str::contains($texte, Str::lower(Str::ascii($v))));
        $motsCles = collect(preg_split('/[^a-z0-9]+/', $texte))
            ->filter(fn (string $mot) => mb_strlen($mot) >= 4 && ! in_array($mot, self::MOTS_VIDES, true))
            ->unique()
            ->take(8)
            ->values();

        $requete = fn () => Association::query()->active()
            ->when($ville, fn ($q) => $q->where('ville', $ville))
            ->with(['campagnes' => fn ($q) => $q->enCours()]);

        $associations = $requete()
            ->when($motsCles->isNotEmpty(), fn ($q) => $q->where(function ($q) use ($motsCles) {
                foreach ($motsCles as $mot) {
                    // Racine du mot (« manteaux » → « mante ») pour attraper le singulier et le pluriel.
                    $racine = '%'.mb_substr($mot, 0, max(4, mb_strlen($mot) - 2)).'%';
                    $q->orWhere('nom', 'like', $racine)->orWhere('besoins', 'like', $racine)->orWhere('description', 'like', $racine)
                        ->orWhereHas('campagnes', fn ($c) => $c->enCours()->where(fn ($c) => $c->where('titre', 'like', $racine)->orWhere('description', 'like', $racine)));
                }
            }))
            ->limit(10)
            ->get();

        if ($associations->isEmpty()) {
            $associations = $requete()->latest()->limit(10)->get();
        }

        $campagnes = $associations->flatMap->campagnes->take(self::LIGNES_CONTEXTE - $associations->count())->values();

        return [$associations, $campagnes];
    }

    /**
     * @return array<string, mixed>
     */
    private function contexteJson(Collection $associations, Collection $campagnes): array
    {
        return [
            'associations' => $associations->map(fn (Association $a) => [
                'id' => $a->id,
                'nom' => $a->nom,
                'ville' => $a->ville,
                'besoins' => Str::limit($a->besoins, 400),
                'description' => Str::limit($a->description, 300),
            ])->values()->all(),
            'campagnes_en_cours' => $campagnes->map(fn (CampagneCollecte $c) => [
                'association_id' => $c->association_id,
                'titre' => $c->titre,
                'objectif_kg' => $c->objectif_kg,
                'jusqu_au' => $c->date_fin->format('d/m/Y'),
                'articles_cibles' => collect($c->typesCibles())->map->label()->all(),
            ])->values()->all(),
        ];
    }

    /**
     * @return array{reponse: string, associations: list<array<string, mixed>>, simplifiee: bool, hors_sujet: bool}
     */
    private function sansIa(Collection $associations): array
    {
        return [
            'reponse' => $associations->isEmpty()
                ? 'L\'assistant est momentanément indisponible et aucune association ne correspond à votre question. Consultez la liste des associations partenaires.'
                : 'L\'assistant est momentanément indisponible. Voici des associations partenaires qui pourraient correspondre à votre question :',
            'associations' => $this->liens($associations->take(5)),
            'simplifiee' => true,
            'hors_sujet' => false,
        ];
    }

    /**
     * @return list<array{id: int, nom: string, ville: string, url: string}>
     */
    private function liens(Collection $associations): array
    {
        return $associations->map(fn (Association $a) => [
            'id' => $a->id,
            'nom' => $a->nom,
            'ville' => $a->ville,
            'url' => route('dons.associations.show', $a),
        ])->values()->all();
    }
}
