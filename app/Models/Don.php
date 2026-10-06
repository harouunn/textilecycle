<?php

namespace App\Models;

use Database\Factories\DonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['association_id', 'user_id', 'type_article', 'quantite', 'poids_kg', 'etat_general', 'mode_remise', 'date_remise', 'adresse_collecte', 'statut', 'message'])]
class Don extends Model
{
    /** @use HasFactory<DonFactory> */
    use HasFactory;

    protected $table = 'dons';

    protected $attributes = [
        'statut' => 'propose',
    ];

    public const TYPES = [
        'vetements_homme' => 'Vêtements homme',
        'vetements_femme' => 'Vêtements femme',
        'vetements_enfant' => 'Vêtements enfant',
        'chaussures' => 'Chaussures',
        'linge_maison' => 'Linge de maison',
        'accessoires' => 'Accessoires',
    ];

    public const ETATS = [
        'bon' => 'Bon état',
        'moyen' => 'État moyen',
        'a_recycler' => 'À recycler',
    ];

    public const MODES = [
        'depot_sur_place' => 'Dépôt sur place',
        'collecte_a_domicile' => 'Collecte à domicile',
    ];

    public const STATUTS = [
        'propose' => 'Proposé',
        'accepte' => 'Accepté',
        'recu' => 'Reçu',
        'refuse' => 'Refusé',
    ];

    /** Color name per statut (Sneat theme colors / Bootstrap contextual classes). */
    public const STATUT_COLORS = [
        'propose' => 'warning',
        'accepte' => 'info',
        'recu' => 'success',
        'refuse' => 'danger',
    ];

    protected function casts(): array
    {
        return [
            'date_remise' => 'date',
            'poids_kg' => 'decimal:2',
            'quantite' => 'integer',
        ];
    }

    public function association(): BelongsTo
    {
        return $this->belongsTo(Association::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type_article] ?? $this->type_article;
    }

    public function etatLabel(): string
    {
        return self::ETATS[$this->etat_general] ?? $this->etat_general;
    }

    public function modeLabel(): string
    {
        return self::MODES[$this->mode_remise] ?? $this->mode_remise;
    }

    public function statutLabel(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    public function statutColor(): string
    {
        return self::STATUT_COLORS[$this->statut] ?? 'secondary';
    }

    public function isCancellable(): bool
    {
        return $this->statut === 'propose';
    }
}
