<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\PdfService;
use Modules\Referentiel\Models\Categorie;
use Modules\Referentiel\Models\Magasin;
use Modules\Referentiel\Services\Parametres;
use Modules\Referentiel\Services\PerimetreMagasins;
use Modules\Stock\Models\Inventaire;
use Modules\Stock\Services\InventaireService;

class InventaireController extends Controller
{
    public function __construct(protected InventaireService $inventaires, protected PerimetreMagasins $perimetre) {}

    public function index()
    {
        return view('stock::inventaires.index');
    }

    public function getData(Request $request)
    {
        $query = Inventaire::query()
            ->with(['magasin:id,libelle', 'utilisateur:id,name,last_name', 'document:id,reference'])
            ->withCount([
                'lignes',
                'lignes as ecarts_count' => fn ($q) => $q->where('ecart', '!=', 0),
            ])
            ->whereIn('magasin_id', $this->perimetre->query($request->user())->select('id'));

        $total = (clone $query)->count();
        $rows = $query->orderByDesc('date_inventaire')->orderByDesc('id')
            ->offset((int) $request->get('offset', 0))
            ->limit(min((int) $request->get('limit', 25), 100))
            ->get()
            ->map(fn (Inventaire $i) => [
                'id' => $i->id,
                'date_inventaire' => $i->date_inventaire->format('d/m/Y'),
                'magasin' => $i->magasin->libelle,
                'lignes_count' => $i->lignes_count,
                'ecarts_count' => $i->ecarts_count,
                'reference_ajustement' => $i->document?->reference,
                'utilisateur' => $i->utilisateur->full_name,
            ]);

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    public function create(Request $request)
    {
        return view('stock::inventaires.saisie', [
            'magasins' => $this->perimetre->query($request->user())->where('actif', true)->orderBy('libelle')->get(),
            'categories' => Categorie::orderBy('libelle')->get(),
            'unitesMesure' => config('referentiel.unites_mesure'),
        ]);
    }

    public function articles(Request $request, $magasinId)
    {
        $magasin = $this->magasinAccessible($request, $magasinId);

        return response()->json(['success' => true, 'data' => $this->inventaires->articlesAInventorier($magasin)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'magasin_id' => ['required', 'integer', 'exists:magasins,id'],
            'date_inventaire' => ['required', 'date_format:Y-m-d'],
            'commentaire' => ['nullable', 'string', 'max:500'],
            'comptages' => ['nullable', 'array'],
            'comptages.*.article_id' => ['required', 'integer', 'distinct', 'exists:articles,id'],
            'comptages.*.quantite_reelle' => ['nullable', 'integer', 'min:0'],
        ], [
            'magasin_id.required' => 'Veuillez sélectionner un magasin.',
            'comptages.*.quantite_reelle.min' => 'La quantité réelle ne peut pas être négative.',
        ]);

        $magasin = $this->magasinAccessible($request, $data['magasin_id']);

        // Champ vide = 0 (SFD 12.3)
        $comptages = collect($data['comptages'] ?? [])
            ->mapWithKeys(fn ($c) => [(int) $c['article_id'] => (int) ($c['quantite_reelle'] ?? 0)])
            ->all();

        $inventaire = $this->inventaires->valider($magasin, $data['date_inventaire'], $comptages, $data['commentaire'] ?? null, $request->user());
        $nbAjustements = $inventaire->lignes->where('ecart', '!=', 0)->count();

        return response()->json([
            'success' => true,
            'message' => "Inventaire du {$inventaire->date_inventaire->format('d/m/Y')} validé. {$nbAjustements} ajustement(s) généré(s).",
            'data' => ['id' => $inventaire->id, 'reference_ajustement' => $inventaire->document?->reference],
        ]);
    }

    public function show(Request $request, $id)
    {
        $inventaire = Inventaire::with(['lignes.article', 'magasin', 'utilisateur', 'document'])->findOrFail($id);
        $this->magasinAccessible($request, $inventaire->magasin_id);

        return view('stock::inventaires.show', [
            'inventaire' => $inventaire,
            'unitesMesure' => config('referentiel.unites_mesure'),
        ]);
    }

    /**
     * Fiche de comptage vierge du magasin (comptage à l'aveugle par défaut : sans stock théorique).
     */
    public function ficheComptage(Request $request)
    {
        $data = $request->validate([
            'magasin_id' => ['required', 'integer', 'exists:magasins,id'],
            'categorie_id' => ['nullable', 'integer', 'exists:categories,id'],
            'theorique' => ['nullable', 'boolean'],
        ]);
        $magasin = $this->magasinAccessible($request, $data['magasin_id']);
        $categories = Categorie::pluck('libelle', 'id');

        $articles = collect($this->inventaires->articlesAInventorier($magasin))
            ->when($data['categorie_id'] ?? null, fn ($c, $id) => $c->where('categorie_id', (int) $id))
            ->sortBy(fn ($a) => ($categories[$a['categorie_id']] ?? '').'|'.$a['code']);

        $lots = Parametres::get('lots_actifs')
            ? DB::table('stocks_lots as s')->join('lots as l', 'l.id', '=', 's.lot_id')
                ->where('s.magasin_id', $magasin->id)->where('s.quantite', '>', 0)
                ->orderBy('l.date_peremption')
                ->get(['l.article_id', 'l.numero', 'l.date_peremption'])->groupBy('article_id')
            : collect();

        $this->journaliser('fiche_comptage_imprimee', $request, $magasin, ['categorie_id' => $data['categorie_id'] ?? null]);

        return app(PdfService::class)->rendre('stock::pdf.fiche-comptage', [
            'magasin' => $magasin,
            'groupes' => $articles->groupBy(fn ($a) => $categories[$a['categorie_id']] ?? 'Sans catégorie'),
            'lots' => $lots,
            'avecTheorique' => $request->boolean('theorique'),
            'categorie' => isset($data['categorie_id']) ? $categories[$data['categorie_id']] : null,
            'unitesMesure' => config('referentiel.unites_mesure'),
        ], 'fiche-comptage-'.str($magasin->code)->slug().'-'.now()->format('Ymd').'.pdf');
    }

    /**
     * Procès-verbal d'un inventaire validé : écarts constatés et ajustement généré.
     */
    public function imprimer(Request $request, $id)
    {
        $inventaire = Inventaire::with(['lignes.article.categorie', 'magasin', 'utilisateur', 'document'])->findOrFail($id);
        $this->magasinAccessible($request, $inventaire->magasin_id);
        $this->journaliser('inventaire_imprime', $request, $inventaire->magasin, ['inventaire_id' => $inventaire->id], $inventaire);

        return app(PdfService::class)->rendre('stock::pdf.proces-verbal', [
            'inventaire' => $inventaire,
            'unitesMesure' => config('referentiel.unites_mesure'),
        ], 'pv-inventaire-'.str($inventaire->magasin->code)->slug().'-'.$inventaire->date_inventaire->format('Ymd').'.pdf');
    }

    protected function journaliser(string $evenement, Request $request, Magasin $magasin, array $proprietes, $sujet = null): void
    {
        $journal = activity('stock')->causedBy($request->user());
        if ($sujet) {
            $journal->performedOn($sujet);
        }
        $journal->withProperties($proprietes + ['magasin_id' => $magasin->id])
            ->tap(function ($activity) use ($request) {
                $activity->module = 'stock';
                $activity->ip_address = $request->ip();
                $activity->user_agent = $request->userAgent();
            })
            ->log($evenement);
    }

    protected function magasinAccessible(Request $request, $magasinId): Magasin
    {
        $magasin = Magasin::findOrFail($magasinId);
        abort_unless($this->perimetre->peutAcceder($request->user(), $magasin), 403, 'Vous n\'êtes pas rattaché à ce magasin.');

        return $magasin;
    }
}
