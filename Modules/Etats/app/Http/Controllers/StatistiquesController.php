<?php

namespace Modules\Etats\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Core\Services\PdfService;
use Modules\Etats\Services\EtatsService;
use Modules\Etats\Services\ExportService;
use Modules\Etats\Services\StatistiquesService;
use Modules\Etats\Support\Etat;

/**
 * Page de pilotage : indicateurs, graphiques et rapport d'activité imprimable.
 */
class StatistiquesController extends Controller
{
    public function __construct(protected StatistiquesService $statistiques) {}

    public function index(Request $request, EtatsService $etats)
    {
        $filtres = $this->filtres($request);

        return view('etats::statistiques', [
            'stats' => $this->statistiques->tableau($filtres, $request->user()),
            'filtres' => $filtres,
            'periodes' => StatistiquesService::PERIODES,
            'magasins' => $etats->magasinsVisibles($request->user()),
        ]);
    }

    /**
     * Rapport d'activité PDF (aperçu en modale) reprenant les indicateurs et les classements.
     */
    public function rapport(Request $request, ExportService $exports)
    {
        abort_unless($request->user()->can('etats.export'), 403);
        $filtres = $this->filtres($request);
        $stats = $this->statistiques->tableau($filtres, $request->user());
        $exports->journaliser(new Etat('Rapport d\'activité', ['Période' => $stats['periode']['libelle']], []), 'PDF');

        return app(PdfService::class)->rendre('etats::pdf-rapport', ['stats' => $stats],
            'rapport-activite-'.now()->format('Ymd').'.pdf');
    }

    protected function filtres(Request $request): array
    {
        return $request->validate([
            'periode' => ['nullable', Rule::in(array_keys(StatistiquesService::PERIODES))],
            'magasin_id' => ['nullable', 'integer'],
        ]);
    }
}
