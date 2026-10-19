<?php

namespace App\Services\Depot;

use App\Models\Vetement;
use Illuminate\Support\Facades\Storage;

/**
 * Allège les photos déposées avec GD : la photo d'origine est ramenée à 1600 px au plus,
 * et une miniature de 600 px est créée pour les listes. Une page du catalogue charge ainsi
 * quelques dizaines de Ko par vêtement au lieu de plusieurs Mo.
 */
class OptimiseurPhoto
{
    private const TAILLE_MAX = 1600;

    private const TAILLE_MINIATURE = 600;

    private const QUALITE_JPEG = 82;

    public function optimiser(string $chemin): void
    {
        $disque = Storage::disk('public');
        $image = @imagecreatefromstring((string) $disque->get($chemin));
        if ($image === false) {
            return;
        }

        // Photo d'origine trop grande : on la réduit en gardant son format.
        if (max(imagesx($image), imagesy($image)) > self::TAILLE_MAX) {
            $reduite = $this->redimensionner($image, self::TAILLE_MAX);
            $disque->put($chemin, $this->encoder($reduite, pathinfo($chemin, PATHINFO_EXTENSION)));
        }

        $miniature = $this->redimensionner($image, self::TAILLE_MINIATURE);
        $disque->put(Vetement::cheminMiniature($chemin), $this->encoder($miniature, pathinfo($chemin, PATHINFO_EXTENSION)));
    }

    private function redimensionner(\GdImage $image, int $tailleMax): \GdImage
    {
        $largeur = imagesx($image);
        $hauteur = imagesy($image);
        $ratio = min(1, $tailleMax / max($largeur, $hauteur));

        $copie = imagescale($image, max(1, (int) round($largeur * $ratio)), max(1, (int) round($hauteur * $ratio)));

        return $copie === false ? $image : $copie;
    }

    private function encoder(\GdImage $image, string $extension): string
    {
        ob_start();
        match (strtolower($extension)) {
            'png' => imagepng($image, null, 6),
            'webp' => imagewebp($image, null, self::QUALITE_JPEG),
            default => imagejpeg($image, null, self::QUALITE_JPEG),
        };

        return (string) ob_get_clean();
    }
}
