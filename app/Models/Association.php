<?php

namespace App\Models;

use Database\Factories\AssociationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['nom', 'description', 'adresse', 'ville', 'telephone', 'email', 'site_web', 'logo', 'besoins', 'active'])]
class Association extends Model
{
    /** @use HasFactory<AssociationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function dons(): HasMany
    {
        return $this->hasMany(Don::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    /**
     * Public URL of the logo, or null when none was uploaded.
     */
    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->logo ? Storage::disk('public')->url($this->logo) : null);
    }
}
