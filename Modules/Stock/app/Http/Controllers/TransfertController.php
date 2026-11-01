<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Models\User;
use Modules\Referentiel\Services\Parametres;
use Modules\Referentiel\Services\PerimetreMagasins;
use Modules\Stock\Models\Document;
use Modules\Stock\Services\TransfertService;

/**
 * Réception des transferts par le magasin destinataire.
 */
class TransfertController extends Controller
{
    public function __construct(protected TransfertService $transferts, protected PerimetreMagasins $perimetre) {}

    public function aReceptionner(Request $request)
    {
        return view('stock::transferts.a-receptionner', [
            'transferts' => $this->enAttente($request->user())
                ->with(['magasin:id,code,libelle', 'magasinDestination:id,code,libelle', 'validateur:id,name,last_name'])
                ->withCount('lignes')
                ->orderBy('validated_at')
                ->get(),
        ]);
    }

    public function reception(Request $request, $id)
    {
        $transfert = $this->transfertAReceptionner($request->user(), $id);

        return view('stock::transferts.reception', [
            'transfert' => $transfert->load('lignes.article', 'magasin', 'magasinDestination', 'validateur'),
            'unitesMesure' => config('referentiel.unites_mesure'),
            'ecartReintegre' => Parametres::get('transfert_ecart') === 'RETOUR_SOURCE',
        ]);
    }

    public function receptionner(Request $request, $id)
    {
        $transfert = $this->transfertAReceptionner($request->user(), $id);
        $donnees = $request->validate([
            'quantites' => ['required', 'array'],
            'quantites.*' => ['required', 'integer', 'min:0'],
            'motif' => ['nullable', 'string', 'max:500'],
        ], [], ['quantites.*' => 'quantité reçue', 'motif' => 'motif']);

        $transfert = $this->transferts->receptionner($transfert, $donnees['quantites'], $donnees['motif'] ?? null, $request->user());
        $ecart = $transfert->lignes()->whereColumn('quantite_recue', '<', 'quantite')->exists();

        return response()->json([
            'success' => true,
            'message' => $ecart
                ? "Transfert {$transfert->reference} réceptionné avec un écart. L'écart est tracé sur le transfert."
                : "Transfert {$transfert->reference} réceptionné. Stock de {$transfert->magasinDestination->libelle} mis à jour.",
            'data' => ['id' => $transfert->id],
        ]);
    }

    /**
     * Transferts en transit vers les magasins de l'utilisateur.
     */
    public function enAttente(User $user)
    {
        return Document::where('type', Document::TRANSFERT)
            ->where('statut', Document::EN_TRANSIT)
            ->whereIn('magasin_destination_id', $this->perimetre->query($user)->select('id'));
    }

    protected function transfertAReceptionner(User $user, $id): Document
    {
        $transfert = Document::where('type', Document::TRANSFERT)->findOrFail($id);
        abort_unless($this->perimetre->peutAcceder($user, $transfert->magasinDestination), 403, 'Vous n\'êtes pas rattaché au magasin destinataire.');
        abort_unless($transfert->statut === Document::EN_TRANSIT, 409, 'Ce transfert n\'est pas en attente de réception.');

        return $transfert;
    }
}
