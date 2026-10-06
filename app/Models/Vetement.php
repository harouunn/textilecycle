<?php

namespace App\Models;

use App\Enums\Depot\Etat;
use App\Enums\Depot\Genre;
use App\Enums\Depot\StatutVetement;
use App\Enums\Depot\Taille;
use Database\Factories\VetementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Vetement extends Model
{
    /** @use HasFactory<VetementFactory> */
    use HasFactory;

    public const PHOTO_DIRECTORY = 'vetements';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'categorie_id',
        'user_id',
        'titre',
        'description',
        'taille',
        'genre',
        'matiere',
        'etat',
        'photo',
        'statut',
        'date_depot',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'statut' => 'disponible',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'taille' => Taille::class,
            'genre' => Genre::class,
            'etat' => Etat::class,
            'statut' => StatutVetement::class,
            'date_depot' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Categorie, $this>
     */
    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * URL publique de la photo, ou image par défaut du template si aucune photo.
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn (): string => $this->photo
            ? asset('storage/'.$this->photo)
            : asset('front/images/product-item-'.((($this->id ?? 0) % 10) + 1).'.jpg'));
    }

    /**
     * Supprime la photo stockée, si elle existe.
     */
    public function deletePhoto(): void
    {
        if ($this->photo) {
            Storage::disk('public')->delete($this->photo);
        }
    }

    /**
     * @param  Builder<Vetement>  $query
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where('titre', 'like', "%{$search}%"))
            ->when($filters['categorie'] ?? null, fn (Builder $query, $categorieId) => $query->where('categorie_id', $categorieId))
            ->when($filters['etat'] ?? null, fn (Builder $query, string $etat) => $query->where('etat', $etat))
            ->when($filters['statut'] ?? null, fn (Builder $query, string $statut) => $query->where('statut', $statut));
    }
}
