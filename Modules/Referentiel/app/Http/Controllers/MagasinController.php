<?php

namespace Modules\Referentiel\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Referentiel\Http\Concerns\RepondBootstrapTable;
use Modules\Referentiel\Http\Requests\MagasinRequest;
use Modules\Referentiel\Models\Magasin;
use Modules\Referentiel\Models\ResponsableMagasin;

class MagasinController extends Controller
{
    use RepondBootstrapTable;

    public function index()
    {
        return view('referentiel::magasins.index');
    }

    public function getData(Request $request)
    {
        $query = Magasin::query()->with('responsablesActifs.user');

        $search = $request->get('search');
        if (filled($search)) {
            $query->where(function (Builder $q) use ($search) {
                $this->filtreRecherche($q, $search, ['code', 'libelle']);
                $q->orWhereHas('responsablesActifs.user', function (Builder $u) use ($search) {
                    $this->filtreRecherche($u, $search, ['name', 'last_name']);
                });
            });
        }

        // Colonne « Responsables actuels » : « J. Dupont (Principal), M. Martin (Adjoint) »
        return $this->paginer($query, $request, ['code', 'libelle', 'localisation', 'actif'], 'code', function (Magasin $magasin) {
            return $magasin->makeHidden('responsablesActifs')->toArray() + [
                'responsables_actuels' => $magasin->responsablesActifs
                    ->sortBy(fn ($r) => $r->role_responsable === ResponsableMagasin::PRINCIPAL ? 0 : 1)
                    ->map(fn ($r) => mb_substr($r->user->name, 0, 1).'. '.$r->user->last_name.' ('.ucfirst(strtolower($r->role_responsable)).')')
                    ->implode(', '),
            ];
        });
    }

    public function show($id)
    {
        return response()->json(['success' => true, 'data' => Magasin::findOrFail($id)]);
    }

    public function store(MagasinRequest $request)
    {
        $magasin = Magasin::create($request->validated() + ['actif' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Magasin créé avec succès',
            'data' => $magasin,
        ]);
    }

    public function update(MagasinRequest $request, $id)
    {
        $magasin = Magasin::findOrFail($id);
        $magasin->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Magasin modifié avec succès',
            'data' => $magasin,
        ]);
    }

    public function toggleStatus($id)
    {
        $magasin = Magasin::findOrFail($id);
        $magasin->update(['actif' => ! $magasin->actif]);

        return response()->json([
            'success' => true,
            'message' => $magasin->actif ? 'Magasin activé' : 'Magasin désactivé',
            'data' => $magasin,
        ]);
    }
}
