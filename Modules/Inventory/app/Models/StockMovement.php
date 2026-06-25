<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockMovement extends Model
{
    protected $fillable = [
        'reference', 'type', 'store_id', 'status', 'comment', 'user_id', 'validated_at',
    ];

    protected function casts(): array
    {
        return ['validated_at' => 'datetime'];
    }

    const TYPE_PREFIX = [
        'entry' => 'ENT',
        'exit' => 'SOR',
        'inventory' => 'INV',
    ];

    /** Génère une référence unique type ENT-20240115-003 */
    public static function generateReference(string $type): string
    {
        $prefix = self::TYPE_PREFIX[$type];
        $date = now()->format('Ymd');
        $count = static::whereDate('created_at', today())
            ->where('type', $type)
            ->count() + 1;

        return sprintf('%s-%s-%03d', $prefix, $date, $count);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockMovementLine::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isValidated(): bool
    {
        return $this->status === 'validated';
    }
}
