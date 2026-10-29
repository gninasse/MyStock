<?php

namespace Modules\Referentiel\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Models\User;
use Modules\Referentiel\Http\Requests\ResponsableMagasinRequest;
use Modules\Referentiel\Models\Magasin;
use Modules\Referentiel\Models\ResponsableMagasin;
use Modules\Referentiel\Notifications\ResponsableAssigneNotification;

class ResponsableMagasinController extends Controller
{
    public function index($id)
    {
        $magasin = Magasin::findOrFail($id);
        $utilisateurs = User::where('is_active', true)->orderBy('last_name')->orderBy('name')->get();

        return view('referentiel::magasins.responsables', compact('magasin', 'utilisateurs'));
    }

    /**
     * Responsables actuels, historique complet et utilisateurs rattachés.
     */
    public function getData($id)
    {
        $magasin = Magasin::findOrFail($id);

        $formater = fn (ResponsableMagasin $r) => [
            'id' => $r->id,
            'utilisateur' => $r->user->full_name,
            'role_responsable' => $r->role_responsable,
            'date_debut' => $r->date_debut->format('d/m/Y'),
            'date_fin' => $r->date_fin?->format('d/m/Y'),
            'actif' => $r->actif,
        ];

        $responsables = $magasin->responsables()->with('user')->orderByDesc('date_debut')->get();

        return response()->json([
            'success' => true,
            'actuels' => $responsables->where('actif', true)->values()->map($formater),
            'historique' => $responsables->map($formater),
            'rattaches' => $magasin->utilisateurs()->orderBy('last_name')->get()
                ->map(fn (User $u) => ['id' => $u->id, 'utilisateur' => $u->full_name, 'email' => $u->email]),
        ]);
    }

    public function store(ResponsableMagasinRequest $request, $id)
    {
        $magasin = Magasin::findOrFail($id);
        $actifs = $magasin->responsablesActifs();

        if ((clone $actifs)->where('user_id', $request->user_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cet utilisateur est déjà responsable actif de ce magasin.',
            ], 422);
        }

        if ($request->role_responsable === ResponsableMagasin::PRINCIPAL
            && (clone $actifs)->where('role_responsable', ResponsableMagasin::PRINCIPAL)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Il existe déjà un responsable Principal actif pour ce magasin. Veuillez d\'abord terminer le mandat du principal actuel.',
            ], 422);
        }

        $responsable = $magasin->responsables()->create($request->validated() + [
            'actif' => true,
            'cree_par' => $request->user()->id,
        ]);
        $responsable->load('user', 'magasin');

        try {
            $responsable->user->notify(new ResponsableAssigneNotification($responsable));
        } catch (\Throwable $e) {
            // L'échec de l'email ne doit pas annuler l'affectation
            report($e);
        }

        $role = $responsable->role_responsable === ResponsableMagasin::PRINCIPAL ? 'Principal' : 'Adjoint';

        return response()->json([
            'success' => true,
            'message' => "{$responsable->user->full_name} a été ajouté comme responsable {$role} du magasin {$magasin->libelle}.",
        ]);
    }

    public function terminer(Request $request, $id, $responsableId)
    {
        $responsable = ResponsableMagasin::where('magasin_id', $id)->where('actif', true)->findOrFail($responsableId);

        $data = $request->validate([
            'date_fin' => ['nullable', 'date', 'after_or_equal:'.$responsable->date_debut->toDateString()],
        ], [
            'date_fin.after_or_equal' => 'La date de fin ne peut pas précéder la date de début',
        ]);

        $responsable->update([
            'actif' => false,
            'date_fin' => $data['date_fin'] ?? now()->toDateString(),
        ]);

        return response()->json(['success' => true, 'message' => 'Mandat terminé']);
    }

    public function rattacher(Request $request, $id)
    {
        $magasin = Magasin::findOrFail($id);
        $data = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id']]);

        $magasin->utilisateurs()->syncWithoutDetaching([$data['user_id']]);

        $this->journaliserRattachement($magasin, $data['user_id'], 'utilisateur_rattache');

        return response()->json(['success' => true, 'message' => 'Utilisateur rattaché au magasin']);
    }

    public function detacher($id, $userId)
    {
        $magasin = Magasin::findOrFail($id);
        $magasin->utilisateurs()->detach($userId);

        $this->journaliserRattachement($magasin, (int) $userId, 'utilisateur_detache');

        return response()->json(['success' => true, 'message' => 'Utilisateur détaché du magasin']);
    }

    protected function journaliserRattachement(Magasin $magasin, int $userId, string $evenement): void
    {
        activity('referentiel')
            ->performedOn($magasin)
            ->causedBy(auth()->user())
            ->withProperties(['user_id' => $userId])
            ->tap(function ($activity) {
                $activity->module = 'referentiel';
                $activity->ip_address = request()->ip();
                $activity->user_agent = request()->userAgent();
            })
            ->log($evenement);
    }
}
