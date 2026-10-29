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

    /**
     * Prochain code automatique : préfixe configurable + numéro suivant le plus grand existant.
     */
    public static function prochainCode(): string
    {
        $prefixe = config('referentiel.article_code_prefixe');
        $dernier = static::where('code', 'like', $prefixe.'%')->pluck('code')
            ->map(fn (string $code) => substr($code, strlen($prefixe)))
            ->filter(fn (string $suffixe) => ctype_digit($suffixe))
            ->map(fn (string $suffixe) => (int) $suffixe)
            ->max() ?? 0;

        return $prefixe.str_pad((string) ($dernier + 1), 3, '0', STR_PAD_LEFT);
    }

    public function scopeActifs(Builder $query): Builder
    {
        return $query->where('actif', true);
    }
}
