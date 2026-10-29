<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;
use Modules\Referentiel\Models\Categorie;
use Modules\Referentiel\Models\Magasin;
use Modules\Referentiel\Services\PerimetreMagasins;
use Modules\Stock\Http\Requests\DocumentRequest;
use Modules\Stock\Models\Document;
use Modules\Stock\Services\MouvementService;

class DocumentController extends Controller
{
    /**
     * Segment d'URL => type de document saisissable
     */
    protected const TYPES = ['entrees' => Document::ENTREE, 'sorties' => Document::SORTIE];

    protected const PERMISSIONS = [Document::ENTREE => 'stock.entrees.saisir', Document::SORTIE => 'stock.sorties.saisir'];

    public function __construct(protected MouvementService $mouvements, protected PerimetreMagasins $perimetre) {}

    public function index(Request $request)
    {
        return view('stock::documents.index', [
            'magasins' => $this->perimetre->query($request->user())->orderBy('libelle')->get(),
        ]);
    }

    public function getData(Request $request)
    {
        $magasins = $this->perimetre->query($request->user())->select('id');
        $query = Document::query()
            ->with(['magasin:id,code,libelle', 'utilisateur:id,name,last_name', 'service:id,libelle', 'unite:id,libelle'])
            ->withCount('lignes')
            ->whereIn('magasin_id', $magasins);

        $query->when($request->filled('date_du'), fn (Builder $q) => $q->whereDate('date_mouvement', '>=', $request->date_du))
            ->when($request->filled('date_au'), fn (Builder $q) => $q->whereDate('date_mouvement', '<=', $request->date_au))
            ->when($request->filled('type'), fn (Builder $q) => $q->where('type', $request->type))
            ->when($request->filled('statut'), fn (Builder $q) => $q->where('statut', $request->statut))
            ->when($request->filled('magasin_id'), fn (Builder $q) => $q->where('magasin_id', $request->integer('magasin_id')))
            ->when($request->filled('search'), fn (Builder $q) => $q->whereRaw('LOWER(reference) LIKE ?', ['%'.mb_strtolower($request->search).'%']));

        $sort = in_array($request->get('sort'), ['reference', 'date_mouvement', 'type', 'statut'], true) ? $request->get('sort') : 'date_mouvement';
        $order = $request->get('order') === 'asc' ? 'asc' : 'desc';

        $total = (clone $query)->count();
        $rows = $query->orderBy($sort, $order)->orderByDesc('id')
            ->offset((int) $request->get('offset', 0))
            ->limit(min((int) $request->get('limit', 25), 100))
            ->get()
            ->map(fn (Document $d) => [
                'id' => $d->id,
                'reference' => $d->reference,
                'date_mouvement' => $d->date_mouvement->format('d/m/Y'),
                'type' => $d->type,
                'magasin' => $d->magasin->libelle,
                'beneficiaire' => $d->beneficiaire,
                'lignes_count' => $d->lignes_count,
                'statut' => $d->statut,
                'utilisateur' => $d->utilisateur->full_name,
                'modifiable' => $d->estBrouillon() && isset(self::PERMISSIONS[$d->type]) && $request->user()->can(self::PERMISSIONS[$d->type]),
                'annulable' => $d->statut === Document::VALIDE && isset(self::PERMISSIONS[$d->type]) && $request->user()->can('stock.documents.annuler'),
            ]);

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    public function show(Request $request, $id)
    {
        $document = Document::with(['lignes.article', 'magasin', 'direction', 'service', 'unite', 'utilisateur', 'validateur', 'documentAnnule', 'annulation'])
            ->findOrFail($id);
        $this->autoriserMagasin($request->user(), $document->magasin);

        return view('stock::documents.show', [
            'document' => $document,
            'unitesMesure' => config('referentiel.unites_mesure'),
        ]);
    }

    public function creer(Request $request, string $type)
    {
        $type = $this->type($type);
        $this->autoriserType($request->user(), $type);

        return $this->formulaire($request, new Document([
            'type' => $type,
            'date_mouvement' => now(),
            'statut' => Document::BROUILLON,
        ]));
    }

    public function modifier(Request $request, $id)
    {
        $document = Document::with('lignes.article')->findOrFail($id);
        $this->autoriserDocument($request->user(), $document);
        abort_unless($document->estBrouillon(), 403, 'Seul un brouillon peut être modifié.');

        return $this->formulaire($request, $document);
    }

    public function store(DocumentRequest $request, string $type)
    {
        $type = $this->type($type);
        $this->autoriserType($request->user(), $type);
        $this->autoriserMagasin($request->user(), Magasin::findOrFail($request->magasin_id));

        return $this->enregistrer($request, $type);
    }

    public function update(DocumentRequest $request, $id)
    {
        $document = Document::findOrFail($id);
        $this->autoriserDocument($request->user(), $document);
        $this->autoriserMagasin($request->user(), Magasin::findOrFail($request->magasin_id));

        return $this->enregistrer($request, $document->type, $document);
    }

    public function destroy(Request $request, $id)
    {
        $document = Document::findOrFail($id);
        $this->autoriserDocument($request->user(), $document);
        $this->mouvements->supprimerBrouillon($document, $request->user());

        return response()->json(['success' => true, 'message' => 'Brouillon supprimé']);
    }

    public function annuler(Request $request, $id)
    {
        $document = Document::findOrFail($id);
        $inverse = $this->mouvements->annuler($document, $request->user());

        return response()->json([
            'success' => true,
            'message' => "Mouvement {$document->reference} annulé par {$inverse->reference}.",
            'data' => ['id' => $inverse->id, 'reference' => $inverse->reference],
        ]);
    }

    /**
     * Enregistre le brouillon puis, si demandé, le valide. En cas d'échec de
     * la validation, rien n'est conservé : l'utilisateur corrige sa saisie.
     */
    protected function enregistrer(DocumentRequest $request, string $type, ?Document $document = null)
    {
        $user = $request->user();

        $document = DB::transaction(function () use ($request, $type, $document, $user) {
            $document = $this->mouvements->enregistrerBrouillon($type, $request->validated(), $user, $document);

            return $request->action === 'valider' ? $this->mouvements->valider($document, $user) : $document;
        });

        $libelle = $type === Document::ENTREE ? 'Entrée' : 'Sortie';
        $message = $document->estBrouillon()
            ? 'Brouillon enregistré. Vous pouvez le retrouver dans la liste des documents.'
            : "{$libelle} {$document->reference} validée avec succès. Stock mis à jour.";

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => ['id' => $document->id, 'statut' => $document->statut, 'reference' => $document->reference],
        ]);
    }

    protected function formulaire(Request $request, Document $document)
    {
        $lignes = $document->lignes->map(fn ($ligne) => [
            'article_id' => $ligne->article_id,
            'code' => $ligne->article->code,
            'designation' => $ligne->article->designation,
            'unite_mesure' => $ligne->article->unite_mesure,
            'quantite' => $ligne->quantite,
        ])->values();

        return view('stock::documents.saisie', [
            'document' => $document,
            'lignes' => $lignes,
            'magasins' => $this->perimetre->query($request->user())->where('actif', true)->orderBy('libelle')->get(),
            'categories' => Categorie::orderBy('libelle')->get(),
            'unitesMesure' => config('referentiel.unites_mesure'),
            'segment' => array_search($document->type, self::TYPES, true),
        ]);
    }

    protected function type(string $segment): string
    {
        abort_unless(isset(self::TYPES[$segment]), 404);

        return self::TYPES[$segment];
    }

    protected function autoriserType(User $user, string $type): void
    {
        abort_unless(isset(self::PERMISSIONS[$type]) && $user->can(self::PERMISSIONS[$type]), 403);
    }

    protected function autoriserMagasin(User $user, Magasin $magasin): void
    {
        abort_unless($this->perimetre->peutAcceder($user, $magasin), 403, 'Vous n\'êtes pas rattaché à ce magasin.');
    }

    protected function autoriserDocument(User $user, Document $document): void
    {
        $this->autoriserType($user, $document->type);
        $this->autoriserMagasin($user, $document->magasin);
    }
}
