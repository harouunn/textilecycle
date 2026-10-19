<?php

namespace App\Models;

use App\Enums\Depot\Etat;
use App\Enums\Depot\Genre;
use App\Enums\Depot\Moderation;
use App\Enums\Depot\StatutReservation;
use App\Enums\Depot\StatutVetement;
use App\Enums\Depot\Taille;
use Database\Factories\VetementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Vetement extends Model
{
    /** @use HasFactory<VetementFactory> */
    use HasFactory;

    public const PHOTO_DIRECTORY = 'vetements';

    /** Clé de session de la photo enregistrée par « Analyser la photo », en attente du dépôt. */
    public const SESSION_PHOTO_ANALYSEE = 'depot.photo_analysee';

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
        'photo_empreinte',
        'statut',
        'moderation',
        'motif_refus',
        'date_depot',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'statut' => 'disponible',
        'moderation' => 'approuve',
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
            'moderation' => Moderation::class,
            'date_depot' => 'date',
        ];
    }

    /**
     * Vêtements visibles dans le catalogue public : approuvés par l'admin et disponibles.
     *
     * @param  Builder<Vetement>  $query
     */
    public function scopeAuCatalogue(Builder $query): void
    {
        $query->where('moderation', Moderation::Approuve)->where('statut', StatutVetement::Disponible);
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
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Le cycle du vêtement à travers les autres gestions : réparation, upcycling, don.
     *
     * @return HasMany<DemandeReparation, $this>
     */
    public function demandesReparation(): HasMany
    {
        return $this->hasMany(DemandeReparation::class);
    }

    /**
     * @return HasMany<ProjetUpcycling, $this>
     */
    public function projetsUpcycling(): HasMany
    {
        return $this->hasMany(ProjetUpcycling::class);
    }

    /**
     * @return HasMany<Don, $this>
     */
    public function dons(): HasMany
    {
        return $this->hasMany(Don::class);
    }

    /**
     * La réservation en cours (en attente ou confirmée), s'il y en a une.
     *
     * @return HasOne<Reservation, $this>
     */
    public function reservationActive(): HasOne
    {
        return $this->hasOne(Reservation::class)->ofMany(
            ['id' => 'max'],
            fn (Builder $query) => $query->whereIn('statut', StatutReservation::actives()),
        );
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
     * Miniature légère pour les listes (catalogue, tableaux), sinon la photo d'origine.
     */
    protected function miniatureUrl(): Attribute
    {
        return Attribute::get(function (): string {
            $miniature = $this->photo ? self::cheminMiniature($this->photo) : null;

            return $miniature && Storage::disk('public')->exists($miniature)
                ? asset('storage/'.$miniature)
                : $this->photo_url;
        });
    }

    public static function cheminMiniature(string $photo): string
    {
        return self::PHOTO_DIRECTORY.'/miniatures/'.basename($photo);
    }

    /**
     * Supprime la photo stockée et sa miniature, si elles existent.
     */
    public function deletePhoto(): void
    {
        if ($this->photo) {
            Storage::disk('public')->delete([$this->photo, self::cheminMiniature($this->photo)]);
        }
    }

    /**
     * Ordre d'affichage du catalogue.
     *
     * @param  Builder<Vetement>  $query
     */
    public function scopeTrier(Builder $query, ?string $tri): void
    {
        match ($tri) {
            'titre' => $query->orderBy('titre'),
            'anciens' => $query->oldest('date_depot')->oldest('id'),
            default => $query->latest('date_depot')->latest('id'),
        };
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
            ->when($filters['genre'] ?? null, fn (Builder $query, string $genre) => $query->where('genre', $genre))
            ->when($filters['taille'] ?? null, fn (Builder $query, string $taille) => $query->where('taille', $taille))
            ->when($filters['statut'] ?? null, fn (Builder $query, string $statut) => $query->where('statut', $statut))
            ->when($filters['moderation'] ?? null, fn (Builder $query, string $moderation) => $query->where('moderation', $moderation));
    }
}
