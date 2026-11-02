<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;
use Modules\Core\Services\PdfService;
use Modules\Referentiel\Models\Article;
use Modules\Referentiel\Models\Categorie;
use Modules\Referentiel\Models\Magasin;
use Modules\Referentiel\Services\PerimetreMagasins;
use Modules\Stock\Http\Requests\DocumentRequest;
use Modules\Stock\Models\Document;
use Modules\Stock\Models\LigneMouvement;
use Modules\Stock\Services\MouvementService;
use Modules\Stock\Services\TransfertService;

class DocumentController extends Controller
{
    /**
     * Segment d'URL => type de document saisissable
     */
    protected const TYPES = ['entrees' => Document::ENTREE, 'sorties' => Document::SORTIE, 'transferts' => Document::TRANSFERT];

    protected const PERMISSIONS = [
        Document::ENTREE => 'stock.entrees.saisir',
        Document::SORTIE => 'stock.sorties.saisir',
        Document::TRANSFERT => 'stock.transferts.saisir',
    ];

    public function __construct(
        protected MouvementService $mouvements,
        protected TransfertService $transferts,
        protected PerimetreMagasins $perimetre,
    ) {}

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
            ->with(['magasin:id,code,libelle', 'magasinDestination:id,libelle', 'utilisateur:id,name,last_name', 'service:id,libelle', 'unite:id,libelle'])
            ->withCount('lignes')
            // Un transfert est visible du magasin source et du magasin destinataire
            ->where(fn (Builder $q) => $q->whereIn('magasin_id', $magasins)
                ->orWhere(fn (Builder $t) => $t->where('type', Document::TRANSFERT)->whereIn('magasin_destination_id', clone $magasins)));

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
                'beneficiaire' => $d->type === Document::TRANSFERT ? '→ '.$d->magasinDestination?->libelle : $d->beneficiaire,
                'lignes_count' => $d->lignes_count,
                'statut' => $d->statut,
                'utilisateur' => $d->utilisateur->full_name,
                'modifiable' => $d->estBrouillon() && isset(self::PERMISSIONS[$d->type]) && $request->user()->can(self::PERMISSIONS[$d->type]),
                'annulable' => $d->statut === Document::VALIDE && ! $d->document_annule_id && in_array($d->type, [Document::ENTREE, Document::SORTIE], true) && $request->user()->can('stock.documents.annuler'),
            ]);

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    public function show(Request $request, $id)
    {
        $document = Document::with(['lignes.article', 'lignes.lots.lot', 'magasin', 'magasinDestination', 'direction', 'service', 'unite', 'utilisateur',
            'validateur', 'recepteur', 'documentAnnule', 'annulation', 'inventaire', 'documentOrigine', 'documentsLies'])
            ->findOrFail($id);
        $user = $request->user();
        $this->autoriserConsultation($user, $document);

        return view('stock::documents.show', [
            'document' => $document,
            'peutRetourner' => $document->type === Document::TRANSFERT && $document->statut === Document::VALIDE
                && $user->can('stock.transferts.saisir') && $this->perimetre->peutAcceder($user, $document->magasinDestination)
                && $this->transferts->quantitesRetournables($document),
            'peutReceptionner' => $document->type === Document::TRANSFERT && $document->statut === Document::EN_TRANSIT
                && $user->can('stock.transferts.receptionner') && $this->perimetre->peutAcceder($user, $document->magasinDestination),
            'peutAnnulerTransfert' => $document->type === Document::TRANSFERT && $document->statut === Document::EN_TRANSIT
                && $user->can('stock.transferts.saisir') && $this->perimetre->peutAcceder($user, $document->magasin),
            'unitesMesure' => config('referentiel.unites_mesure'),
        ]);
    }

    /**
     * Bon imprimable du document (aperçu PDF en modale, ?telecharger=1 pour le fichier).
     */
    public function imprimer(Request $request, $id)
    {
        $document = Document::with(['lignes.article', 'lignes.lots.lot', 'magasin', 'magasinDestination', 'direction', 'service', 'unite',
            'utilisateur', 'validateur', 'recepteur', 'documentAnnule', 'annulation', 'documentOrigine.magasin', 'inventaire'])
            ->findOrFail($id);
        $this->autoriserConsultation($request->user(), $document);
        $this->mouvements->journaliser($document, 'document_imprime', $request->user());

        $nom = str($document->reference ?? 'brouillon-'.$document->id)->slug().'.pdf';

        return app(PdfService::class)->rendre('stock::pdf.document', [
            'document' => $document,
            'unitesMesure' => config('referentiel.unites_mesure'),
        ], $nom);
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

    /**
     * Retour vers le magasin source d'un transfert reçu : nouveau transfert inverse,
     * pré-rempli avec les quantités encore retournables.
     */
    public function retour(Request $request, $id)
    {
        $origine = Document::with('magasinDestination')->where('type', Document::TRANSFERT)->findOrFail($id);
        $this->autoriserType($request->user(), Document::TRANSFERT);
        $this->autoriserMagasin($request->user(), $origine->magasinDestination);

        $retournables = $this->transferts->quantitesRetournables($origine);
        abort_unless($retournables, 409, 'Rien à retourner pour ce transfert.');

        $retour = new Document([
            'type' => Document::TRANSFERT,
            'date_mouvement' => now(),
            'statut' => Document::BROUILLON,
            'magasin_id' => $origine->magasin_destination_id,
            'magasin_destination_id' => $origine->magasin_id,
            'document_origine_id' => $origine->id,
            'commentaire' => "Retour du transfert {$origine->reference}",
        ]);
        $retour->setRelation('lignes', collect($retournables)->map(fn ($quantite, $articleId) => (new LigneMouvement([
            'article_id' => $articleId,
            'quantite' => $quantite,
        ]))->setRelation('article', Article::find($articleId)))->values());
        $retour->setRelation('documentOrigine', $origine);

        return $this->formulaire($request, $retour);
    }

    public function modifier(Request $request, $id)
    {
        $document = Document::with('lignes.article', 'lignes.lots.lot', 'documentOrigine')->findOrFail($id);
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
        $this->autoriserMagasin($request->user(), $document->magasin);
        $inverse = $this->mouvements->annuler($document, $request->user());

        return response()->json([
            'success' => true,
            'message' => "Mouvement {$document->reference} annulé par {$inverse->reference}.",
            'data' => ['id' => $inverse->id, 'reference' => $inverse->reference],
        ]);
    }

    /**
     * Annulation d'un transfert pas encore reçu : la marchandise est réintégrée à la source.
     */
    public function annulerTransfert(Request $request, $id)
    {
        $document = Document::findOrFail($id);
        $this->autoriserDocument($request->user(), $document);
        $reintegration = $this->transferts->annuler($document, $request->user());

        return response()->json([
            'success' => true,
            'message' => "Transfert {$document->reference} annulé : quantités réintégrées par {$reintegration->reference}.",
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

            if ($request->action !== 'valider') {
                return $document;
            }

            return $type === Document::TRANSFERT
                ? $this->transferts->expedier($document, $user)
                : $this->mouvements->valider($document, $user);
        });

        $message = match (true) {
            $document->estBrouillon() => 'Brouillon enregistré. Vous pouvez le retrouver dans la liste des documents.',
            $document->statut === Document::EN_TRANSIT => "Transfert {$document->reference} expédié. Il est en attente de réception par {$document->magasinDestination->libelle}.",
            $type === Document::TRANSFERT => "Transfert {$document->reference} effectué. Stock des deux magasins mis à jour.",
            default => ($type === Document::ENTREE ? 'Entrée' : 'Sortie')." {$document->reference} validée avec succès. Stock mis à jour.",
        };

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
            'lot_numero' => $ligne->lots->first()?->lot->numero,
            'date_peremption' => $ligne->lots->first()?->lot->date_peremption?->toDateString(),
        ])->values();

        return view('stock::documents.saisie', [
            'document' => $document,
            'lignes' => $lignes,
            'magasins' => $this->perimetre->query($request->user())->where('actif', true)->orderBy('libelle')->get(),
            'destinations' => $document->type === Document::TRANSFERT ? Magasin::where('actif', true)->orderBy('libelle')->get() : collect(),
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

    /**
     * Consultation : magasin du document, ou magasin destinataire d'un transfert.
     */
    protected function autoriserConsultation(User $user, Document $document): void
    {
        abort_unless($this->perimetre->peutAcceder($user, $document->magasin)
            || ($document->type === Document::TRANSFERT && $document->magasinDestination
                && $this->perimetre->peutAcceder($user, $document->magasinDestination)), 403);
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
