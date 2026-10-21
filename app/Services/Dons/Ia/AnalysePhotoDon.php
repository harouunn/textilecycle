<?php

namespace App\Services\Dons\Ia;

use App\Enums\Dons\EtatGeneral;
use App\Enums\Dons\TypeArticle;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * IA 1 — analyse de la photo d'un don : est-ce un textile, de quel type, en quel état, combien, quel poids ?
 * Le résultat validé est gardé côté serveur sous un jeton : le formulaire renvoie le jeton (pas le résultat),
 * le navigateur ne peut donc pas falsifier ce qui est enregistré dans dons.ia_analyse.
 */
class AnalysePhotoDon
{
    private const DUREE_JETON = 7200;

    public function __construct(private IaDons $ia) {}

    /**
     * @return array{ok: bool, message: string, jeton?: string, analyse?: array<string, mixed>}
     */
    public function analyser(UploadedFile $photo, User $user): array
    {
        $analyse = $this->ia->demander(
            'analyse_photo',
            "Analyse cette photo d'un don de textiles destiné à une association caritative.\n"
            ."Indique s'il s'agit bien de textiles (vêtements, chaussures, linge de maison, accessoires textiles).\n"
            ."Si oui, choisis le type d'article et l'état général parmi les valeurs autorisées, estime le nombre d'articles visibles, "
            ."leur poids total en kilogrammes, et décris le lot en une phrase courte en français.\n"
            .'confiance est ta certitude globale entre 0 et 1.',
            $this->schema(),
            [
                'est_textile' => ['required', 'boolean'],
                'type_article' => ['exclude_if:est_textile,false', 'required', Rule::enum(TypeArticle::class)],
                'etat_general' => ['exclude_if:est_textile,false', 'required', Rule::enum(EtatGeneral::class)],
                'quantite_estimee' => ['exclude_if:est_textile,false', 'required', 'integer', 'min:1', 'max:500'],
                'poids_estime_kg' => ['exclude_if:est_textile,false', 'required', 'numeric', 'min:0.05', 'max:5000'],
                'description_courte' => ['nullable', 'string', 'max:300'],
                'confiance' => ['required', 'numeric', 'between:0,1'],
            ],
            $photo->getRealPath(),
            userId: $user->id,
        );

        if ($analyse === null) {
            return ['ok' => false, 'message' => 'L\'analyse automatique est indisponible pour le moment : remplissez le formulaire vous-même.'];
        }

        $analyse['analyse_le'] = now()->toIso8601String();
        $jeton = (string) Str::uuid();
        Cache::put($this->cle($user, $jeton), $analyse, self::DUREE_JETON);

        return [
            'ok' => true,
            'jeton' => $jeton,
            'analyse' => $analyse,
            'message' => $analyse['est_textile']
                ? 'Champs pré-remplis à partir de la photo : vérifiez-les avant d\'envoyer.'
                : 'Cette photo ne semble pas montrer de textiles. Choisissez une photo de vos vêtements.',
        ];
    }

    /**
     * Résultat d'une analyse faite par ce compte, ou null (jeton inconnu, expiré ou d'un autre compte).
     *
     * @return array<string, mixed>|null
     */
    public function recuperer(User $user, ?string $jeton): ?array
    {
        return $jeton ? Cache::get($this->cle($user, $jeton)) : null;
    }

    public function oublier(User $user, string $jeton): void
    {
        Cache::forget($this->cle($user, $jeton));
    }

    private function cle(User $user, string $jeton): string
    {
        return "dons:ia:photo:{$user->id}:{$jeton}";
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'est_textile' => ['type' => 'BOOLEAN'],
                'type_article' => ['type' => 'STRING', 'enum' => array_column(TypeArticle::cases(), 'value')],
                'etat_general' => ['type' => 'STRING', 'enum' => array_column(EtatGeneral::cases(), 'value')],
                'quantite_estimee' => ['type' => 'INTEGER'],
                'poids_estime_kg' => ['type' => 'NUMBER'],
                'description_courte' => ['type' => 'STRING'],
                'confiance' => ['type' => 'NUMBER'],
            ],
            'required' => ['est_textile', 'confiance'],
        ];
    }
}
