<?php

namespace App\Models;

use App\Enums\Upcycling\Difficulte;
use App\Enums\Upcycling\StatutProjet;
use Database\Factories\ProjetUpcyclingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ProjetUpcycling extends Model
{
    /** @use HasFactory<ProjetUpcyclingFactory> */
    use HasFactory;

    protected $table = 'projets_upcycling';

    protected $fillable = [
        'user_id',
        'titre',
        'description',
        'vetement_origine',
        'resultat',
        'difficulte',
        'duree_minutes',
        'materiel_necessaire',
        'photo_avant',
        'photo_apres',
        'statut',
    ];

    protected $attributes = [
        'statut' => 'brouillon',
    ];

    protected function casts(): array
    {
        return [
            'difficulte' => Difficulte::class,
            'statut' => StatutProjet::class,
            'duree_minutes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Les étapes sont supprimées en cascade par la base : on nettoie aussi leurs photos.
        static::deleting(function (ProjetUpcycling $projet) {
            $fichiers = $projet->etapes()->whereNotNull('photo')->pluck('photo')
                ->merge([$projet->photo_avant, $projet->photo_apres])
                ->filter()
                ->all();

            Storage::disk('public')->delete($fichiers);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function etapes(): HasMany
    {
        return $this->hasMany(EtapeProjet::class)->orderBy('numero');
    }

    public function scopePublie(Builder $query): void
    {
        $query->where('statut', StatutProjet::Publie);
    }

    public function estPublie(): bool
    {
        return $this->statut === StatutProjet::Publie;
    }

    public function prochainNumeroEtape(): int
    {
        return (int) $this->etapes()->max('numero') + 1;
    }

    /** Durée lisible : « 45 min », « 2 h », « 1 h 30 ». */
    public function dureeFormatee(): string
    {
        $heures = intdiv($this->duree_minutes, 60);
        $minutes = $this->duree_minutes % 60;

        if ($heures === 0) {
            return $minutes.' min';
        }

        return $heures.' h'.($minutes ? ' '.str_pad((string) $minutes, 2, '0', STR_PAD_LEFT) : '');
    }

    public function photoAvantUrl(): ?string
    {
        return $this->photo_avant ? Storage::disk('public')->url($this->photo_avant) : null;
    }

    public function photoApresUrl(): ?string
    {
        return $this->photo_apres ? Storage::disk('public')->url($this->photo_apres) : null;
    }
}
