<?php

namespace Modules\Referentiel\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Referentiel\Http\Concerns\RepondBootstrapTable;
use Modules\Referentiel\Http\Requests\CategorieRequest;
use Modules\Referentiel\Models\Categorie;

class CategorieController extends Controller
{
    use RepondBootstrapTable;

    public function index()
    {
        return view('referentiel::categories.index', [
            'unitesMesure' => config('referentiel.unites_mesure'),
        ]);
    }

    public function getData(Request $request)
    {
        $query = Categorie::query()->withCount('articles');
        $this->filtreRecherche($query, $request->get('search'), ['code', 'libelle']);

        return $this->paginer($query, $request, ['code', 'libelle', 'unite_par_defaut', 'articles_count']);
    }

    public function show($id)
    {
        return response()->json(['success' => true, 'data' => Categorie::findOrFail($id)]);
    }

    public function store(CategorieRequest $request)
    {
        $categorie = Categorie::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Catégorie créée avec succès',
            'data' => $categorie,
        ]);
    }

    public function update(CategorieRequest $request, $id)
    {
        $categorie = Categorie::findOrFail($id);
        $categorie->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Catégorie modifiée avec succès',
            'data' => $categorie,
        ]);
    }

    public function destroy($id)
    {
        $categorie = Categorie::findOrFail($id);

        if ($categorie->articles()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer : des articles appartiennent à cette catégorie.',
            ], 422);
        }

        $categorie->delete();

        return response()->json(['success' => true, 'message' => 'Catégorie supprimée']);
    }
}
