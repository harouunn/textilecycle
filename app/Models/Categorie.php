<?php

namespace App\Models;

use Database\Factories\CategorieFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categorie extends Model
{
    /** @use HasFactory<CategorieFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nom',
        'description',
        'icone',
    ];

    /**
     * @return HasMany<Vetement, $this>
     */
    public function vetements(): HasMany
    {
        return $this->hasMany(Vetement::class);
    }
}
