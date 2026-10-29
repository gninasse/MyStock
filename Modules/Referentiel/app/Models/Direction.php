<?php

namespace Modules\Referentiel\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Direction extends ReferentielModel
{
    protected $fillable = ['code', 'libelle', 'actif'];

    protected function casts(): array
    {
        return ['actif' => 'boolean'];
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}
