<?php

namespace Modules\Referentiel\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Unite extends ReferentielModel
{
    protected $fillable = ['code', 'libelle', 'service_id', 'actif'];

    protected function casts(): array
    {
        return ['actif' => 'boolean'];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
