<?php

namespace Modules\Stock\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\User;
use Modules\Referentiel\Services\Parametres;
use Modules\Stock\Models\Document;
use Modules\Stock\Models\Stock;

/**
 * Cycle de vie des documents de stock (SFD 7) : brouillon, validation, annulation.
 * Le stock par magasin n'évolue qu'à la validation.
 */
class MouvementService
{
    public function __construct(protected LotService $lots) {}

    /**
     * Crée ou met à jour un brouillon. $donnees['lignes'] : [['article_id' => …, 'quantite' => …], …]
     * Un même article saisi plusieurs fois est regroupé et ses quantités additionnées.
     */
    public function enregistrerBrouillon(string $type, array $donnees, User $user, ?Document $document = null): Document
    {
        return DB::transaction(function () use ($type, $donnees, $user, $document) {
            // Verrou puis contrôle : le document a pu être validé ou expédié entre-temps
            if ($document) {
                $document = Document::whereKey($document->id)->lockForUpdate()->firstOrFail();
                if (! $document->estBrouillon()) {
                    throw ValidationException::withMessages(['document' => 'Seul un brouillon peut être modifié.']);
                }
            }

            $entete = [
                'date_mouvement' => $donnees['date_mouvement'],
                'magasin_id' => $donnees['magasin_id'],
                'commentaire' => $donnees['commentaire'] ?? null,
                'magasin_destination_id' => $type === Document::TRANSFERT ? ($donnees['magasin_destination_id'] ?? null) : null,
                // Retour : transfert d'origine (contrôlé à l'expédition)
                'document_origine_id' => $type === Document::TRANSFERT ? ($donnees['document_origine_id'] ?? null) : null,
                'direction_id' => $type === Document::SORTIE ? ($donnees['direction_id'] ?? null) : null,
                'service_id' => $type === Document::SORTIE ? ($donnees['service_id'] ?? null) : null,
                'unite_id' => $type === Document::SORTIE ? ($donnees['unite_id'] ?? null) : null,
            ];

            if ($document) {
                $document->update($entete);
                $document->lignes()->delete();
            } else {
                $document = Document::create($entete + [
                    'type' => $type,
                    'statut' => Document::BROUILLON,
                    'user_id' => $user->id,
                ]);
            }

            $sens = $type === Document::ENTREE ? 1 : -1;
            collect($donnees['lignes'] ?? [])
                ->groupBy('article_id')
                ->each(function ($lignes, $articleId) use ($document, $sens, $type) {
                    $ligne = $document->lignes()->create([
                        'article_id' => $articleId,
                        'quantite' => $lignes->sum('quantite'),
                        'sens' => $sens,
                    ]);

                    // Entrée : numéro de lot et date de péremption saisis par ligne
                    if ($type === Document::ENTREE) {
                        $ventilation = [];
                        foreach ($lignes->filter(fn ($l) => filled($l['lot_numero'] ?? null)) as $l) {
                            $lot = $this->lots->lot($articleId, $l['lot_numero'], $l['date_peremption'] ?? null);
                            $ventilation[$lot->id] = ($ventilation[$lot->id] ?? 0) + (int) $l['quantite'];
                        }
                        $this->lots->ventiler($ligne, $ventilation);
                    }
                });

            $this->journaliser($document, $document->wasRecentlyCreated ? 'brouillon_cree' : 'brouillon_modifie', $user);

            return $document->load('lignes');
        });
    }

    public function supprimerBrouillon(Document $document, User $user): void
    {
        DB::transaction(function () use ($document, $user) {
            $document = Document::whereKey($document->id)->lockForUpdate()->firstOrFail();
            if (! $document->estBrouillon()) {
                throw ValidationException::withMessages(['document' => 'Seul un brouillon peut être supprimé.']);
            }

            $this->journaliser($document, 'brouillon_supprime', $user);
            $document->delete();
        });
    }

    /**
     * Contrôles de la SFD 7.3.2 / 7.4.2 / 11.1, puis numérotation et mise à jour du stock.
     */
    public function valider(Document $document, User $user): Document
    {
        return DB::transaction(function () use ($document, $user) {
            $document = Document::whereKey($document->id)->lockForUpdate()->firstOrFail();

            if (! $document->estBrouillon()) {
                throw ValidationException::withMessages(['document' => 'Ce document a déjà été validé.']);
            }

            if ($document->type === Document::TRANSFERT) {
                throw ValidationException::withMessages(['document' => 'Un transfert s\'expédie, il ne se valide pas directement.']);
            }

            $this->controler($document);
            $this->appliquer($document, $user);
            $this->journaliser($document, 'document_valide', $user);

            return $document;
        });
    }

    /**
     * Annulation d'un mouvement validé par un mouvement inverse (SFD 7.6).
     */
    public function annuler(Document $document, User $user): Document
    {
        return DB::transaction(function () use ($document, $user) {
            $document = Document::whereKey($document->id)->lockForUpdate()->firstOrFail();

            if ($document->statut !== Document::VALIDE || ! in_array($document->type, [Document::ENTREE, Document::SORTIE], true)) {
                throw ValidationException::withMessages(['document' => 'Seule une entrée ou une sortie validée peut être annulée.']);
            }

            if ($document->document_annule_id) {
                throw ValidationException::withMessages(['document' => 'Un mouvement d\'annulation ne peut pas être annulé.']);
            }

            $typeInverse = $document->type === Document::ENTREE ? Document::SORTIE : Document::ENTREE;
            $inverse = Document::create([
                'type' => $typeInverse,
                'date_mouvement' => now()->toDateString(),
                'magasin_id' => $document->magasin_id,
                'commentaire' => "Annulation du mouvement {$document->reference}",
                'statut' => Document::BROUILLON,
                'user_id' => $user->id,
                'document_annule_id' => $document->id,
            ]);

            foreach ($document->lignes()->with('lots')->get() as $ligne) {
                $ligneInverse = $inverse->lignes()->create([
                    'article_id' => $ligne->article_id,
                    'quantite' => $ligne->quantite,
                    'sens' => -$ligne->sens,
                ]);
                // Mêmes lots que le mouvement annulé
                $this->lots->ventiler($ligneInverse, $ligne->lots->pluck('quantite', 'lot_id')->all());
            }

            $this->appliquer($inverse, $user);
            $document->update(['statut' => Document::ANNULE]);
            $this->journaliser($document, 'document_annule', $user, ['annule_par_document' => $inverse->reference]);

            return $inverse;
        });
    }

    /**
     * Document d'ajustement validé immédiatement (SFD 8.3) : $ecarts = [article_id => écart non nul].
     * À appeler dans une transaction où les stocks concernés sont déjà verrouillés.
     */
    public function creerAjustement(int $magasinId, string $date, array $ecarts, string $commentaire, User $user): Document
    {
        $document = Document::create([
            'type' => Document::AJUSTEMENT,
            'date_mouvement' => $date,
            'magasin_id' => $magasinId,
            'commentaire' => mb_substr($commentaire, 0, 500),
            'statut' => Document::BROUILLON,
            'user_id' => $user->id,
        ]);

        foreach ($ecarts as $articleId => $ecart) {
            $document->lignes()->create([
                'article_id' => $articleId,
                'quantite' => abs($ecart),
                'sens' => $ecart > 0 ? 1 : -1,
            ]);
        }

        $this->appliquer($document, $user);
        $this->journaliser($document, 'document_valide', $user);

        return $document;
    }

    /**
     * Stock disponible par article dans un magasin : [article_id => quantité].
     */
    public function stocksDisponibles(int $magasinId, array $articleIds): array
    {
        $stocks = Stock::where('magasin_id', $magasinId)->whereIn('article_id', $articleIds)->pluck('quantite', 'article_id');

        return collect($articleIds)->mapWithKeys(fn ($id) => [$id => (int) ($stocks[$id] ?? 0)])->all();
    }

    public function controler(Document $document): void
    {
        $document->load('magasin', 'magasinDestination', 'lignes.article');
        $erreurs = [];

        if ($document->lignes->where('quantite', '>', 0)->isEmpty()) {
            $erreurs['lignes'][] = 'Veuillez ajouter au moins un article avec une quantité supérieure à zéro.';
        }

        if (! $document->magasin->actif) {
            $erreurs['magasin_id'][] = 'Le magasin sélectionné est désactivé. Veuillez en choisir un autre.';
        }

        if ($message = $this->erreurDate($document->date_mouvement->toDateString())) {
            $erreurs['date_mouvement'][] = $message;
        }

        if ($document->type === Document::SORTIE && (! $document->direction_id || ! $document->service_id)) {
            $erreurs['service_id'][] = 'Veuillez sélectionner une Direction et un Service.';
        }

        if ($document->type === Document::TRANSFERT) {
            if (! $document->magasinDestination) {
                $erreurs['magasin_destination_id'][] = 'Veuillez sélectionner le magasin destinataire.';
            } elseif ($document->magasin_destination_id === $document->magasin_id) {
                $erreurs['magasin_destination_id'][] = 'Le magasin destinataire doit être différent du magasin source.';
            } elseif (! $document->magasinDestination->actif) {
                $erreurs['magasin_destination_id'][] = 'Le magasin destinataire est désactivé.';
            }
        }

        foreach ($document->lignes as $ligne) {
            if (! $ligne->article->actif) {
                $erreurs['lignes'][] = "L'article {$ligne->article->designation} est désactivé et ne peut pas faire l'objet d'un mouvement.";
            }
        }

        if ($erreurs) {
            throw ValidationException::withMessages($erreurs);
        }
    }

    /**
     * Message d'erreur si la date est future ou trop ancienne, null sinon.
     */
    public function erreurDate(string $date): ?string
    {
        if ($date > now()->toDateString()) {
            return 'La date du mouvement ne peut pas être dans le futur.';
        }

        $jours = Parametres::get('mouvement_jours_retroactifs');
        if ($date < now()->subDays($jours)->toDateString()) {
            return "La date du mouvement ne peut pas être antérieure à {$jours} jours.";
        }

        return null;
    }

    /**
     * Attribue la référence, met à jour le stock et passe le document au statut donné
     * (VALIDE, ou EN_TRANSIT pour un transfert expédié).
     */
    public function appliquer(Document $document, User $user, string $statut = Document::VALIDE): void
    {
        $lignes = $document->lignes()->with('article')->get()->sortBy('article_id');
        $insuffisants = [];

        foreach ($lignes as $ligne) {
            $stock = $this->stockVerrouille($document->magasin_id, $ligne->article_id);
            $nouveau = $stock->quantite + $ligne->sens * $ligne->quantite;

            if ($nouveau < 0) {
                $insuffisants[] = "Stock insuffisant pour {$ligne->article->designation}. Disponible : {$stock->quantite}, Demandé : {$ligne->quantite}.";

                continue;
            }

            $stock->update(['quantite' => $nouveau]);
        }

        if ($insuffisants) {
            throw ValidationException::withMessages(['lignes' => $insuffisants]);
        }

        $this->lots->appliquer($document);

        $document->update([
            'reference' => $this->genererReference($document),
            'statut' => $statut,
            'validated_at' => now(),
            'valide_par' => $user->id,
        ]);
    }

    public function stockVerrouille(int $magasinId, int $articleId): Stock
    {
        Stock::insertOrIgnore([
            'magasin_id' => $magasinId,
            'article_id' => $articleId,
            'quantite' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Stock::where('magasin_id', $magasinId)->where('article_id', $articleId)->lockForUpdate()->firstOrFail();
    }

    /**
     * Format PREFIXE-AAAAMMJJ-NNN, compteur séquentiel par type et par jour du mouvement (SFD 12.2).
     */
    protected function genererReference(Document $document): string
    {
        $prefixe = Document::PREFIXES[$document->type];
        $jour = $document->date_mouvement->toDateString();

        DB::table('compteurs_reference')->insertOrIgnore(['prefixe' => $prefixe, 'jour' => $jour, 'dernier' => 0]);
        $compteur = DB::table('compteurs_reference')->where('prefixe', $prefixe)->where('jour', $jour)->lockForUpdate()->first();
        $numero = $compteur->dernier + 1;
        DB::table('compteurs_reference')->where('id', $compteur->id)->update(['dernier' => $numero]);

        return sprintf('%s-%s-%03d', $prefixe, $document->date_mouvement->format('Ymd'), $numero);
    }

    public function journaliser(Document $document, string $evenement, User $user, array $proprietes = []): void
    {
        activity('stock')
            ->performedOn($document)
            ->causedBy($user)
            ->withProperties($proprietes + [
                'type' => $document->type,
                'reference' => $document->reference,
                'magasin_id' => $document->magasin_id,
                'lignes' => $document->lignes()->get(['article_id', 'quantite'])->toArray(),
            ])
            ->tap(function ($activity) {
                $activity->module = 'stock';
                $activity->ip_address = request()->ip();
                $activity->user_agent = request()->userAgent();
            })
            ->log($evenement);
    }
}
