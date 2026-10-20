<?php

namespace App\Services\Upcycling;

use App\Enums\Upcycling\Difficulte;
use App\Models\ProjetUpcycling;
use Illuminate\Support\Collection;

class RecommandationProjets
{
    private const POIDS = ['titre' => 3, 'vetement_origine' => 3, 'resultat' => 3, 'description' => 1, 'materiel_necessaire' => 1, 'difficulte' => 2];

    private const STOP = ['a', 'ai', 'au', 'aux', 'avec', 'ce', 'ces', 'dans', 'de', 'des', 'du', 'en', 'et', 'est', 'faire', 'il', 'j', 'je', 'la', 'le', 'les', 'ma', 'mes', 'mon', 'ne', 'ou', 'par', 'pour', 'que', 'qui', 'se', 'son', 'sur', 'un', 'une', 'veux', 'vieux', 'vieille', 'the', 'an', 'and', 'for', 'from', 'i', 'my', 'to', 'want', 'with', 'old', 'و', 'في', 'من', 'الى', 'على', 'اريد'];

    private function mots(string $texte): array
    {
        $texte = strtr(mb_strtolower($texte), ['é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'à' => 'a', 'â' => 'a', 'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'ö' => 'o', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c', 'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا']);
        preg_match_all('/[\p{L}\p{N}]+/u', $texte, $matches);
        $synonymes = ['jeans' => 'jean', 'denim' => 'jean', 'bags' => 'sac', 'bag' => 'sac', 'sacs' => 'sac', 'faciles' => 'facile', 'easy' => 'facile', 'simple' => 'facile', 'debutant' => 'facile', 'moyenne' => 'moyen', 'medium' => 'moyen', 'hard' => 'difficile', 'difficult' => 'difficile', 'shirts' => 'chemise', 'shirt' => 'chemise', 'chemises' => 'chemise'];
        $result = [];
        foreach ($matches[0] as $mot) {
            if (mb_strlen($mot) < 2 || in_array($mot, self::STOP, true)) {
                continue;
            }
            $result[] = $synonymes[$mot] ?? $mot;
        }

        return $result;
    }

    private function frequences(string $texte, int $poids = 1): array
    {
        $v = [];
        foreach ($this->mots($texte) as $mot) {
            $v[$mot] = ($v[$mot] ?? 0) + $poids;
        }

return $v;
    }

    public function chercher(string $idee, ?Difficulte $difficulte = null): Collection
    {
        $projets = ProjetUpcycling::query()->publie()->when($difficulte, fn ($q) => $q->where('difficulte', $difficulte))->with('user')->withCount('etapes')->orderBy('id')->get();
        $query = $this->frequences($idee);
        if (! $query || $projets->isEmpty()) {
            return collect();
        }
        $documents = [];
        $df = [];
        foreach ($projets as $projet) {
            $v = [];
            foreach (self::POIDS as $champ => $poids) {
                $value = $projet->$champ;
                $value = $value instanceof \BackedEnum ? $value->value : (string) $value;
                foreach ($this->frequences($value, $poids) as $mot => $count) {
                    $v[$mot] = ($v[$mot] ?? 0) + $count;
                }
            }
            $documents[$projet->id] = $v;
            foreach (array_keys($v) as $mot) {
                $df[$mot] = ($df[$mot] ?? 0) + 1;
            }
        }
        $idf = [];
        foreach ($df as $mot => $count) {
            $idf[$mot] = log((1 + $projets->count()) / (1 + $count)) + 1;
        }
        $vecteur = function (array $counts) use ($idf): array {
            $v = [];
            foreach ($counts as $mot => $count) {
                if (isset($idf[$mot])) {
                    $v[$mot] = (1 + log($count)) * $idf[$mot];
                }
            }

return $v;
        };
        $norme = fn (array $v) => sqrt(array_sum(array_map(fn ($n) => $n * $n, $v)));
        $q = $vecteur($query);
        $qn = $norme($q);
        if ($qn == 0) {
            return collect();
        }

        return $projets->map(function ($projet) use ($documents, $q, $qn, $vecteur, $norme) {
            $v = $vecteur($documents[$projet->id]);
            $produit = 0;
            foreach ($q as $mot => $poids) {
                $produit += $poids * ($v[$mot] ?? 0);
            }
            $dn = $norme($v);
            $projet->setAttribute('similarite', $dn > 0 ? $produit / ($qn * $dn) : 0);

            return $projet;
        })->filter(fn ($projet) => $projet->similarite > 0)->sortByDesc('similarite')->values();
    }
}
