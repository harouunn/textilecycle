<?php

namespace App\Models;

use Database\Factories\CategorieFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Categorie extends Model
{
    /** @use HasFactory<CategorieFactory> */
    use HasFactory;

    public const COULEUR_PAR_DEFAUT = '#3d7a4f';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nom',
        'slug',
        'description',
        'icone',
        'couleur',
        'ordre',
        'active',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'couleur' => self::COULEUR_PAR_DEFAUT,
        'ordre' => 0,
        'active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ordre' => 'integer',
            'active' => 'boolean',
        ];
    }

    /**
     * Le slug est déduit du nom, et reste unique même si deux noms se ressemblent.
     */
    protected static function booted(): void
    {
        static::saving(function (Categorie $categorie) {
            if ($categorie->isDirty('nom') || blank($categorie->slug)) {
                $base = Str::slug($categorie->nom) ?: 'categorie';
                $slug = $base;
                for ($i = 2; static::query()->where('slug', $slug)->whereKeyNot($categorie->getKey())->exists(); $i++) {
                    $slug = "{$base}-{$i}";
                }
                $categorie->slug = $slug;
            }
        });
    }

    /**
     * @return HasMany<Vetement, $this>
     */
    public function vetements(): HasMany
    {
        return $this->hasMany(Vetement::class);
    }

    /**
     * Catégories proposées aux déposants et dans le catalogue, dans l'ordre choisi par l'admin.
     *
     * @param  Builder<Categorie>  $query
     */
    public function scopeActives(Builder $query): void
    {
        $query->where('active', true)->orderBy('ordre')->orderBy('nom');
    }
}
