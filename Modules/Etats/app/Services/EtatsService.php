<?php

namespace Modules\Etats\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;
use Modules\Etats\Support\Etat;
use Modules\Referentiel\Models\Article;
use Modules\Referentiel\Models\Categorie;
use Modules\Referentiel\Models\Magasin;
use Modules\Referentiel\Services\PerimetreMagasins;

/**
 * Les quatre états de la SFD 9, toujours restreints aux magasins visibles par l'utilisateur.
 */
class EtatsService
{
    public const TYPES = ['ENTREE' => 'Entrée', 'SORTIE' => 'Sortie', 'AJUSTEMENT' => 'Ajustement'];

    public function __construct(protected PerimetreMagasins $perimetre) {}

    public function magasinsVisibles(User $user): Collection
    {
        return $this->perimetre->query($user)->orderBy('libelle')->get();
    }

    /**
     * SFD 9.1 — filtres : magasins[], categorie_id, sans_stock (bool), alerte (bool)
     */
    public function stockParMagasin(array $f, User $user): Etat
    {
        $magasins = $this->magasinsChoisis($f['magasins'] ?? [], $user);
        $unites = config('referentiel.unites_mesure');
        $groupes = [];

        foreach ($magasins as $magasin) {
            $lignes = $this->stocksArticles($magasin->id, $f)
                ->orderBy('a.code')
                ->limit(config('etats.lignes_max'))
                ->get()
                ->map(function ($r) use ($unites) {
                    $niveau = AlerteStockService::niveau((int) $r->quantite, $r->stock_minimum);

                    return [
                        'code' => $r->code,
                        'designation' => $r->designation,
                        'categorie' => $r->categorie,
                        'unite' => $unites[$r->unite_mesure] ?? $r->unite_mesure,
                        'stock' => (int) $r->quantite,
                        'stock_minimum' => $r->stock_minimum,
                        'alerte' => ['sous' => 'Sous le seuil', 'egal' => 'Au seuil'][$niveau] ?? '',
                        '_classe' => $niveau ? 'alerte-'.$niveau : null,
                    ];
                });

            $groupes[] = [
                'titre' => "{$magasin->libelle} ({$magasin->code})",
                'lignes' => $lignes->all(),
                'total' => ['designation' => 'Total '.$magasin->libelle.' ('.$lignes->count().' articles)', 'stock' => $lignes->sum('stock')],
            ];
        }

        return new Etat(
            titre: 'État de stock par magasin',
            filtres: [
                'Magasins' => $this->libelleMagasins($f['magasins'] ?? [], $magasins),
                'Catégorie' => $this->libelleCategorie($f['categorie_id'] ?? null),
                'Articles sans stock' => ($f['sans_stock'] ?? true) ? 'affichés' : 'masqués',
                'Alertes uniquement' => ($f['alerte'] ?? false) ? 'oui' : 'non',
            ],
            colonnes: ['code' => 'Code', 'designation' => 'Désignation', 'categorie' => 'Catégorie', 'unite' => 'Unité', 'stock' => 'Stock', 'stock_minimum' => 'Stock min', 'alerte' => 'Alerte'],
            numeriques: ['stock', 'stock_minimum'],
            groupes: $groupes,
        );
    }

    /**
     * SFD 9.2 — filtres : magasins[]. Articles en stock regroupés par catégorie.
     */
    public function stockParCategorie(array $f, User $user): Etat
    {
        $magasins = $this->magasinsChoisis($f['magasins'] ?? [], $user);
        $unites = config('referentiel.unites_mesure');

        $lignes = DB::table('stocks as s')
            ->join('articles as a', 'a.id', '=', 's.article_id')
            ->join('categories as c', 'c.id', '=', 'a.categorie_id')
            ->whereIn('s.magasin_id', $magasins->pluck('id'))
            ->groupBy('c.id', 'c.libelle', 'a.id', 'a.code', 'a.designation', 'a.unite_mesure')
            ->havingRaw('SUM(s.quantite) <> 0')
            ->orderBy('c.libelle')->orderBy('a.code')
            ->limit(config('etats.lignes_max'))
            ->get(['c.libelle as categorie', 'a.code', 'a.designation', 'a.unite_mesure', DB::raw('SUM(s.quantite) as quantite')]);

        $groupes = $lignes->groupBy('categorie')->map(fn (Collection $articles, $categorie) => [
            'titre' => "{$categorie} — {$articles->count()} article(s), quantité totale {$articles->sum('quantite')}",
            'lignes' => $articles->map(fn ($r) => [
                'code' => $r->code,
                'designation' => $r->designation,
                'unite' => $unites[$r->unite_mesure] ?? $r->unite_mesure,
                'stock' => (int) $r->quantite,
            ])->all(),
            'total' => ['designation' => "Total {$categorie}", 'stock' => (int) $articles->sum('quantite')],
        ])->values()->all();

        return new Etat(
            titre: 'État de stock par catégorie',
            filtres: ['Magasins' => $this->libelleMagasins($f['magasins'] ?? [], $magasins)],
            colonnes: ['code' => 'Code', 'designation' => 'Désignation', 'unite' => 'Unité', 'stock' => 'Quantité'],
            numeriques: ['stock'],
            groupes: $groupes,
        );
    }

    /**
     * SFD 9.3 — filtres : date_du, date_au, types[], magasin_id, article, beneficiaire, user_id
     */
    public function journal(array $f, User $user): Etat
    {
        $du = $f['date_du'] ?? now()->startOfMonth()->toDateString();
        $au = $f['date_au'] ?? now()->toDateString();
        $types = array_intersect($f['types'] ?? array_keys(self::TYPES), array_keys(self::TYPES));

        $requete = $this->lignesValidees($user)
            ->whereDate('d.date_mouvement', '>=', $du)->whereDate('d.date_mouvement', '<=', $au)
            ->whereIn('d.type', $types ?: ['-'])
            ->when($f['magasin_id'] ?? null, fn ($q, $id) => $q->where('d.magasin_id', $id))
            ->when($f['user_id'] ?? null, fn ($q, $id) => $q->where('d.user_id', $id))
            ->when($f['article'] ?? null, fn ($q, $texte) => $q->where(fn ($w) => $w
                ->whereRaw('LOWER(a.code) LIKE ?', ['%'.mb_strtolower($texte).'%'])
                ->orWhereRaw('LOWER(a.designation) LIKE ?', ['%'.mb_strtolower($texte).'%'])))
            ->when($f['beneficiaire'] ?? null, fn ($q, $texte) => $q->where(fn ($w) => $w
                ->whereRaw('LOWER(un.libelle) LIKE ?', ['%'.mb_strtolower($texte).'%'])
                ->orWhereRaw('LOWER(se.libelle) LIKE ?', ['%'.mb_strtolower($texte).'%'])));

        $lignes = $requete
            ->orderBy('d.date_mouvement')->orderBy('d.validated_at')->orderBy('d.reference')
            ->limit(config('etats.lignes_max'))
            ->get()
            ->map(fn ($r) => [
                'reference' => $r->reference,
                'date' => Carbon::parse($r->date_mouvement)->format('d/m/Y'),
                'type' => self::TYPES[$r->type],
                'magasin' => $r->magasin,
                'article' => "{$r->designation} ({$r->code})",
                'quantite' => $r->sens * $r->quantite,
                'beneficiaire' => $r->unite ?? $r->service ?? '',
                'commentaire' => $r->commentaire,
                'utilisateur' => trim("{$r->prenom} {$r->nom}"),
            ]);

        $utilisateur = isset($f['user_id']) ? User::find($f['user_id'])?->full_name : null;

        return new Etat(
            titre: 'Journal des mouvements',
            filtres: array_filter([
                'Période' => Carbon::parse($du)->format('d/m/Y').' → '.Carbon::parse($au)->format('d/m/Y'),
                'Types' => implode(', ', array_map(fn ($t) => self::TYPES[$t], $types)) ?: 'aucun',
                'Magasin' => isset($f['magasin_id']) ? Magasin::find($f['magasin_id'])?->libelle : 'Tous',
                'Article' => $f['article'] ?? null,
                'Bénéficiaire' => $f['beneficiaire'] ?? null,
                'Utilisateur' => $utilisateur ?? 'Tous',
            ]),
            colonnes: ['reference' => 'N° réf', 'date' => 'Date', 'type' => 'Type', 'magasin' => 'Magasin', 'article' => 'Article', 'quantite' => 'Quantité', 'beneficiaire' => 'Bénéficiaire', 'commentaire' => 'Commentaire', 'utilisateur' => 'Utilisateur'],
            numeriques: ['quantite'],
            groupes: [['titre' => null, 'lignes' => $lignes->all(), 'total' => null]],
        );
    }

    /**
     * SFD 9.4 — filtres : article_id, magasin_id (obligatoires), date_du, date_au
     */
    public function ficheStock(array $f, User $user): ?Etat
    {
        if (empty($f['article_id']) || empty($f['magasin_id'])) {
            return null;
        }

        $article = Article::findOrFail($f['article_id']);
        $magasin = Magasin::findOrFail($f['magasin_id']);
        abort_unless($this->perimetre->peutAcceder($user, $magasin), 403, 'Vous n\'êtes pas rattaché à ce magasin.');

        $du = $f['date_du'] ?? now()->startOfYear()->toDateString();
        $au = $f['date_au'] ?? now()->toDateString();

        $base = fn () => $this->lignesValidees($user)->where('d.magasin_id', $magasin->id)->where('l.article_id', $article->id);
        $stockDebut = (int) $base()->whereDate('d.date_mouvement', '<', $du)->sum(DB::raw('l.sens * l.quantite'));

        $cumul = $stockDebut;
        $lignes = $base()
            ->whereDate('d.date_mouvement', '>=', $du)->whereDate('d.date_mouvement', '<=', $au)
            ->orderBy('d.date_mouvement')->orderBy('d.validated_at')->orderBy('d.id')
            ->limit(config('etats.lignes_max'))
            ->get()
            ->map(function ($r) use (&$cumul) {
                $quantite = $r->sens * $r->quantite;
                $cumul += $quantite;

                return [
                    'date' => Carbon::parse($r->date_mouvement)->format('d/m/Y'),
                    'type' => self::TYPES[$r->type],
                    'reference' => $r->reference,
                    'quantite' => $quantite,
                    'beneficiaire' => $r->unite ?? $r->service ?? '',
                    'cumul' => $cumul,
                ];
            });

        $periode = Carbon::parse($du)->format('d/m/Y').' → '.Carbon::parse($au)->format('d/m/Y');

        return new Etat(
            titre: 'Fiche de stock',
            filtres: ['Article' => "{$article->designation} ({$article->code})", 'Magasin' => $magasin->libelle, 'Période' => $periode],
            colonnes: ['date' => 'Date', 'type' => 'Type', 'reference' => 'N° réf', 'quantite' => 'Quantité', 'beneficiaire' => 'Bénéficiaire', 'cumul' => 'Stock cumulé'],
            numeriques: ['quantite', 'cumul'],
            groupes: [['titre' => null, 'lignes' => $lignes->all(), 'total' => null]],
            entete: [
                'Article' => "{$article->designation} ({$article->code})",
                'Magasin' => "{$magasin->libelle} ({$magasin->code})",
                'Stock au '.Carbon::parse($du)->format('d/m/Y') => $stockDebut,
            ],
            pied: ['Stock au '.Carbon::parse($au)->format('d/m/Y') => $cumul],
        );
    }

    /**
     * Lignes des documents validés (y compris ceux annulés depuis, compensés par leur inverse).
     */
    protected function lignesValidees(User $user): Builder
    {
        return DB::table('lignes_mouvement as l')
            ->join('documents as d', 'd.id', '=', 'l.document_id')
            ->join('articles as a', 'a.id', '=', 'l.article_id')
            ->join('magasins as m', 'm.id', '=', 'd.magasin_id')
            ->join('users as u', 'u.id', '=', 'd.user_id')
            ->leftJoin('services as se', 'se.id', '=', 'd.service_id')
            ->leftJoin('unites as un', 'un.id', '=', 'd.unite_id')
            ->whereNotNull('d.validated_at')
            ->whereIn('d.magasin_id', $this->perimetre->query($user)->select('id'))
            ->select([
                'd.reference', 'd.date_mouvement', 'd.type', 'd.commentaire', 'm.libelle as magasin',
                'a.code', 'a.designation', 'l.quantite', 'l.sens', 'se.libelle as service', 'un.libelle as unite',
                'u.name as prenom', 'u.last_name as nom',
            ]);
    }

    protected function stocksArticles(int $magasinId, array $f): Builder
    {
        return DB::table('articles as a')
            ->join('categories as c', 'c.id', '=', 'a.categorie_id')
            ->leftJoin('stocks as s', fn ($j) => $j->on('s.article_id', '=', 'a.id')->where('s.magasin_id', $magasinId))
            ->where(fn ($q) => $q->where('a.actif', true)->orWhere('s.quantite', '!=', 0))
            ->when($f['categorie_id'] ?? null, fn ($q, $id) => $q->where('a.categorie_id', $id))
            ->when(! ($f['sans_stock'] ?? true), fn ($q) => $q->whereRaw('COALESCE(s.quantite, 0) <> 0'))
            ->when($f['alerte'] ?? false, fn ($q) => $q->whereNotNull('a.stock_minimum')->whereRaw('COALESCE(s.quantite, 0) <= a.stock_minimum'))
            ->select(['a.code', 'a.designation', 'a.unite_mesure', 'a.stock_minimum', 'c.libelle as categorie', DB::raw('COALESCE(s.quantite, 0) as quantite')]);
    }

    protected function magasinsChoisis(array $ids, User $user): Collection
    {
        $ids = array_filter(array_map('intval', $ids));

        return $this->perimetre->query($user)
            ->when($ids, fn ($q) => $q->whereIn('id', $ids))
            ->orderBy('libelle')
            ->get();
    }

    protected function libelleMagasins(array $ids, Collection $magasins): string
    {
        return array_filter($ids) ? $magasins->pluck('libelle')->implode(', ') : 'Tous';
    }

    protected function libelleCategorie(?string $id): string
    {
        return $id ? (Categorie::find($id)?->libelle ?? '—') : 'Toutes';
    }
}
