<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreFrequentItem extends Model
{
    protected $fillable = ['store_id', 'article_id', 'sort_order'];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
