<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Inventory\Http\Requests\ArticleFormRequest;
use Modules\Inventory\Models\Article;
use Modules\Inventory\Models\Category;

class ArticleController extends Controller
{
    public function index()
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return view('inventory::articles.index', compact('categories'));
    }

    /** DataTable server-side */
    public function data(Request $request)
    {
        $query = Article::with('category');

        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('designation', 'LIKE', "%{$search}%")
                    ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }

        return response()->json([
            'data' => $query->get(),
            'recordsTotal' => Article::count(),
            'recordsFiltered' => $query->count(),
        ]);
    }

    public function create()
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        if (request()->ajax()) {
            return view('inventory::articles.partials.form', compact('categories'))->render();
        }

        return view('inventory::articles.create', compact('categories'));
    }

    public function store(ArticleFormRequest $request)
    {
        $article = Article::create($request->validated());
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Article créé avec succès',
                'data' => $article->load('category'),
            ], 201);
        }

        return redirect()->route('inventory.articles.index')->with('success', 'Article créé');
    }

    public function show(Article $article)
    {
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json($article->load('category'));
        }

        return view('inventory::articles.show', compact('article'));
    }

    public function edit(Article $article)
    {
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json($article);
        }
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return view('inventory::articles.edit', compact('article', 'categories'));
    }

    public function update(ArticleFormRequest $request, Article $article)
    {
        $article->update($request->validated());
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Article modifié', 'data' => $article->load('category')]);
        }

        return redirect()->route('inventory.articles.index')->with('success', 'Article modifié');
    }

    public function destroy(Article $article)
    {
        if ($article->movementLines()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible : cet article a des mouvements enregistrés.',
            ], 422);
        }
        $article->delete();

        return response()->json(['success' => true, 'message' => 'Article supprimé']);
    }

    /** Recherche autocomplete */
    public function search(Request $request)
    {
        $term = $request->get('q');
        $articles = Article::search($term)
            ->with('category')
            ->where('is_active', true)
            ->limit(10)
            ->get();

        return response()->json($articles);
    }

    /** Création rapide dans le wizard */
    public function quickCreate(ArticleFormRequest $request)
    {
        $article = Article::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Article créé avec succès',
            'data' => $article->load('category'),
        ], 201);
    }
}
