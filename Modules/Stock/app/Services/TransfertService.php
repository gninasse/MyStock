<?php

namespace Modules\Stock\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\User;
use Modules\Referentiel\Services\Parametres;
use Modules\Referentiel\Services\PerimetreMagasins;
use Modules\Stock\Models\Document;

/**
 * Transferts entre magasins : un transfert est toujours un couple d'opérations,
 * sortie du magasin source (expédition) puis entrée dans le magasin destinataire
 * (réception). Entre les deux, la marchandise est « en transit ».
 */
class TransfertService
{
    public function __construct(protected MouvementService $mouvements, protected PerimetreMagasins $perimetre) {}

    /**
     * Expédie un brouillon de transfert : débite la source, puis réceptionne
     * immédiatement si le paramètre « transfert_mode » le prévoit pour cet utilisateur.
     */
    public function expedier(Document $document, User $user): Document
    {
        return DB::transaction(function () use ($document, $user) {
            $document = Document::whereKey($document->id)->lockForUpdate()->firstOrFail();

            if ($document->type !== Document::TRANSFERT || ! $document->estBrouillon()) {
                throw ValidationException::withMessages(['document' => 'Seul un brouillon de transfert peut être expédié.']);
            }

            $this->mouvements->controler($document);
            $this->mouvements->appliquer($document, $user, Document::EN_TRANSIT);
            $this->mouvements->journaliser($document, 'transfert_expedie', $user, ['magasin_destination_id' => $document->magasin_destination_id]);

            if ($this->receptionImmediate($document, $user)) {
                $quantites = $document->lignes()->pluck('quantite', 'article_id')->all();
                $this->receptionner($document, $quantites, null, $user);
            }

            return $document->refresh();
        });
    }

    /**
     * Une étape (réception automatique) ou deux étapes, selon le paramétrage.
     */
    public function receptionImmediate(Document $transfert, User $user): bool
    {
        return match (Parametres::get('transfert_mode')) {
            'UNE_ETAPE' => true,
            'UNE_ETAPE_SI_ACCES' => $user->can('stock.transferts.receptionner')
                && $this->perimetre->peutAcceder($user, $transfert->magasinDestination),
            default => false,
        };
    }

    /**
     * Réception d'un transfert en transit. $quantitesRecues : [article_id => quantité reçue].
     * Tout écart (réception partielle ou refus) exige un motif ; la quantité non reçue
     * est réintégrée à la source ou comptée comme perte selon « transfert_ecart ».
     */
    public function receptionner(Document $transfert, array $quantitesRecues, ?string $motif, User $user): Document
    {
        return DB::transaction(function () use ($transfert, $quantitesRecues, $motif, $user) {
            $transfert = Document::whereKey($transfert->id)->lockForUpdate()->firstOrFail();

            if ($transfert->type !== Document::TRANSFERT || $transfert->statut !== Document::EN_TRANSIT) {
                throw ValidationException::withMessages(['document' => 'Ce transfert n\'est pas en attente de réception.']);
            }

            $lignes = $transfert->lignes()->with('article')->get();
            $recues = [];
            $ecarts = [];
            $erreurs = [];

            foreach ($lignes as $ligne) {
                $recue = (int) ($quantitesRecues[$ligne->article_id] ?? $ligne->quantite);
                if ($recue < 0 || $recue > $ligne->quantite) {
                    $erreurs[] = "La quantité reçue pour {$ligne->article->designation} doit être comprise entre 0 et {$ligne->quantite}.";

                    continue;
                }
                $recues[$ligne->id] = $recue;
                if ($recue < $ligne->quantite) {
                    $ecarts[$ligne->article_id] = $ligne->quantite - $recue;
                }
            }

            $motif = trim((string) $motif);
            if ($ecarts && $motif === '') {
                $erreurs[] = 'Veuillez indiquer le motif de l\'écart entre les quantités expédiées et reçues.';
            }

            if ($erreurs) {
                throw ValidationException::withMessages(['lignes' => $erreurs]);
            }

            foreach ($lignes as $ligne) {
                $ligne->update(['quantite_recue' => $recues[$ligne->id]]);
            }

            $credits = $lignes->filter(fn ($l) => $recues[$l->id] > 0)->mapWithKeys(fn ($l) => [$l->article_id => $recues[$l->id]])->all();
            if ($credits) {
                $this->creerMouvementLie($transfert, Document::RECEPTION, $transfert->magasin_destination_id, $credits,
                    "Réception du transfert {$transfert->reference}", $user);
            }

            if ($ecarts && Parametres::get('transfert_ecart') === 'RETOUR_SOURCE') {
                $this->creerMouvementLie($transfert, Document::REINTEGRATION, $transfert->magasin_id, $ecarts,
                    "Quantité non reçue du transfert {$transfert->reference}", $user);
            }

            $transfert->update([
                'statut' => Document::VALIDE,
                'motif_ecart' => $ecarts ? mb_substr($motif, 0, 500) : null,
                'recu_at' => now(),
                'recu_par' => $user->id,
            ]);
            $this->mouvements->journaliser($transfert, 'transfert_receptionne', $user, ['ecarts' => $ecarts, 'motif' => $ecarts ? $motif : null]);

            return $transfert;
        });
    }

    /**
     * Annulation avant réception : toute la marchandise en transit est réintégrée à la source.
     */
    public function annuler(Document $transfert, User $user): Document
    {
        return DB::transaction(function () use ($transfert, $user) {
            $transfert = Document::whereKey($transfert->id)->lockForUpdate()->firstOrFail();

            if ($transfert->type !== Document::TRANSFERT || $transfert->statut !== Document::EN_TRANSIT) {
                throw ValidationException::withMessages(['document' => 'Seul un transfert en transit (pas encore reçu) peut être annulé.']);
            }

            $quantites = $transfert->lignes()->pluck('quantite', 'article_id')->all();
            $transfert->lignes()->update(['quantite_recue' => 0]);
            $reintegration = $this->creerMouvementLie($transfert, Document::REINTEGRATION, $transfert->magasin_id, $quantites,
                "Annulation du transfert {$transfert->reference}", $user);

            $transfert->update(['statut' => Document::ANNULE]);
            $this->mouvements->journaliser($transfert, 'transfert_annule', $user, ['reintegre_par' => $reintegration->reference]);

            return $reintegration;
        });
    }

    /**
     * Document validé immédiatement, rattaché au transfert : [article_id => quantité] ajoutées au magasin.
     */
    protected function creerMouvementLie(Document $transfert, string $type, int $magasinId, array $quantites, string $commentaire, User $user): Document
    {
        $document = Document::create([
            'type' => $type,
            'date_mouvement' => now()->toDateString(),
            'magasin_id' => $magasinId,
            'commentaire' => $commentaire,
            'statut' => Document::BROUILLON,
            'user_id' => $user->id,
            'document_origine_id' => $transfert->id,
        ]);

        foreach ($quantites as $articleId => $quantite) {
            $document->lignes()->create(['article_id' => $articleId, 'quantite' => $quantite, 'sens' => 1]);
        }

        $this->mouvements->appliquer($document, $user);
        $this->mouvements->journaliser($document, 'document_valide', $user);

        return $document;
    }
}
