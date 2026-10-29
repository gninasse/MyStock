<?php

namespace Modules\Referentiel\Http\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait RepondBootstrapTable
{
    /**
     * Tri et pagination côté serveur au format attendu par Bootstrap Table.
     * Seules les colonnes de $triables sont acceptées pour le tri ; $transformer formate chaque ligne.
     */
    protected function paginer(Builder $query, Request $request, array $triables, string $triParDefaut = 'code', ?callable $transformer = null): JsonResponse
    {
        $sort = in_array($request->get('sort'), $triables, true) ? $request->get('sort') : $triParDefaut;
        $order = $request->get('order') === 'desc' ? 'desc' : 'asc';

        $total = (clone $query)->count();
        $rows = $query->orderBy($sort, $order)
            ->offset((int) $request->get('offset', 0))
            ->limit(min((int) $request->get('limit', 20), 100))
            ->get();

        if ($transformer) {
            $rows = $rows->map($transformer);
        }

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    protected function filtreRecherche(Builder $query, ?string $search, array $colonnes): Builder
    {
        if (blank($search)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search, $colonnes) {
            foreach ($colonnes as $colonne) {
                $q->orWhereRaw("LOWER({$colonne}) LIKE ?", ['%'.mb_strtolower($search).'%']);
            }
        });
    }
}
