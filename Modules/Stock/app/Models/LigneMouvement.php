<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Referentiel\Models\Article;

class LigneMouvement extends Model
{
    protected $table = 'lignes_mouvement';

    protected $fillable = ['document_id', 'article_id', 'quantite', 'sens'];

    protected function casts(): array
    {
        return ['quantite' => 'integer', 'sens' => 'integer'];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
