<?php

namespace App\Models;

use App\Enums\Dons\DonStatut;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une étape de la vie d'un don. ancien_statut est vide pour la création du don.
 */
#[Fillable(['user_id', 'ancien_statut', 'nouveau_statut', 'commentaire'])]
class DonHistorique extends Model
{
    protected $table = 'don_historiques';

    protected function casts(): array
    {
        return [
            'ancien_statut' => DonStatut::class,
            'nouveau_statut' => DonStatut::class,
        ];
    }

    public function don(): BelongsTo
    {
        return $this->belongsTo(Don::class)->withTrashed();
    }

    /**
     * Auteur du changement (vide si le compte a été supprimé ou si le changement vient du système).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function estCreation(): bool
    {
        return $this->ancien_statut === null;
    }
}
