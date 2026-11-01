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

    public const TRANSFERT = 'TRANSFERT';

    public const RECEPTION = 'RECEPTION';

    public const REINTEGRATION = 'REINTEGRATION';

    public const BROUILLON = 'BROUILLON';

    public const EN_TRANSIT = 'EN_TRANSIT';

    public const VALIDE = 'VALIDE';

    public const ANNULE = 'ANNULE';

    public const PREFIXES = [
        self::ENTREE => 'ENT', self::SORTIE => 'SOR', self::AJUSTEMENT => 'AJU',
        self::TRANSFERT => 'TRF', self::RECEPTION => 'REC', self::REINTEGRATION => 'RIN',
    ];

    public const LIBELLES = [
        self::ENTREE => 'Entrée', self::SORTIE => 'Sortie', self::AJUSTEMENT => 'Ajustement',
        self::TRANSFERT => 'Transfert', self::RECEPTION => 'Réception de transfert', self::REINTEGRATION => 'Réintégration',
    ];

    public const STATUTS = [
        self::BROUILLON => ['Brouillon', 'secondary'],
        self::EN_TRANSIT => ['En transit', 'warning'],
        self::VALIDE => ['Validé', 'primary'],
        self::ANNULE => ['Annulé', 'dark'],
    ];

    protected $fillable = [
        'type', 'reference', 'date_mouvement', 'magasin_id', 'magasin_destination_id', 'direction_id', 'service_id', 'unite_id',
        'commentaire', 'motif_ecart', 'statut', 'user_id', 'validated_at', 'valide_par', 'recu_at', 'recu_par',
        'document_annule_id', 'document_origine_id',
    ];

    protected function casts(): array
    {
        return [
            'date_mouvement' => 'date',
            'validated_at' => 'datetime',
            'recu_at' => 'datetime',
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

    public function magasinDestination(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_destination_id');
    }

    public function recepteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recu_par');
    }

    /**
     * Transfert d'origine d'une réception, d'une réintégration ou d'un retour.
     */
    public function documentOrigine(): BelongsTo
    {
        return $this->belongsTo(self::class, 'document_origine_id');
    }

    public function documentsLies(): HasMany
    {
        return $this->hasMany(self::class, 'document_origine_id');
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

    public function inventaire(): HasOne
    {
        return $this->hasOne(Inventaire::class);
    }

    public function getLibelleTypeAttribute(): string
    {
        return self::LIBELLES[$this->type] ?? $this->type;
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
