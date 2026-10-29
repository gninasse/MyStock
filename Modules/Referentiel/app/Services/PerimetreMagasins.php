<?php

namespace Modules\Referentiel\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Models\User;
use Modules\Referentiel\Models\Magasin;

/**
 * Magasins visibles par un utilisateur (SFD 3.4.3 et 3.4.4) :
 * tous pour ceux qui ont referentiel.magasins.tous, sinon les magasins
 * rattachés et ceux dont il est responsable actif.
 */
class PerimetreMagasins
{
    public function voitTous(User $user): bool
    {
        return $user->can('referentiel.magasins.tous');
    }

    public function query(User $user): Builder
    {
        $query = Magasin::query();

        if ($this->voitTous($user)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->whereHas('utilisateurs', fn (Builder $u) => $u->whereKey($user->id))
                ->orWhereHas('responsablesActifs', fn (Builder $r) => $r->where('user_id', $user->id));
        });
    }

    public function peutAcceder(User $user, Magasin $magasin): bool
    {
        return $this->query($user)->whereKey($magasin->id)->exists();
    }
}
