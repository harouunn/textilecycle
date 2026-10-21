<?php

namespace App\Models;

use App\Enums\Dons\DonStatut;
use App\Enums\Dons\EtatGeneral;
use App\Enums\Dons\ModeRemise;
use App\Enums\Dons\TypeArticle;
use Database\Factories\DonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Le statut ne doit changer que par DonWorkflowService (historique + événement).
 * score_risque, ia_analyse et reference sont remplis par le système, jamais par un formulaire : ils ne sont pas fillable.
 */
#[Fillable(['vetement_id', 'association_id', 'campagne_id', 'creneau_id', 'user_id', 'type_article', 'quantite', 'poids_kg', 'etat_general', 'mode_remise', 'date_remise', 'adresse_collecte', 'statut', 'message'])]
class Don extends Model
{
    /** @use HasFactory<DonFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'dons';

    protected $attributes = [
        'statut' => 'propose',
    ];

    /*
     * Tableaux valeur => libellé, conservés pour les autres modules (statistiques, tableau de bord, factories).
     * Ils doivent rester identiques aux enums App\Enums\Dons\* (vérifié par DonWorkflowTest).
     */
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
        'planifie' => 'Planifié',
        'recu' => 'Reçu',
        'distribue' => 'Distribué',
        'refuse' => 'Refusé',
        'annule' => 'Annulé',
    ];

    /** Color name per statut (Sneat theme colors / Bootstrap contextual classes). */
    public const STATUT_COLORS = [
        'propose' => 'warning',
        'accepte' => 'info',
        'planifie' => 'primary',
        'recu' => 'success',
        'distribue' => 'success',
        'refuse' => 'danger',
        'annule' => 'secondary',
    ];

    /** À partir de ce score (règles d'AnalyserRisqueDon), le don est signalé « À vérifier ». */
    public const SEUIL_RISQUE = 40;

    protected static function booted(): void
    {
        // Première étape de l'historique, quel que soit le chemin de création (formulaire, back office, module Dépôt).
        static::created(function (Don $don) {
            $don->historiques()->create([
                'user_id' => auth()->id(),
                'ancien_statut' => null,
                'nouveau_statut' => $don->statut,
            ]);
        });
    }

    protected function casts(): array
    {
        return [
            'statut' => DonStatut::class,
            'type_article' => TypeArticle::class,
            'etat_general' => EtatGeneral::class,
            'mode_remise' => ModeRemise::class,
            'date_remise' => 'date',
            'poids_kg' => 'decimal:2',
            'quantite' => 'integer',
            'score_risque' => 'integer',
            'ia_analyse' => 'array',
        ];
    }

    /**
     * Une association archivée (soft delete) reste visible dans l'historique de ses dons.
     */
    public function association(): BelongsTo
    {
        return $this->belongsTo(Association::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vêtement du catalogue (module Dépôt) donné à l'association, s'il y en a un.
     */
    public function vetement(): BelongsTo
    {
        return $this->belongsTo(Vetement::class);
    }

    /**
     * @return HasMany<DonHistorique, $this>
     */
    public function historiques(): HasMany
    {
        return $this->hasMany(DonHistorique::class)->oldest()->orderBy('id');
    }

    public function campagne(): BelongsTo
    {
        return $this->belongsTo(CampagneCollecte::class, 'campagne_id');
    }

    public function creneau(): BelongsTo
    {
        return $this->belongsTo(CreneauCollecte::class, 'creneau_id');
    }

    /**
     * @return HasMany<DonPoint, $this>
     */
    public function points(): HasMany
    {
        return $this->hasMany(DonPoint::class);
    }

    /**
     * Référence du reçu, ex. DON-2026-000123 : année de réception et numéro du don.
     */
    public static function genererReference(int $id, ?int $annee = null): string
    {
        return sprintf('DON-%d-%06d', $annee ?? now()->year, $id);
    }

    /**
     * Les textiles sont arrivés à l'association : ils comptent pour l'impact, les points et les campagnes.
     */
    public function estRecu(): bool
    {
        return in_array($this->statut, [DonStatut::Recu, DonStatut::Distribue], true);
    }

    public function estARisque(): bool
    {
        return $this->score_risque !== null && $this->score_risque >= self::SEUIL_RISQUE;
    }

    public function scopeRecus(Builder $query): void
    {
        $query->whereIn('statut', [DonStatut::Recu, DonStatut::Distribue]);
    }

    /**
     * Filtres de la liste du back office, partagés avec l'export Excel / CSV.
     *
     * @param  array{q?: ?string, association_id?: mixed, statut?: ?string, type_article?: ?string, campagne_id?: mixed, risque?: mixed}  $filters
     */
    public function scopeFiltrer(Builder $query, array $filters): void
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $statut = DonStatut::tryFrom((string) ($filters['statut'] ?? ''));
        $type = TypeArticle::tryFrom((string) ($filters['type_article'] ?? ''));

        $query
            ->when($q !== '', fn ($query) => $query->where(function ($query) use ($q) {
                $search = "%{$q}%";
                $query->where('message', 'like', $search)
                    ->orWhere('adresse_collecte', 'like', $search)
                    ->orWhere('reference', 'like', $search)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $search)->orWhere('email', 'like', $search))
                    ->orWhereHas('association', fn ($a) => $a->where('nom', 'like', $search));
            }))
            ->when($filters['association_id'] ?? null, fn ($query, $id) => $query->where('association_id', $id))
            ->when($filters['campagne_id'] ?? null, fn ($query, $id) => $query->where('campagne_id', $id))
            ->when($statut, fn ($query) => $query->where('statut', $statut))
            ->when($type, fn ($query) => $query->where('type_article', $type))
            ->when(filter_var($filters['risque'] ?? false, FILTER_VALIDATE_BOOL), fn ($query) => $query->where('score_risque', '>=', self::SEUIL_RISQUE));
    }

    public function typeLabel(): string
    {
        return $this->type_article?->label() ?? '';
    }

    public function etatLabel(): string
    {
        return $this->etat_general?->label() ?? '';
    }

    public function modeLabel(): string
    {
        return $this->mode_remise?->label() ?? '';
    }

    public function statutLabel(): string
    {
        return $this->statut->label();
    }

    public function statutColor(): string
    {
        return $this->statut->color();
    }

    public function isCancellable(): bool
    {
        return $this->statut === DonStatut::Propose;
    }
}
