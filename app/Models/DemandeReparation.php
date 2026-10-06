<?php

namespace App\Models;

use App\Enums\Ateliers\StatutDemande;
use App\Enums\Ateliers\TypeReparation;
use App\Enums\Ateliers\TypeVetement;
use App\Services\Ateliers\DiagnosticReparation;
use Database\Factories\DemandeReparationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DemandeReparation extends Model
{
    /** @use HasFactory<DemandeReparationFactory> */
    use HasFactory;

    public const PHOTO_DIRECTORY = 'reparations';

    public const DEVISE = 'DT';

    protected $table = 'demandes_reparation';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'atelier_id',
        'user_id',
        'titre',
        'type_vetement',
        'description',
        'photo',
        'reparations',
        'cout_estime',
        'delai_estime_jours',
        'diagnostic',
        'statut',
        'cout_final',
        'date_prevue',
        'reponse_atelier',
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
            'type_vetement' => TypeVetement::class,
            'reparations' => AsEnumCollection::of(TypeReparation::class),
            'cout_estime' => 'decimal:2',
            'delai_estime_jours' => 'integer',
            'statut' => StatutDemande::class,
            'cout_final' => 'decimal:2',
            'date_prevue' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Atelier, $this>
     */
    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calcule le diagnostic (réparations, coût, délai) à partir de la description, de la photo
     * et de la charge actuelle de l'atelier, puis le recopie dans les attributs.
     */
    public function diagnostiquer(): static
    {
        $demandesEnCours = static::query()
            ->where('atelier_id', $this->atelier_id)
            ->whereIn('statut', StatutDemande::occupantLAtelier())
            ->when($this->exists, fn (Builder $query) => $query->whereKeyNot($this->getKey()))
            ->count();

        $diagnostic = app(DiagnosticReparation::class)->analyser(
            $this->description,
            (bool) $this->photo,
            $this->type_vetement,
            $demandesEnCours,
        );

        return $this->fill($diagnostic);
    }

    /**
     * Une demande ne peut être annulée par son auteur que tant que l'atelier ne l'a pas prise en charge.
     */
    public function estAnnulable(): bool
    {
        return $this->statut === StatutDemande::EnAttente;
    }

    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->photo ? asset('storage/'.$this->photo) : null);
    }

    /**
     * Coût à afficher : le coût final s'il est fixé, sinon l'estimation.
     */
    protected function coutAffiche(): Attribute
    {
        return Attribute::get(fn (): string => self::formaterMontant((float) ($this->cout_final ?? $this->cout_estime)));
    }

    public static function formaterMontant(float $montant): string
    {
        return number_format($montant, 2, ',', ' ').' '.self::DEVISE;
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
     * @param  Builder<DemandeReparation>  $query
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where('titre', 'like', "%{$search}%"))
            ->when($filters['atelier'] ?? null, fn (Builder $query, $atelierId) => $query->where('atelier_id', $atelierId))
            ->when($filters['statut'] ?? null, fn (Builder $query, string $statut) => $query->where('statut', $statut));
    }
}
