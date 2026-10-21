<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un appel à l'IA : quelle fonctionnalité, combien de temps, réussi ou non (et pourquoi).
 */
#[Fillable(['user_id', 'fonctionnalite', 'duree_ms', 'succes', 'erreur'])]
class AiLog extends Model
{
    public const FONCTIONNALITES = [
        'analyse_photo' => 'Analyse photo',
        'matching' => 'Associations recommandées',
        'assistant' => 'Assistant donateur',
        'redaction' => 'Rédaction',
        'risque' => 'Anomalies',
    ];

    protected function casts(): array
    {
        return [
            'succes' => 'boolean',
            'duree_ms' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fonctionnaliteLabel(): string
    {
        return self::FONCTIONNALITES[$this->fonctionnalite] ?? $this->fonctionnalite;
    }
}
