<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Referentiel\Models\Article;
use Modules\Referentiel\Models\Magasin;

class Stock extends Model
{
    protected $fillable = ['magasin_id', 'article_id', 'quantite'];

    protected function casts(): array
    {
        return ['quantite' => 'integer'];
    }

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
