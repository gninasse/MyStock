<?php

namespace Modules\Referentiel\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

class Article extends ReferentielModel
{
    protected $fillable = ['code', 'designation', 'unite_mesure', 'categorie_id', 'stock_minimum', 'actif', 'cree_par'];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
            'stock_minimum' => 'integer',
        ];
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class);
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cree_par');
    }

    public function scopeActifs(Builder $query): Builder
    {
        return $query->where('actif', true);
    }
}
