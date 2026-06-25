<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    protected $fillable = [
        'code', 'designation', 'category_id',
        'unit', 'description', 'min_stock', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'min_stock' => 'integer'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function movementLines(): HasMany
    {
        return $this->hasMany(StockMovementLine::class);
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    /** Stock disponible dans un magasin précis */
    public function stockInStore(int $storeId): float
    {
        return $this->stockBalances()
            ->where('store_id', $storeId)
            ->value('quantity') ?? 0;
    }

    /** Scope pour autocomplete */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('code', 'LIKE', "%{$term}%")
                ->orWhere('designation', 'LIKE', "%{$term}%");
        });
    }
}
