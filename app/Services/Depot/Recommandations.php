<?php

namespace App\Services\Depot;

use App\Models\Vetement;
use Illuminate\Support\Collection;

/**
 * « Vous aimerez aussi » : vêtements du catalogue les plus proches de celui consulté, par score de similarité.
 * Même catégorie +3, même taille +2, même genre +2, même matière +1, même état +1.
 * On note les 60 vêtements disponibles les plus récents puis on garde les meilleurs.
 */
class Recommandations
{
    private const CANDIDATS = 60;

    /**
     * @return Collection<int, Vetement>
     */
    public function pour(Vetement $vetement, int $nombre = 4): Collection
    {
        return Vetement::query()
            ->with('categorie')
            ->auCatalogue()
            ->whereKeyNot($vetement->id)
            ->latest('id')
            ->limit(self::CANDIDATS)
            ->get()
            ->map(fn (Vetement $candidat) => [$candidat, $this->score($vetement, $candidat)])
            ->filter(fn (array $paire) => $paire[1] > 0)
            ->sortByDesc(fn (array $paire) => $paire[1])
            ->take($nombre)
            ->map(fn (array $paire) => $paire[0])
            ->values();
    }

    public function score(Vetement $reference, Vetement $candidat): int
    {
        return ($candidat->categorie_id === $reference->categorie_id ? 3 : 0)
            + ($candidat->taille === $reference->taille ? 2 : 0)
            + ($candidat->genre === $reference->genre ? 2 : 0)
            + (mb_strtolower($candidat->matiere) === mb_strtolower($reference->matiere) ? 1 : 0)
            + ($candidat->etat === $reference->etat ? 1 : 0);
    }
}
