<?php

namespace App\Models;

use Database\Factories\AssociationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable(['nom', 'description', 'adresse', 'ville', 'latitude', 'longitude', 'telephone', 'email', 'site_web', 'logo', 'besoins', 'capacite_mensuelle_kg', 'active', 'responsable_id'])]
class Association extends Model
{
    /** @use HasFactory<AssociationFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'capacite_mensuelle_kg' => 'integer',
            'responsable_id' => 'integer',
        ];
    }

    public function dons(): HasMany
    {
        return $this->hasMany(Don::class);
    }

    /**
     * @return HasMany<CampagneCollecte, $this>
     */
    public function campagnes(): HasMany
    {
        return $this->hasMany(CampagneCollecte::class);
    }

    /**
     * @return HasMany<CreneauCollecte, $this>
     */
    public function creneaux(): HasMany
    {
        return $this->hasMany(CreneauCollecte::class);
    }

    /**
     * Associations à moins de $rayonKm du point donné, triées de la plus proche à la plus lointaine,
     * avec leur distance en km (attribut distance_km). Distance calculée par la formule de Haversine.
     */
    public function scopeProcheDe(Builder $query, float $latitude, float $longitude, float $rayonKm): void
    {
        self::fonctionsMathSqlite($query);

        // cos(Δσ) peut dépasser 1 d'un epsilon par arrondi : on le borne avant acos().
        $cosinus = 'cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))';
        $distance = "(6371 * acos(case when {$cosinus} > 1 then 1 else {$cosinus} end))";
        $bindings = [$latitude, $longitude, $latitude, $latitude, $longitude, $latitude];
        // Pré-filtre rectangulaire (1° de latitude ≈ 111 km) : évite le calcul sur les associations lointaines.
        $deltaLat = $rayonKm / 111;
        $deltaLng = $rayonKm / (111 * max(cos(deg2rad($latitude)), 0.01));

        $query->whereNotNull('latitude')->whereNotNull('longitude')
            ->whereBetween('latitude', [$latitude - $deltaLat, $latitude + $deltaLat])
            ->whereBetween('longitude', [$longitude - $deltaLng, $longitude + $deltaLng])
            ->whereRaw("{$distance} <= ?", [...$bindings, $rayonKm])
            ->select('associations.*')
            ->selectRaw("{$distance} as distance_km", $bindings)
            ->reorder()
            ->orderBy('distance_km');
    }

    /**
     * SQLite (tests, environnement minimal) n'a pas les fonctions trigonométriques : on les fournit depuis PHP.
     */
    private static function fonctionsMathSqlite(Builder $query): void
    {
        $connection = $query->getConnection();
        if ($connection->getDriverName() !== 'sqlite') {
            return;
        }

        $pdo = $connection->getPdo();
        foreach (['radians' => 'deg2rad', 'acos' => 'acos', 'cos' => 'cos', 'sin' => 'sin'] as $nom => $fonction) {
            method_exists($pdo, 'createFunction')
                ? $pdo->createFunction($nom, $fonction, 1)
                : $pdo->sqliteCreateFunction($nom, $fonction, 1);
        }
    }

    /**
     * Compte qui gère l'association dans l'« Espace association ».
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    public function scopeGereesPar(Builder $query, User $user): void
    {
        $query->where('responsable_id', $user->id);
    }

    public function estGereePar(User $user): bool
    {
        return $this->responsable_id !== null && $this->responsable_id === (int) $user->id;
    }

    /**
     * Public URL of the logo, or null when none was uploaded.
     */
    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->logo ? Storage::disk('public')->url($this->logo) : null);
    }
}
