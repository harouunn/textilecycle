<?php

namespace App\Services\Depot;

use RuntimeException;

/**
 * Analyse les pixels d'une photo avec GD (sans IA) : couleur dominante et vêtement uni ou à motifs.
 *
 * La photo est réduite à 40 × 40 pixels, chaque pixel est rattaché à la couleur nommée la plus proche,
 * puis on compte. Un fond blanc (photo de produit) est ignoré quand une autre couleur occupe assez de place.
 */
class AnalyseImage
{
    private const TAILLE_ECHANTILLON = 40;

    /** Part minimale d'une couleur pour considérer que le blanc dominant n'est qu'un fond. */
    private const PART_MIN_SOUS_FOND = 0.15;

    /** Part minimale de la couleur dominante pour dire que le vêtement est uni. */
    private const PART_MIN_UNI = 0.7;

    /** Couleurs reconnues et leur valeur RVB de référence. */
    private const PALETTE = [
        'noir' => [20, 20, 20],
        'blanc' => [245, 245, 245],
        'gris' => [128, 128, 128],
        'beige' => [215, 195, 160],
        'marron' => [110, 70, 40],
        'rouge' => [200, 30, 40],
        'rose' => [235, 140, 170],
        'orange' => [240, 140, 30],
        'jaune' => [240, 210, 40],
        'vert' => [50, 140, 60],
        'kaki' => [110, 110, 60],
        'bleu marine' => [25, 35, 80],
        'bleu' => [50, 100, 200],
        'violet' => [120, 60, 150],
    ];

    /**
     * @return array{couleur: string, part: int, uni: bool}
     */
    public function analyser(string $chemin): array
    {
        $image = @imagecreatefromstring((string) @file_get_contents($chemin));
        if ($image === false) {
            throw new RuntimeException("Image illisible : {$chemin}");
        }

        $echantillon = imagescale($image, self::TAILLE_ECHANTILLON, self::TAILLE_ECHANTILLON);
        $comptes = array_fill_keys(array_keys(self::PALETTE), 0);

        for ($x = 0; $x < self::TAILLE_ECHANTILLON; $x++) {
            for ($y = 0; $y < self::TAILLE_ECHANTILLON; $y++) {
                $rgb = imagecolorsforindex($echantillon, imagecolorat($echantillon, $x, $y));
                $comptes[$this->couleurLaPlusProche($rgb['red'], $rgb['green'], $rgb['blue'])]++;
            }
        }

        arsort($comptes);
        $total = array_sum($comptes);

        // Fond blanc : on ne garde que le vêtement si une autre couleur est assez présente.
        $secondeCouleur = array_slice($comptes, 1, 1, true);
        if (array_key_first($comptes) === 'blanc' && reset($secondeCouleur) / $total >= self::PART_MIN_SOUS_FOND) {
            $total -= $comptes['blanc'];
            unset($comptes['blanc']);
        }

        $couleur = array_key_first($comptes);
        $part = $comptes[$couleur] / $total;

        return [
            'couleur' => $couleur,
            'part' => (int) round($part * 100),
            'uni' => $part >= self::PART_MIN_UNI,
        ];
    }

    private function couleurLaPlusProche(int $r, int $g, int $b): string
    {
        $meilleure = 'noir';
        $distanceMin = PHP_INT_MAX;

        foreach (self::PALETTE as $nom => [$pr, $pg, $pb]) {
            $distance = ($r - $pr) ** 2 + ($g - $pg) ** 2 + ($b - $pb) ** 2;
            if ($distance < $distanceMin) {
                $distanceMin = $distance;
                $meilleure = $nom;
            }
        }

        return $meilleure;
    }
}
