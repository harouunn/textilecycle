<?php

namespace App\Models;

use Database\Factories\EtapeProjetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class EtapeProjet extends Model
{
    /** @use HasFactory<EtapeProjetFactory> */
    use HasFactory;

    protected $table = 'etapes_projet';

    protected $fillable = [
        'projet_upcycling_id',
        'numero',
        'titre',
        'contenu',
        'photo',
    ];

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(function (EtapeProjet $etape) {
            if ($etape->photo) {
                Storage::disk('public')->delete($etape->photo);
            }
        });
    }

    public function projet(): BelongsTo
    {
        return $this->belongsTo(ProjetUpcycling::class, 'projet_upcycling_id');
    }

    public function photoUrl(): ?string
    {
        return $this->photo ? Storage::disk('public')->url($this->photo) : null;
    }
}
