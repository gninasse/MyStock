<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Inventory\Http\Requests\CategoryFormRequest;
use Modules\Inventory\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        return view('inventory::categories.index');
    }

    /** DataTable server-side */
    public function data(Request $request)
    {
        $query = Category::query();

        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }

        return response()->json([
            'data' => $query->get(),
            'recordsTotal' => Category::count(),
            'recordsFiltered' => $query->count(),
        ]);
    }

    public function store(CategoryFormRequest $request)
    {
        $category = Category::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Catégorie créée avec succès',
            'data' => $category,
        ], 201);
    }

    public function update(CategoryFormRequest $request, Category $category)
    {
        $category->update($request->validated());

        return response()->json(['success' => true, 'message' => 'Catégorie modifiée', 'data' => $category]);
    }

    public function show(Category $category)
    {
        return response()->json($category);
    }

    public function edit(Category $category)
    {
        return response()->json($category);
    }

    public function destroy(Category $category)
    {
        if ($category->articles()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible : cette catégorie contient des articles.',
            ], 422);
        }
        $category->delete();

        return response()->json(['success' => true, 'message' => 'Catégorie supprimée']);
    }
}
