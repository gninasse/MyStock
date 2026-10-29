<?php

namespace Modules\Referentiel\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\User;

class Magasin extends ReferentielModel
{
    protected $fillable = ['code', 'libelle', 'localisation', 'actif'];

    protected function casts(): array
    {
        return ['actif' => 'boolean'];
    }

    public function responsables(): HasMany
    {
        return $this->hasMany(ResponsableMagasin::class);
    }

    public function responsablesActifs(): HasMany
    {
        return $this->responsables()->where('actif', true);
    }

    public function utilisateurs(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function articlesFrequents(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'articles_frequents')
            ->withPivot('ordre')
            ->withTimestamps()
            ->orderByPivot('ordre');
    }
}
