<?php

namespace Modules\Etats\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Models\User;
use Modules\Etats\Services\EtatsService;
use Modules\Etats\Services\ExportService;
use Modules\Etats\Support\Etat;
use Modules\Referentiel\Models\Article;
use Modules\Referentiel\Models\Categorie;

/**
 * États de la SFD 9. Chaque état s'affiche, s'imprime ou s'exporte
 * (paramètre format=excel|pdf) à partir des mêmes filtres.
 */
class EtatController extends Controller
{
    public function __construct(protected EtatsService $etats, protected ExportService $exports) {}

    public function stockMagasin(Request $request)
    {
        $filtres = $request->validate([
            'magasins' => ['nullable', 'array'],
            'magasins.*' => ['integer'],
            'categorie_id' => ['nullable', 'integer'],
        ]) + [
            // Cases à cocher : valeurs par défaut de la SFD tant que le formulaire n'a pas été envoyé
            'sans_stock' => $request->has('envoye') ? $request->boolean('sans_stock') : true,
            'alerte' => $request->boolean('alerte'),
        ];

        return $this->rendre($request, 'stock-magasin', $this->etats->stockParMagasin($filtres, $request->user()), $filtres, [
            'categories' => Categorie::orderBy('libelle')->get(),
        ]);
    }

    public function stockCategorie(Request $request)
    {
        $filtres = $request->validate(['magasins' => ['nullable', 'array'], 'magasins.*' => ['integer']]);

        return $this->rendre($request, 'stock-categorie', $this->etats->stockParCategorie($filtres, $request->user()), $filtres);
    }

    public function journal(Request $request)
    {
        $filtres = $request->validate([
            'date_du' => ['nullable', 'date_format:Y-m-d'],
            'date_au' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_du'],
            'types' => ['nullable', 'array'],
            'magasin_id' => ['nullable', 'integer'],
            'article' => ['nullable', 'string', 'max:100'],
            'beneficiaire' => ['nullable', 'string', 'max:100'],
            'user_id' => ['nullable', 'integer'],
        ], ['date_au.after_or_equal' => 'La date de fin doit suivre la date de début.']);

        if ($request->has('envoye') && ! $request->has('types')) {
            $filtres['types'] = [];
        }

        return $this->rendre($request, 'journal', $this->etats->journal($filtres, $request->user()), $filtres, [
            'utilisateurs' => User::orderBy('last_name')->orderBy('name')->get(),
            'types' => EtatsService::TYPES,
        ]);
    }

    public function ficheStock(Request $request)
    {
        $filtres = $request->validate([
            'article_id' => ['nullable', 'integer', 'exists:articles,id'],
            'magasin_id' => ['nullable', 'integer', 'exists:magasins,id'],
            'date_du' => ['nullable', 'date_format:Y-m-d'],
            'date_au' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_du'],
        ], ['date_au.after_or_equal' => 'La date de fin doit suivre la date de début.']);

        return $this->rendre($request, 'fiche-stock', $this->etats->ficheStock($filtres, $request->user()), $filtres, [
            'articles' => Article::orderBy('designation')->get(['id', 'code', 'designation']),
        ]);
    }

    /**
     * Impression navigateur : seule la journalisation passe par le serveur (SFD 9.5).
     */
    public function impression(Request $request)
    {
        abort_unless($request->user()->can('etats.export'), 403);
        $data = $request->validate(['titre' => ['required', 'string', 'max:100'], 'filtres' => ['nullable', 'array']]);

        $this->exports->journaliser(new Etat($data['titre'], $data['filtres'] ?? [], []), 'Impression');

        return response()->json(['success' => true]);
    }

    protected function rendre(Request $request, string $vue, ?Etat $etat, array $filtres, array $donnees = [])
    {
        $format = $request->query('format');

        if ($etat && in_array($format, ['excel', 'pdf'], true)) {
            abort_unless($request->user()->can('etats.export'), 403);

            return $format === 'excel' ? $this->exports->excel($etat) : $this->exports->pdf($etat);
        }

        return view("etats::etats.{$vue}", $donnees + [
            'etat' => $etat,
            'filtres' => $filtres,
            'magasins' => $this->etats->magasinsVisibles($request->user()),
            'tropDeLignes' => $etat && $etat->nombreLignes() >= config('etats.lignes_max'),
        ]);
    }
}
