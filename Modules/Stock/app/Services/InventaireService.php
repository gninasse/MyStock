<?php

namespace Modules\Stock\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\User;
use Modules\Referentiel\Models\Magasin;
use Modules\Stock\Models\Inventaire;

/**
 * Inventaire physique d'un magasin (SFD 8 et 12.3) : l'écart est calculé
 * contre le stock du moment de la validation, puis un document d'ajustement
 * ramène le stock aux quantités comptées.
 */
class InventaireService
{
    public function __construct(protected MouvementService $mouvements) {}

    /**
     * $comptages = [article_id => quantité réelle comptée]
     */
    public function valider(Magasin $magasin, string $date, array $comptages, ?string $commentaire, User $user): Inventaire
    {
        $erreurs = [];

        if (! $magasin->actif) {
            $erreurs['magasin_id'][] = 'Le magasin sélectionné est désactivé. Veuillez en choisir un autre.';
        }

        if ($message = $this->mouvements->erreurDate($date)) {
            $erreurs['date_inventaire'][] = str_replace('du mouvement', 'de l\'inventaire', $message);
        }

        if (! $comptages) {
            $erreurs['comptages'][] = 'Aucun article à inventorier.';
        }

        if ($erreurs) {
            throw ValidationException::withMessages($erreurs);
        }

        return DB::transaction(function () use ($magasin, $date, $comptages, $commentaire, $user) {
            $inventaire = Inventaire::create([
                'magasin_id' => $magasin->id,
                'date_inventaire' => $date,
                'commentaire' => $commentaire,
                'user_id' => $user->id,
            ]);

            ksort($comptages);
            $ecarts = [];

            foreach ($comptages as $articleId => $reelle) {
                $theorique = $this->mouvements->stockVerrouille($magasin->id, $articleId)->quantite;
                $ecart = $reelle - $theorique;

                $inventaire->lignes()->create([
                    'article_id' => $articleId,
                    'stock_theorique' => $theorique,
                    'quantite_reelle' => $reelle,
                    'ecart' => $ecart,
                ]);

                if ($ecart !== 0) {
                    $ecarts[$articleId] = $ecart;
                }
            }

            if ($ecarts && blank($commentaire)) {
                throw ValidationException::withMessages([
                    'commentaire' => 'Un commentaire est obligatoire pour justifier les écarts constatés.',
                ]);
            }

            if ($ecarts) {
                $libelle = 'Ajustement inventaire du '.$inventaire->date_inventaire->format('d/m/Y').' — '.$magasin->libelle;
                $document = $this->mouvements->creerAjustement($magasin->id, $date, $ecarts, $libelle.' — '.$commentaire, $user);
                $inventaire->update(['document_id' => $document->id]);
            }

            activity('stock')
                ->performedOn($inventaire)
                ->causedBy($user)
                ->withProperties([
                    'magasin_id' => $magasin->id,
                    'articles' => count($comptages),
                    'ecarts' => $ecarts,
                    'reference_ajustement' => $inventaire->document?->reference,
                ])
                ->tap(function ($activity) {
                    $activity->module = 'stock';
                    $activity->ip_address = request()->ip();
                    $activity->user_agent = request()->userAgent();
                })
                ->log('inventaire_valide');

            return $inventaire->load('lignes', 'document');
        });
    }

    /**
     * Articles à inventorier : tous les articles actifs, plus les inactifs
     * qui ont encore du stock, avec leur stock théorique.
     */
    public function articlesAInventorier(Magasin $magasin): array
    {
        return DB::table('articles')
            ->leftJoin('stocks', fn ($j) => $j->on('stocks.article_id', '=', 'articles.id')->where('stocks.magasin_id', $magasin->id))
            ->where(fn ($q) => $q->where('articles.actif', true)->orWhere('stocks.quantite', '!=', 0))
            ->orderBy('articles.code')
            ->get([
                'articles.id', 'articles.code', 'articles.designation', 'articles.unite_mesure', 'articles.categorie_id',
                DB::raw('COALESCE(stocks.quantite, 0) as stock_theorique'),
            ])
            ->map(fn ($a) => array_merge((array) $a, ['stock_theorique' => (int) $a->stock_theorique]))
            ->all();
    }
}
