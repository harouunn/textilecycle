<?php

namespace App\Models;

use Database\Factories\AtelierFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Atelier extends Model
{
    /** @use HasFactory<AtelierFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nom',
        'description',
        'adresse',
        'ville',
        'code_postal',
        'telephone',
        'email',
        'actif',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'actif' => 'boolean',
    ];

    /**
     * @return HasMany<DemandeReparation, $this>
     */
    public function demandes(): HasMany
    {
        return $this->hasMany(DemandeReparation::class);
    }

    /**
     * Seuls les ateliers actifs sont visibles et peuvent recevoir des demandes.
     *
     * @param  Builder<Atelier>  $query
     */
    public function scopeActif(Builder $query): void
    {
        $query->where('actif', true);
    }
}
