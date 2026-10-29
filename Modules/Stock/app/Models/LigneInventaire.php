<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Referentiel\Models\Article;

class LigneInventaire extends Model
{
    public $timestamps = false;

    protected $table = 'lignes_inventaire';

    protected $fillable = ['inventaire_id', 'article_id', 'stock_theorique', 'quantite_reelle', 'ecart'];

    protected function casts(): array
    {
        return ['stock_theorique' => 'integer', 'quantite_reelle' => 'integer', 'ecart' => 'integer'];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
