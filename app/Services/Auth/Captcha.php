<?php

namespace App\Services\Auth;

/**
 * CAPTCHA en image (anti-robot), généré avec GD, sans service externe ni clé.
 *
 * Un code de 5 caractères (sans les caractères ambigus 0/O, 1/I/L) est dessiné avec des lettres
 * inclinées, décalées, de couleurs différentes, sur un fond bruité de lignes et de points.
 * Seule son empreinte est gardée en session ; il expire après 10 minutes et ne sert qu'une fois.
 */
class Captcha
{
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    private const LONGUEUR = 5;

    private const DUREE_MINUTES = 10;

    private const SESSION = 'captcha';

    private const LARGEUR = 200;

    private const HAUTEUR = 64;

    public function actif(): bool
    {
        return (bool) config('auth.captcha', true);
    }

    /**
     * Crée un nouveau code et renvoie l'image PNG correspondante.
     */
    public function genererImage(): string
    {
        $code = '';
        for ($i = 0; $i < self::LONGUEUR; $i++) {
            $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        session()->put(self::SESSION, [
            'empreinte' => hash('sha256', $code),
            'expire' => now()->addMinutes(self::DUREE_MINUTES)->getTimestamp(),
        ]);

        return $this->dessiner($code);
    }

    /**
     * Vérifie la saisie (sans tenir compte des majuscules ni des espaces). Le code est ensuite détruit.
     */
    public function verifier(?string $saisie): bool
    {
        $attendu = session()->pull(self::SESSION);
        $saisie = strtoupper(preg_replace('/\s+/', '', (string) $saisie));

        return is_array($attendu)
            && $attendu['expire'] >= now()->getTimestamp()
            && hash_equals($attendu['empreinte'], hash('sha256', $saisie));
    }

    private function dessiner(string $code): string
    {
        $image = imagecreatetruecolor(self::LARGEUR, self::HAUTEUR);
        imagefilledrectangle($image, 0, 0, self::LARGEUR, self::HAUTEUR, imagecolorallocate($image, 246, 244, 239));
        $police = resource_path('fonts/captcha.ttf');

        // Bruit de fond : points et lignes de couleurs claires.
        for ($i = 0; $i < 350; $i++) {
            imagesetpixel($image, random_int(0, self::LARGEUR), random_int(0, self::HAUTEUR), $this->couleur($image, 140, 210));
        }
        for ($i = 0; $i < 6; $i++) {
            imageline($image, random_int(0, self::LARGEUR), random_int(0, self::HAUTEUR), random_int(0, self::LARGEUR), random_int(0, self::HAUTEUR), $this->couleur($image, 150, 200));
        }

        // Lettres inclinées, décalées et de tailles variables.
        foreach (str_split($code) as $i => $lettre) {
            imagettftext($image, random_int(24, 30), random_int(-28, 28), 18 + $i * 35 + random_int(-4, 4), random_int(40, 52),
                $this->couleur($image, 20, 90), $police, $lettre);
        }

        // Deux lignes sombres qui traversent le texte, pour gêner la lecture automatique.
        imagesetthickness($image, 2);
        for ($i = 0; $i < 2; $i++) {
            imageline($image, 0, random_int(15, 50), self::LARGEUR, random_int(15, 50), $this->couleur($image, 60, 110));
        }

        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    private function couleur(\GdImage $image, int $min, int $max): int
    {
        return imagecolorallocate($image, random_int($min, $max), random_int($min, $max), random_int($min, $max));
    }
}
