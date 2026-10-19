<?php

namespace App\Models;

use App\Enums\Depot\Creneau;
use App\Enums\Depot\StatutReservation;
use Database\Factories\ReservationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Réservation d'un vêtement du catalogue par un client, qui vient ensuite le retirer.
 */
class Reservation extends Model
{
    /** @use HasFactory<ReservationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'vetement_id',
        'user_id',
        'date_retrait',
        'creneau',
        'telephone',
        'message',
        'statut',
        'motif_annulation',
        'confirmee_le',
        'retiree_le',
        'annulee_le',
        'rappel_envoye_le',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'statut' => 'en_attente',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_retrait' => 'date',
            'creneau' => Creneau::class,
            'statut' => StatutReservation::class,
            'confirmee_le' => 'datetime',
            'retiree_le' => 'datetime',
            'annulee_le' => 'datetime',
            'rappel_envoye_le' => 'datetime',
        ];
    }

    /**
     * Code lisible communiqué au client, ex. RES-7K2Q9M.
     */
    protected static function booted(): void
    {
        static::creating(function (Reservation $reservation) {
            if (blank($reservation->code)) {
                do {
                    $code = 'RES-'.Str::upper(Str::random(6));
                } while (static::query()->where('code', $code)->exists());
                $reservation->code = $code;
            }
        });
    }

    /**
     * @return BelongsTo<Vetement, $this>
     */
    public function vetement(): BelongsTo
    {
        return $this->belongsTo(Vetement::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<Reservation>  $query
     */
    public function scopeActives(Builder $query): void
    {
        $query->whereIn('statut', StatutReservation::actives());
    }

    /**
     * @param  Builder<Reservation>  $query
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['statut'] ?? null, fn (Builder $query, string $statut) => $query->where('statut', $statut))
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(fn (Builder $query) => $query
                ->where('code', 'like', "%{$search}%")
                ->orWhereHas('vetement', fn (Builder $query) => $query->where('titre', 'like', "%{$search}%"))
                ->orWhereHas('user', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))));
    }
}
