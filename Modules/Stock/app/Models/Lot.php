<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Referentiel\Models\Article;

class Lot extends Model
{
    protected $fillable = ['article_id', 'numero', 'date_peremption'];

    protected function casts(): array
    {
        return ['date_peremption' => 'date'];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * « L123 (exp. 31/12/2026) »
     */
    public function getLibelleAttribute(): string
    {
        return $this->numero.($this->date_peremption ? ' (exp. '.$this->date_peremption->format('d/m/Y').')' : '');
    }
}
