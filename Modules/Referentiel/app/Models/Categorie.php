<?php

namespace Modules\Referentiel\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Categorie extends ReferentielModel
{
    protected $table = 'categories';

    protected $fillable = ['code', 'libelle', 'unite_par_defaut'];

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
