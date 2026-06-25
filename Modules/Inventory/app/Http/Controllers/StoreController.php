<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Inventory\Http\Requests\StoreFormRequest;
use Modules\Inventory\Models\Store;

class StoreController extends Controller
{
    public function index()
    {
        return view('inventory::stores.index');
    }

    /** DataTable server-side */
    public function data(Request $request)
    {
        $query = Store::query();

        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }

        return response()->json([
            'data' => $query->get(),
            'recordsTotal' => Store::count(),
            'recordsFiltered' => $query->count(),
        ]);
    }

    public function store(StoreFormRequest $request)
    {
        $store = Store::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Magasin créé avec succès',
            'data' => $store,
        ], 201);
    }

    public function update(StoreFormRequest $request, Store $store)
    {
        $store->update($request->validated());

        return response()->json(['success' => true, 'message' => 'Magasin modifié', 'data' => $store]);
    }

    public function show(Store $store)
    {
        return response()->json($store);
    }

    public function edit(Store $store)
    {
        return response()->json($store);
    }

    public function destroy(Store $store)
    {
        if ($store->stockMovements()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible : ce magasin a des mouvements enregistrés.',
            ], 422);
        }
        $store->delete();

        return response()->json(['success' => true, 'message' => 'Magasin supprimé']);
    }

    /** Articles fréquents d'un magasin */
    public function frequentItems(Store $store)
    {
        return response()->json($store->frequentItems()->with('article.category')->get());
    }

    public function updateFrequentItems(Request $request, Store $store)
    {
        $store->frequentItems()->delete();
        foreach ($request->article_ids ?? [] as $i => $id) {
            $store->frequentItems()->create(['article_id' => $id, 'sort_order' => $i]);
        }

        return response()->json(['success' => true, 'message' => 'Articles fréquents mis à jour']);
    }
}
