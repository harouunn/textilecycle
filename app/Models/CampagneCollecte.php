<?php

namespace App\Models;

use App\Enums\Dons\DonStatut;
use App\Enums\Dons\StatutCampagne;
use App\Enums\Dons\TypeArticle;
use Database\Factories\CampagneCollecteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Campagne de collecte d'une association. La progression ne compte que les kilos réellement reçus
 * (dons « Reçu » ou « Distribué ») : une promesse de don ne fait pas avancer la jauge.
 */
#[Fillable(['association_id', 'titre', 'description', 'type_articles_cibles', 'objectif_kg', 'date_debut', 'date_fin', 'statut', 'image'])]
class CampagneCollecte extends Model
{
    /** @use HasFactory<CampagneCollecteFactory> */
    use HasFactory;

    protected $table = 'campagnes_collecte';

    protected $attributes = [
        'statut' => 'brouillon',
    ];

    /** Statuts dont le poids compte dans la progression. */
    public const STATUTS_COMPTES = [DonStatut::Recu, DonStatut::Distribue];

    protected function casts(): array
    {
        return [
            'type_articles_cibles' => 'array',
            'objectif_kg' => 'integer',
            'date_debut' => 'date',
            'date_fin' => 'date',
            'statut' => StatutCampagne::class,
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
        return $this->hasMany(Don::class, 'campagne_id');
    }

    /**
     * Campagnes publiées et dans leur période : on peut leur rattacher un don.
     */
    public function scopeEnCours(Builder $query): void
    {
        $query->where('statut', StatutCampagne::Active)
            ->whereDate('date_debut', '<=', today())
            ->whereDate('date_fin', '>=', today());
    }

    /**
     * Ajoute kg_collectes (somme des poids reçus) sans requête par campagne.
     */
    public function scopeAvecProgression(Builder $query): void
    {
        $query->withSum(['dons as kg_collectes' => fn ($q) => $q->whereIn('statut', self::STATUTS_COMPTES)], 'poids_kg');
    }

    public function kgCollectes(): float
    {
        if (array_key_exists('kg_collectes', $this->attributes)) {
            return (float) $this->attributes['kg_collectes'];
        }

        return (float) $this->dons()->whereIn('statut', self::STATUTS_COMPTES)->sum('poids_kg');
    }

    /**
     * Pourcentage de l'objectif atteint (peut dépasser 100).
     */
    public function progression(): float
    {
        return $this->objectif_kg > 0 ? round($this->kgCollectes() / $this->objectif_kg * 100, 1) : 0.0;
    }

    public function estOuverte(): bool
    {
        return $this->statut === StatutCampagne::Active
            && $this->date_debut->lte(today())
            && $this->date_fin->gte(today());
    }

    /**
     * @return list<TypeArticle>
     */
    public function typesCibles(): array
    {
        return collect($this->type_articles_cibles ?? [])
            ->map(fn (string $type) => TypeArticle::tryFrom($type))
            ->filter()
            ->values()
            ->all();
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->image ? Storage::disk('public')->url($this->image) : null);
    }
}
