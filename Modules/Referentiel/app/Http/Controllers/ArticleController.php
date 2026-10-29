<?php

namespace Modules\Referentiel\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Referentiel\Http\Concerns\RepondBootstrapTable;
use Modules\Referentiel\Http\Requests\ArticleRequest;
use Modules\Referentiel\Models\Article;
use Modules\Referentiel\Models\Categorie;

class ArticleController extends Controller
{
    use RepondBootstrapTable;

    public function index()
    {
        return view('referentiel::articles.index', [
            'categories' => Categorie::orderBy('libelle')->get(),
            'unitesMesure' => config('referentiel.unites_mesure'),
        ]);
    }

    public function getData(Request $request)
    {
        $query = Article::query()->with('categorie:id,code,libelle');
        $this->filtreRecherche($query, $request->get('search'), ['code', 'designation']);

        if ($request->filled('categorie_id')) {
            $query->where('categorie_id', $request->integer('categorie_id'));
        }

        $unites = config('referentiel.unites_mesure');

        return $this->paginer($query, $request, ['code', 'designation', 'unite_mesure', 'stock_minimum', 'actif'], 'code',
            fn (Article $article) => $article->toArray() + ['unite_libelle' => $unites[$article->unite_mesure] ?? $article->unite_mesure]
        );
    }

    /**
     * Recherche d'articles actifs pour les listes de sélection (articles fréquents, mouvements).
     */
    public function recherche(Request $request)
    {
        $query = Article::actifs()->with('categorie:id,libelle');
        $this->filtreRecherche($query, $request->get('q'), ['code', 'designation']);

        if ($request->filled('categorie_id')) {
            $query->where('categorie_id', $request->integer('categorie_id'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderBy('designation')->limit(30)->get(['id', 'code', 'designation', 'unite_mesure', 'categorie_id']),
        ]);
    }

    public function show($id)
    {
        return response()->json(['success' => true, 'data' => Article::findOrFail($id)]);
    }

    public function store(ArticleRequest $request)
    {
        $article = Article::create($request->validated() + [
            'actif' => true,
            'cree_par' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Article « {$article->designation} » créé avec succès",
            'data' => $article,
        ]);
    }

    public function update(ArticleRequest $request, $id)
    {
        $article = Article::findOrFail($id);
        $article->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Article modifié avec succès',
            'data' => $article,
        ]);
    }

    public function toggleStatus($id)
    {
        $article = Article::findOrFail($id);
        $article->update(['actif' => ! $article->actif]);

        return response()->json([
            'success' => true,
            'message' => $article->actif ? 'Article activé' : 'Article désactivé',
            'data' => $article,
        ]);
    }
}
