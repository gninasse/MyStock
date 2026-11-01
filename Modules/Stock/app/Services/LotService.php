<?php

namespace Modules\Stock\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Stock\Models\Document;
use Modules\Stock\Models\LigneMouvement;
use Modules\Stock\Models\Lot;

/**
 * Stock par lot. Une ligne qui augmente le stock crédite les lots de sa ventilation
 * (sinon la quantité reste « sans lot ») ; une ligne qui le diminue débite sa ventilation
 * si elle est imposée (annulation, retour de lots connus), sinon les lots en FEFO :
 * premier périmé, premier sorti, puis le stock sans lot.
 */
class LotService
{
    /**
     * Applique les lots d'un document dont le stock total vient d'être mis à jour.
     */
    public function appliquer(Document $document): void
    {
        // Même ordre que la mise à jour des stocks totaux, pour éviter les interblocages
        $lignes = $document->lignes()->with('lots.lot', 'article')->get()->sortBy('article_id');
        $erreurs = [];

        foreach ($lignes as $ligne) {
            if ($ligne->sens > 0) {
                foreach ($ligne->lots as $ventilation) {
                    $this->mouvementLot($document->magasin_id, $ventilation->lot_id, $ventilation->quantite);
                }

                continue;
            }

            if ($ligne->lots->isNotEmpty()) {
                foreach ($ligne->lots as $ventilation) {
                    if (! $this->mouvementLot($document->magasin_id, $ventilation->lot_id, -$ventilation->quantite)) {
                        $erreurs[] = "Stock du lot {$ventilation->lot->numero} insuffisant pour {$ligne->article->designation}.";
                    }
                }

                continue;
            }

            $this->sortieFefo($document->magasin_id, $ligne);
        }

        if ($erreurs) {
            throw ValidationException::withMessages(['lignes' => $erreurs]);
        }
    }

    /**
     * Lots disponibles d'articles dans un magasin, ordre FEFO :
     * [article_id => [['lot_id', 'numero', 'date_peremption', 'quantite'], …]]
     */
    public function lotsDisponibles(int $magasinId, array $articleIds): array
    {
        return $this->requeteFefo($magasinId, $articleIds)->get()
            ->groupBy('article_id')
            ->map(fn (Collection $lots) => $lots->map(fn ($l) => [
                'lot_id' => $l->lot_id,
                'numero' => $l->numero,
                'date_peremption' => $l->date_peremption,
                'quantite' => (int) $l->quantite,
            ])->values()->all())
            ->all();
    }

    /**
     * Lot d'un article, créé au besoin. Un numéro existant doit garder sa date de péremption.
     */
    public function lot(int $articleId, string $numero, ?string $datePeremption): Lot
    {
        $numero = mb_strtoupper(trim($numero));
        // createOrFirst : deux saisies simultanées d'un nouveau lot ne se heurtent pas à l'index unique
        $lot = Lot::createOrFirst(['article_id' => $articleId, 'numero' => $numero], ['date_peremption' => $datePeremption]);

        if ($datePeremption && $lot->date_peremption && $lot->date_peremption->toDateString() !== $datePeremption) {
            throw ValidationException::withMessages(['lignes' => [
                "Le lot {$numero} existe déjà avec la date de péremption {$lot->date_peremption->format('d/m/Y')}.",
            ]]);
        }

        if ($datePeremption && ! $lot->date_peremption) {
            $lot->update(['date_peremption' => $datePeremption]);
        }

        return $lot;
    }

    /**
     * Répartit $quantite sur une ventilation existante, dans l'ordre FEFO.
     * Renvoie [prise, reste] : deux ventilations [lot_id => quantité].
     */
    public function repartir(LigneMouvement $ligne, int $quantite): array
    {
        $prise = [];
        $reste = [];

        foreach ($ligne->lots()->with('lot')->get()->sortBy(fn ($v) => $v->lot->date_peremption?->toDateString() ?? '9999-12-31') as $ventilation) {
            $part = min($quantite, $ventilation->quantite);
            $quantite -= $part;
            if ($part > 0) {
                $prise[$ventilation->lot_id] = $part;
            }
            if ($ventilation->quantite - $part > 0) {
                $reste[$ventilation->lot_id] = $ventilation->quantite - $part;
            }
        }

        return [$prise, $reste];
    }

    /**
     * Enregistre la ventilation d'une ligne : [lot_id => quantité].
     */
    public function ventiler(LigneMouvement $ligne, array $ventilation): void
    {
        foreach ($ventilation as $lotId => $quantite) {
            if ($quantite > 0) {
                $ligne->lots()->create(['lot_id' => $lotId, 'quantite' => $quantite]);
            }
        }
    }

    protected function sortieFefo(int $magasinId, LigneMouvement $ligne): void
    {
        $restant = $ligne->quantite;

        // Pas de verrou ici : la ligne `stocks` (magasin, article) est déjà verrouillée par
        // MouvementService::appliquer, ce qui sérialise les mouvements de lots de cet article
        foreach ($this->requeteFefo($magasinId, [$ligne->article_id])->get() as $disponible) {
            if ($restant <= 0) {
                break;
            }
            $part = min($restant, (int) $disponible->quantite);
            $this->mouvementLot($magasinId, $disponible->lot_id, -$part);
            $ligne->lots()->create(['lot_id' => $disponible->lot_id, 'quantite' => $part]);
            $restant -= $part;
        }
        // Le reste éventuel sort du stock sans lot (le stock total a déjà été contrôlé)
    }

    protected function requeteFefo(int $magasinId, array $articleIds)
    {
        return DB::table('stocks_lots as s')
            ->join('lots as l', 'l.id', '=', 's.lot_id')
            ->where('s.magasin_id', $magasinId)
            ->whereIn('l.article_id', $articleIds)
            ->where('s.quantite', '>', 0)
            // Sans date de péremption en dernier
            ->orderByRaw('CASE WHEN l.date_peremption IS NULL THEN 1 ELSE 0 END')
            ->orderBy('l.date_peremption')
            ->orderBy('l.id')
            ->select('s.id', 's.lot_id', 's.quantite', 'l.article_id', 'l.numero', 'l.date_peremption');
    }

    /**
     * Ajoute $delta au stock du lot ; false si le stock du lot deviendrait négatif.
     */
    protected function mouvementLot(int $magasinId, int $lotId, int $delta): bool
    {
        DB::table('stocks_lots')->insertOrIgnore(['magasin_id' => $magasinId, 'lot_id' => $lotId, 'quantite' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $stock = DB::table('stocks_lots')->where('magasin_id', $magasinId)->where('lot_id', $lotId)->lockForUpdate()->first();

        if ($stock->quantite + $delta < 0) {
            return false;
        }

        DB::table('stocks_lots')->where('id', $stock->id)->update(['quantite' => $stock->quantite + $delta, 'updated_at' => now()]);

        return true;
    }
}
