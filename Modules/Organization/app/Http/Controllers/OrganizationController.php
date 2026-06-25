<?php

namespace Modules\Organization\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Organization\Http\Requests\DirectionFormRequest;
use Modules\Organization\Http\Requests\ServiceFormRequest;
use Modules\Organization\Http\Requests\UnitFormRequest;
use Modules\Organization\Models\Direction;
use Modules\Organization\Models\Service;
use Modules\Organization\Models\Unit;

class OrganizationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $directions = Direction::where('is_active', true)->orderBy('name')->get();
        $services = Service::where('is_active', true)->with('direction')->orderBy('name')->get();

        return view('organization::index', compact('directions', 'services'));
    }

    /* ── Directions CRUD ── */

    public function directionsData(Request $request)
    {
        $query = Direction::query();

        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }

        return response()->json([
            'data' => $query->get(),
            'recordsTotal' => Direction::count(),
            'recordsFiltered' => $query->count(),
        ]);
    }

    public function storeDirection(DirectionFormRequest $request)
    {
        $direction = Direction::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Direction créée avec succès',
            'data' => $direction,
        ], 201);
    }

    public function editDirection($id)
    {
        $direction = Direction::findOrFail($id);

        return response()->json($direction);
    }

    public function updateDirection(DirectionFormRequest $request, $id)
    {
        $direction = Direction::findOrFail($id);
        $direction->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Direction modifiée avec succès',
            'data' => $direction,
        ]);
    }

    public function destroyDirection($id)
    {
        $direction = Direction::findOrFail($id);

        if ($direction->services()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer cette direction car elle contient des services actifs.',
            ], 422);
        }

        $direction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Direction supprimée avec succès',
        ]);
    }

    /* ── Services CRUD ── */

    public function servicesData(Request $request)
    {
        $query = Service::with('direction');

        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }

        return response()->json([
            'data' => $query->get(),
            'recordsTotal' => Service::count(),
            'recordsFiltered' => $query->count(),
        ]);
    }

    public function storeService(ServiceFormRequest $request)
    {
        $service = Service::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Service créé avec succès',
            'data' => $service->load('direction'),
        ], 201);
    }

    public function editService($id)
    {
        $service = Service::findOrFail($id);

        return response()->json($service);
    }

    public function updateService(ServiceFormRequest $request, $id)
    {
        $service = Service::findOrFail($id);
        $service->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Service modifié avec succès',
            'data' => $service->load('direction'),
        ]);
    }

    public function destroyService($id)
    {
        $service = Service::findOrFail($id);

        if ($service->units()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer ce service car il contient des unités actives.',
            ], 422);
        }

        $service->delete();

        return response()->json([
            'success' => true,
            'message' => 'Service supprimé avec succès',
        ]);
    }

    /* ── Unités CRUD ── */

    public function unitsData(Request $request)
    {
        $query = Unit::with('service.direction');

        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }

        return response()->json([
            'data' => $query->get(),
            'recordsTotal' => Unit::count(),
            'recordsFiltered' => $query->count(),
        ]);
    }

    public function storeUnit(UnitFormRequest $request)
    {
        $unit = Unit::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Unité créée avec succès',
            'data' => $unit->load('service.direction'),
        ], 201);
    }

    public function editUnit($id)
    {
        $unit = Unit::findOrFail($id);

        return response()->json($unit);
    }

    public function updateUnit(UnitFormRequest $request, $id)
    {
        $unit = Unit::findOrFail($id);
        $unit->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Unité modifiée avec succès',
            'data' => $unit->load('service.direction'),
        ]);
    }

    public function destroyUnit($id)
    {
        $unit = Unit::findOrFail($id);
        $unit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Unité supprimée avec succès',
        ]);
    }
}
