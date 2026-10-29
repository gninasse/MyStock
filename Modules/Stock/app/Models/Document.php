<?php

namespace Modules\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Core\Models\User;
use Modules\Referentiel\Models\Direction;
use Modules\Referentiel\Models\Magasin;
use Modules\Referentiel\Models\Service;
use Modules\Referentiel\Models\Unite;

class Document extends Model
{
    public const ENTREE = 'ENTREE';

    public const SORTIE = 'SORTIE';

    public const AJUSTEMENT = 'AJUSTEMENT';

    public const BROUILLON = 'BROUILLON';

    public const VALIDE = 'VALIDE';

    public const ANNULE = 'ANNULE';

    public const PREFIXES = [self::ENTREE => 'ENT', self::SORTIE => 'SOR', self::AJUSTEMENT => 'AJU'];

    protected $fillable = [
        'type', 'reference', 'date_mouvement', 'magasin_id', 'direction_id', 'service_id', 'unite_id',
        'commentaire', 'statut', 'user_id', 'validated_at', 'valide_par', 'document_annule_id',
    ];

    protected function casts(): array
    {
        return [
            'date_mouvement' => 'date',
            'validated_at' => 'datetime',
        ];
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneMouvement::class);
    }

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class);
    }

    public function direction(): BelongsTo
    {
        return $this->belongsTo(Direction::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function unite(): BelongsTo
    {
        return $this->belongsTo(Unite::class);
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function documentAnnule(): BelongsTo
    {
        return $this->belongsTo(self::class, 'document_annule_id');
    }

    public function annulation(): HasOne
    {
        return $this->hasOne(self::class, 'document_annule_id');
    }

    public function estBrouillon(): bool
    {
        return $this->statut === self::BROUILLON;
    }

    /**
     * Bénéficiaire final : l'Unité si elle est renseignée, sinon le Service (SFD 4.5).
     */
    public function getBeneficiaireAttribute(): ?string
    {
        return $this->unite?->libelle ?? $this->service?->libelle;
    }
}
