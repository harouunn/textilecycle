<?php

namespace App\Services\Dons;

use App\Models\Don;
use App\Models\DonPoint;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Gamification : points (table dons_points), badges et classement public.
 * Les badges ne sont pas stockés : ils sont recalculés à partir des dons reçus, donc toujours justes.
 */
class BadgeService
{
    public const RAISON_DON_RECU = 'don_recu';

    /**
     * +N points par kilo (config textilecycle.dons.points_par_kg) quand un don est reçu.
     * Un don ne rapporte qu'une fois, même s'il repasse par « Reçu ».
     */
    public function crediterDonRecu(Don $don): ?DonPoint
    {
        $points = (int) round((float) $don->poids_kg * (int) config('textilecycle.dons.points_par_kg'));
        if ($points <= 0) {
            return null;
        }

        return DonPoint::query()->firstOrCreate(
            ['don_id' => $don->id, 'raison' => self::RAISON_DON_RECU],
            ['user_id' => $don->user_id, 'points' => $points],
        );
    }

    public function points(User $user): int
    {
        return (int) DonPoint::query()->whereBelongsTo($user)->sum('points');
    }

    /**
     * Tous les badges, obtenus ou non, avec la progression vers chacun.
     *
     * @return list<array{cle: string, titre: string, description: string, obtenu: bool, progression: int}>
     */
    public function badges(User $user): array
    {
        $totaux = Don::query()->whereBelongsTo($user)->recus()
            ->selectRaw('count(*) as dons, coalesce(sum(poids_kg), 0) as kg')
            ->first();
        $valeurs = ['dons' => (int) $totaux->dons, 'kg' => (float) $totaux->kg];

        return collect(config('textilecycle.dons.badges'))
            ->map(function (array $badge, string $cle) use ($valeurs) {
                $critere = isset($badge['kg']) ? 'kg' : 'dons';
                $seuil = (float) $badge[$critere];

                return [
                    'cle' => $cle,
                    'titre' => $badge['titre'],
                    'description' => $badge['description'],
                    'obtenu' => $valeurs[$critere] >= $seuil,
                    'progression' => (int) min(100, floor($valeurs[$critere] / $seuil * 100)),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<string> titres des badges obtenus
     */
    public function badgesObtenus(User $user): array
    {
        return collect($this->badges($user))->where('obtenu', true)->pluck('titre')->all();
    }

    /**
     * Top 10 public : seul le prénom (premier mot du nom) est affiché.
     *
     * @return list<array{rang: int, prenom: string, points: int}>
     */
    public function classement(int $limite = 10): array
    {
        return Cache::remember("dons.classement.{$limite}", config('textilecycle.dons.cache_ttl'), fn () => DonPoint::query()
            ->join('users', 'users.id', '=', 'dons_points.user_id')
            ->groupBy('dons_points.user_id', 'users.name')
            ->select('users.name', DB::raw('sum(dons_points.points) as total'))
            ->orderByDesc('total')
            ->orderBy('dons_points.user_id')
            ->limit($limite)
            ->get()
            ->values()
            ->map(fn ($ligne, int $i) => [
                'rang' => $i + 1,
                'prenom' => Str::of($ligne->name)->trim()->explode(' ')->first() ?: 'Donateur',
                'points' => (int) $ligne->total,
            ])
            ->all());
    }
}
