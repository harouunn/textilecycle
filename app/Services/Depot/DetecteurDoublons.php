<?php

namespace App\Services\Depot;

use App\Models\Vetement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Détection de photos en double par empreinte visuelle (« difference hash », 64 bits).
 * La photo est réduite à 9 × 8 pixels en niveaux de gris ; chaque bit dit si un pixel est plus clair
 * que son voisin de droite. Deux photos presque identiques (même vêtement, recadrage ou compression
 * différents) ont des empreintes qui diffèrent de quelques bits seulement (distance de Hamming).
 */
class DetecteurDoublons
{
    /** Au-delà de 6 bits différents sur 64, les photos sont considérées comme différentes. */
    public const SEUIL = 6;

    public function empreinte(string $cheminPublic): ?string
    {
        $image = @imagecreatefromstring((string) Storage::disk('public')->get($cheminPublic));
        if ($image === false) {
            return null;
        }

        $petite = imagescale($image, 9, 8);
        $bits = '';
        for ($y = 0; $y < 8; $y++) {
            for ($x = 0; $x < 8; $x++) {
                $bits .= $this->luminosite($petite, $x, $y) > $this->luminosite($petite, $x + 1, $y) ? '1' : '0';
            }
        }

        // Conversion par blocs de 4 bits : base_convert() perdrait de la précision sur 64 bits.
        return implode('', array_map(fn (string $bloc) => dechex(bindec($bloc)), str_split($bits, 4)));
    }

    public function distance(string $a, string $b): int
    {
        $distance = 0;
        foreach (str_split($a, 4) as $i => $bloc) {
            $distance += substr_count(decbin(hexdec($bloc) ^ hexdec(substr($b, $i * 4, 4))), '1');
        }

        return $distance;
    }

    /**
     * Autres vêtements dont la photo ressemble à celle-ci.
     *
     * @return Collection<int, Vetement>
     */
    public function doublonsDe(Vetement $vetement): Collection
    {
        if (! $vetement->photo_empreinte) {
            return collect();
        }

        return Vetement::query()
            ->whereNotNull('photo_empreinte')
            ->whereKeyNot($vetement->id)
            ->get(['id', 'titre', 'user_id', 'photo', 'photo_empreinte', 'moderation', 'statut'])
            ->filter(fn (Vetement $autre) => $this->distance($vetement->photo_empreinte, $autre->photo_empreinte) <= self::SEUIL)
            ->values();
    }

    private function luminosite(\GdImage $image, int $x, int $y): float
    {
        $rgb = imagecolorsforindex($image, imagecolorat($image, $x, $y));

        return 0.299 * $rgb['red'] + 0.587 * $rgb['green'] + 0.114 * $rgb['blue'];
    }
}
