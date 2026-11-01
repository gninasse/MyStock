<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ventilation d'une ligne de mouvement par lot.
 */
class LigneLot extends Model
{
    public $timestamps = false;

    protected $table = 'lignes_lots';

    protected $fillable = ['ligne_mouvement_id', 'lot_id', 'quantite'];

    protected function casts(): array
    {
        return ['quantite' => 'integer'];
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }
}
