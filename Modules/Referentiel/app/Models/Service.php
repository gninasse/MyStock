<?php

namespace Modules\Referentiel\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends ReferentielModel
{
    protected $fillable = ['code', 'libelle', 'direction_id', 'actif'];

    protected function casts(): array
    {
        return ['actif' => 'boolean'];
    }

    public function direction(): BelongsTo
    {
        return $this->belongsTo(Direction::class);
    }

    public function unites(): HasMany
    {
        return $this->hasMany(Unite::class);
    }
}
