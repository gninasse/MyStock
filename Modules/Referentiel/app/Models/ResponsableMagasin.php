<?php

namespace Modules\Referentiel\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

class ResponsableMagasin extends ReferentielModel
{
    public const PRINCIPAL = 'PRINCIPAL';

    public const ADJOINT = 'ADJOINT';

    protected $table = 'responsables_magasin';

    protected $fillable = ['magasin_id', 'user_id', 'role_responsable', 'date_debut', 'date_fin', 'actif', 'cree_par'];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'actif' => 'boolean',
        ];
    }

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
