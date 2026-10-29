<?php

namespace Modules\Etats\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Models\Activity;
use Modules\Etats\Services\AlerteStockService;
use Modules\Referentiel\Services\PerimetreMagasins;
use Modules\Stock\Models\Document;
use Modules\Stock\Models\Stock;

/**
 * Tableau de bord adapté au rôle (SFD 13.2).
 */
class TableauDeBordController extends Controller
{
    public function index(Request $request, PerimetreMagasins $perimetre, AlerteStockService $alertes)
    {
        $user = $request->user();
        $magasins = $perimetre->query($user)->select('id');

        $indicateurs = [
            'articles_en_stock' => Stock::whereIn('magasin_id', $magasins)->where('quantite', '>', 0)->distinct()->count('article_id'),
            'magasins' => $perimetre->query($user)->where('actif', true)->count(),
            'mouvements_du_jour' => Document::whereIn('magasin_id', $magasins)->whereDate('validated_at', today())->count(),
        ];

        return view('etats::tableau-de-bord', [
            'indicateurs' => $indicateurs,
            'alertes' => $user->can('etats.alertes') ? $alertes->alertes($user, 10) : null,
            'nombreAlertes' => $user->can('etats.alertes') ? $alertes->nombre($user) : 0,
            'derniersMouvements' => $user->can('stock.documents.index')
                ? Document::with('magasin:id,libelle', 'utilisateur:id,name,last_name')->withCount('lignes')
                    ->whereIn('magasin_id', $magasins)->whereNotNull('validated_at')
                    ->latest('validated_at')->limit(10)->get()
                : null,
            'dernieresActions' => $user->can('cores.activities.index')
                ? Activity::with('causer')->latest()->limit(5)->get()
                : null,
            'unitesMesure' => config('referentiel.unites_mesure'),
        ]);
    }
}
