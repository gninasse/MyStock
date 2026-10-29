<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\User;
use Modules\Referentiel\Models\Magasin;

class Inventaire extends Model
{
    protected $fillable = ['magasin_id', 'date_inventaire', 'commentaire', 'user_id', 'document_id'];

    protected function casts(): array
    {
        return ['date_inventaire' => 'date'];
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneInventaire::class);
    }

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class);
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
