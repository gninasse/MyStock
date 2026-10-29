<?php

namespace Modules\Referentiel\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Referentiel\Models\Direction;
use Modules\Referentiel\Models\Service;
use Modules\Referentiel\Models\Unite;

/**
 * Arborescence Direction → Service → Unité (SFD 4).
 */
class StructureController extends Controller
{
    /**
     * niveau => [modèle, table, colonne parent, table parent, libellé, accord du participe]
     */
    protected const NIVEAUX = [
        'direction' => [Direction::class, 'directions', null, null, 'Direction', 'e'],
        'service' => [Service::class, 'services', 'direction_id', 'directions', 'Service', ''],
        'unite' => [Unite::class, 'unites', 'service_id', 'services', 'Unité', 'e'],
    ];

    public function index()
    {
        return view('referentiel::structures.index');
    }

    public function arbre()
    {
        $directions = Direction::with(['services' => fn ($q) => $q->orderBy('code'), 'services.unites' => fn ($q) => $q->orderBy('code')])
            ->orderBy('code')
            ->get();

        return response()->json(['success' => true, 'data' => $directions]);
    }

    public function store(Request $request, string $niveau)
    {
        [$modele] = $this->niveau($niveau);
        $structure = $modele::create($this->valider($request, $niveau) + ['actif' => true]);

        return response()->json([
            'success' => true,
            'message' => $this->libelle($niveau, 'créé').' avec succès',
            'data' => $structure,
        ]);
    }

    public function update(Request $request, string $niveau, $id)
    {
        [$modele] = $this->niveau($niveau);
        $structure = $modele::findOrFail($id);
        $structure->update($this->valider($request, $niveau, $structure));

        return response()->json([
            'success' => true,
            'message' => $this->libelle($niveau, 'modifié').' avec succès',
            'data' => $structure,
        ]);
    }

    public function destroy(string $niveau, $id)
    {
        [$modele] = $this->niveau($niveau);
        $structure = $modele::findOrFail($id);

        $enfants = match ($niveau) {
            'direction' => $structure->services()->exists(),
            'service' => $structure->unites()->exists(),
            default => false,
        };

        if ($enfants) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer : cet élément contient des sous-niveaux. Supprimez-les d\'abord ou désactivez-le.',
            ], 422);
        }

        try {
            $structure->delete();
        } catch (QueryException $e) {
            // Clé étrangère : la structure est bénéficiaire de documents de stock
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer : cet élément est lié à des mouvements de stock. Voulez-vous le désactiver ?',
            ], 422);
        }

        return response()->json(['success' => true, 'message' => $this->libelle($niveau, 'supprimé')]);
    }

    /**
     * « Direction créée », « Service modifié »…
     */
    protected function libelle(string $niveau, string $participe): string
    {
        return self::NIVEAUX[$niveau][4].' '.$participe.self::NIVEAUX[$niveau][5];
    }

    protected function niveau(string $niveau): array
    {
        abort_unless(isset(self::NIVEAUX[$niveau]), 404);

        return self::NIVEAUX[$niveau];
    }

    protected function valider(Request $request, string $niveau, ?Model $structure = null): array
    {
        [, $table, $parent, $tableParent] = self::NIVEAUX[$niveau];

        $request->merge(['code' => strtoupper(trim((string) $request->code))]);

        $regles = [
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9_-]+$/', Rule::unique($table, 'code')->ignore($structure?->id)],
            'libelle' => ['required', 'string', 'max:100'],
            'actif' => ['sometimes', 'boolean'],
        ];

        if ($parent) {
            $regles[$parent] = ['required', 'integer', Rule::exists($tableParent, 'id')];
        }

        return $request->validate($regles, [
            'code.required' => 'Le code est obligatoire',
            'code.regex' => 'Le code ne peut contenir que des lettres, chiffres, tirets et soulignés',
            'code.unique' => 'Ce code existe déjà. Veuillez en choisir un autre.',
            'libelle.required' => 'Le libellé est obligatoire',
            'direction_id.required' => 'Veuillez choisir une Direction',
            'service_id.required' => 'Veuillez choisir un Service',
        ]);
    }
}
