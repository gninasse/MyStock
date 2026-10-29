<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Referentiel\Models\Direction;
use Modules\Referentiel\Models\Magasin;
use Modules\Referentiel\Services\PerimetreMagasins;
use Modules\Stock\Services\MouvementService;

/**
 * Données d'appoint des écrans de saisie : articles fréquents avec stock,
 * stock disponible et arborescence des bénéficiaires.
 */
class SaisieController extends Controller
{
    public function __construct(protected MouvementService $mouvements, protected PerimetreMagasins $perimetre) {}

    public function articlesFrequents(Request $request, $magasinId)
    {
        $magasin = $this->magasinAccessible($request, $magasinId);
        $articles = $magasin->articlesFrequents()->where('articles.actif', true)
            ->get(['articles.id', 'code', 'designation', 'unite_mesure']);
        $stocks = $this->mouvements->stocksDisponibles($magasin->id, $articles->pluck('id')->all());

        return response()->json([
            'success' => true,
            'data' => $articles->map(fn ($a) => [
                'id' => $a->id,
                'code' => $a->code,
                'designation' => $a->designation,
                'unite_mesure' => $a->unite_mesure,
                'stock' => $stocks[$a->id],
            ]),
        ]);
    }

    public function stocks(Request $request, $magasinId)
    {
        $magasin = $this->magasinAccessible($request, $magasinId);
        $ids = array_map('intval', (array) $request->input('article_ids', []));

        return response()->json(['success' => true, 'data' => $this->mouvements->stocksDisponibles($magasin->id, $ids)]);
    }

    public function beneficiaires()
    {
        $directions = Direction::where('actif', true)->orderBy('libelle')
            ->with([
                'services' => fn ($q) => $q->where('actif', true)->orderBy('libelle'),
                'services.unites' => fn ($q) => $q->where('actif', true)->orderBy('libelle'),
            ])
            ->get(['id', 'code', 'libelle']);

        return response()->json(['success' => true, 'data' => $directions]);
    }

    protected function magasinAccessible(Request $request, $magasinId): Magasin
    {
        $magasin = Magasin::findOrFail($magasinId);
        abort_unless($this->perimetre->peutAcceder($request->user(), $magasin), 403, 'Vous n\'êtes pas rattaché à ce magasin.');

        return $magasin;
    }
}
