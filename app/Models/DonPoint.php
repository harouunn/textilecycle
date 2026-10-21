<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Points de gamification gagnés par un donateur (une ligne par gain, avec sa raison).
 */
#[Fillable(['user_id', 'don_id', 'points', 'raison'])]
class DonPoint extends Model
{
    protected $table = 'dons_points';

    protected function casts(): array
    {
        return [
            'points' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function don(): BelongsTo
    {
        return $this->belongsTo(Don::class)->withTrashed();
    }
}
