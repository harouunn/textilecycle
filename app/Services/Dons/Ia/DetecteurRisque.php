<?php

namespace App\Services\Dons\Ia;

use App\Models\Don;
use Illuminate\Support\Str;

/**
 * IA 5, étape « règles » — score de risque d'un don (0 à 100), sans IA :
 *   quantité > 200 (+30), poids par article irréaliste (+25), plus de 3 dons en 24 h par le même compte (+25),
 *   doublon d'un don de la dernière heure (+30), message vide (+5) ou qui ressemble à du spam (+20).
 * À partir de Don::SEUIL_RISQUE, le don est signalé « À vérifier » dans le back office.
 */
class DetecteurRisque
{
    /** Poids plausible d'un article (kg) : une chaussette ≈ 0,03 kg, une couette ≈ 3 kg. */
    private const POIDS_ARTICLE_MIN = 0.02;

    private const POIDS_ARTICLE_MAX = 5.0;

    /**
     * @return array{score: int, raisons: list<string>}
     */
    public function evaluer(Don $don): array
    {
        $score = 0;
        $raisons = [];

        if ($don->quantite > 200) {
            $score += 30;
            $raisons[] = "Quantité inhabituelle ({$don->quantite} articles).";
        }

        if ($don->poids_kg !== null && $don->quantite > 0) {
            $parArticle = (float) $don->poids_kg / $don->quantite;
            if ($parArticle < self::POIDS_ARTICLE_MIN || $parArticle > self::POIDS_ARTICLE_MAX) {
                $score += 25;
                $raisons[] = sprintf('Poids irréaliste : %s kg par article.', number_format($parArticle, 3, ',', ' '));
            }
        }

        $recents = Don::query()->where('user_id', $don->user_id)->whereKeyNot($don->id)
            ->where('created_at', '>=', $don->created_at->copy()->subDay())->count();
        if ($recents >= 3) {
            $score += 25;
            $raisons[] = ($recents + 1).' dons du même compte en 24 h.';
        }

        $doublon = Don::query()->where('user_id', $don->user_id)->whereKeyNot($don->id)
            ->where('association_id', $don->association_id)
            ->where('type_article', $don->type_article)
            ->where('quantite', $don->quantite)
            ->where('created_at', '>=', $don->created_at->copy()->subHour())
            ->exists();
        if ($doublon) {
            $score += 30;
            $raisons[] = 'Doublon probable d\'un don de la dernière heure.';
        }

        $message = trim((string) $don->message);
        if ($message === '') {
            $score += 5;
            $raisons[] = 'Message vide.';
        } elseif ($this->ressembleAduSpam($message)) {
            $score += 20;
            $raisons[] = 'Message qui ressemble à du spam.';
        }

        return ['score' => min(100, $score), 'raisons' => $raisons];
    }

    private function ressembleAduSpam(string $message): bool
    {
        $lettres = preg_replace('/[^\p{L}]/u', '', $message);

        return preg_match('~https?://|www\.~i', $message) === 1                         // liens
            || preg_match('/(.)\1{5,}/u', $message) === 1                                // « aaaaaa », « !!!!!! »
            || (mb_strlen($lettres) >= 12 && $lettres === mb_strtoupper($lettres))       // TOUT EN MAJUSCULES
            || preg_match('/\b(crypto|bitcoin|casino|gagnez|promo|whatsapp|telegram)\b/iu', $message) === 1
            || (mb_strlen($message) >= 25 && ! Str::contains($message, [' ', "\n"]));   // une longue suite sans espace
    }
}
