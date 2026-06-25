<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Inventory\Models\StockBalance;
use Modules\Inventory\Models\StockMovement;
use Modules\Inventory\Models\Store;

class StockEntryController extends Controller
{
    public function index()
    {
        $entries = StockMovement::where('type', 'entry')
            ->with(['store', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('inventory::entries.index', compact('entries'));
    }

    public function create()
    {
        $stores = Store::where('is_active', true)->orderBy('name')->get();

        return view('inventory::entries.create', compact('stores'));
    }

    /** Info magasin + articles fréquents (AJAX step 1 → 2) */
    public function storeInfo(Store $store)
    {
        return response()->json([
            'store' => $store,
            'frequent_items' => $store->frequentItems()
                ->with('article.category')
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    /** Sauvegarde brouillon automatique */
    public function saveDraft(Request $request)
    {
        $movement = $request->draft_id
            ? StockMovement::findOrFail($request->draft_id)
            : new StockMovement;

        $movement->fill([
            'reference' => $movement->reference ?? StockMovement::generateReference('entry'),
            'type' => 'entry',
            'store_id' => $request->store_id,
            'status' => 'draft',
            'user_id' => auth()->id(),
            'comment' => $request->comment,
        ])->save();

        // Remplacer les lignes existantes
        $movement->lines()->delete();
        foreach ($request->lines ?? [] as $line) {
            $movement->lines()->create([
                'article_id' => $line['article_id'],
                'quantity' => $line['quantity'],
            ]);
        }

        return response()->json(['success' => true, 'draft_id' => $movement->id, 'reference' => $movement->reference]);
    }

    /** Validation finale → mise à jour des soldes */
    public function validate(StockMovement $entry)
    {
        if (! $entry->isDraft()) {
            return response()->json(['success' => false, 'message' => 'Déjà validé'], 422);
        }

        foreach ($entry->lines as $line) {
            StockBalance::updateOrCreate(
                ['store_id' => $entry->store_id, 'article_id' => $line->article_id],
                ['last_movement_at' => now()]
            )->increment('quantity', $line->quantity);
        }

        $entry->update(['status' => 'validated', 'validated_at' => now()]);

        activity()->causedBy(auth()->user())
            ->performedOn($entry)
            ->log('Entrée de stock validée');

        return response()->json([
            'success' => true,
            'message' => "Entrée {$entry->reference} validée avec succès",
            'reference' => $entry->reference,
        ]);
    }

    public function show(StockMovement $entry)
    {
        $entry->load(['store', 'user', 'lines.article.category']);

        return view('inventory::entries.show', compact('entry'));
    }
}
