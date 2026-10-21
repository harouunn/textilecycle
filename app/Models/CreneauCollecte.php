<?php

namespace App\Models;

use App\Enums\Dons\DonStatut;
use Database\Factories\CreneauCollecteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Créneau de collecte à domicile. Un don refusé ou annulé libère sa place.
 */
#[Fillable(['association_id', 'date', 'heure_debut', 'heure_fin', 'capacite_max'])]
class CreneauCollecte extends Model
{
    /** @use HasFactory<CreneauCollecteFactory> */
    use HasFactory;

    protected $table = 'creneaux_collecte';

    /** Statuts qui n'occupent plus de place sur le créneau. */
    public const STATUTS_LIBERES = [DonStatut::Refuse, DonStatut::Annule];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'capacite_max' => 'integer',
        ];
    }

    public function association(): BelongsTo
    {
        return $this->belongsTo(Association::class)->withTrashed();
    }

    /**
     * @return HasMany<Don, $this>
     */
    public function dons(): HasMany
    {
        return $this->hasMany(Don::class, 'creneau_id');
    }

    /**
     * @return HasMany<Don, $this>
     */
    public function reservations(): HasMany
    {
        return $this->dons()->whereNotIn('statut', self::STATUTS_LIBERES);
    }

    public function scopeAvecReservations(Builder $query): void
    {
        $query->withCount(['dons as reservations_count' => fn ($q) => $q->whereNotIn('statut', self::STATUTS_LIBERES)]);
    }

    /**
     * Créneaux à venir qui ont encore au moins une place.
     */
    public function scopeDisponibles(Builder $query): void
    {
        $query->whereDate('date', '>=', today())
            ->where('capacite_max', '>', Don::query()
                ->selectRaw('count(*)')
                ->whereColumn('dons.creneau_id', 'creneaux_collecte.id')
                ->whereNotIn('statut', self::STATUTS_LIBERES));
    }

    public function nombreReservations(): int
    {
        return array_key_exists('reservations_count', $this->attributes)
            ? (int) $this->attributes['reservations_count']
            : $this->reservations()->count();
    }

    public function placesRestantes(): int
    {
        return max(0, $this->capacite_max - $this->nombreReservations());
    }

    public function libelle(): string
    {
        return ucfirst($this->date->locale('fr')->translatedFormat('l d/m/Y')).' · '.substr($this->heure_debut, 0, 5).'–'.substr($this->heure_fin, 0, 5);
    }
}
