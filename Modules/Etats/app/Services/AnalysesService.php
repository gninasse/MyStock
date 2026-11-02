<?php

namespace Modules\Etats\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;
use Modules\Etats\Support\Etat;
use Modules\Referentiel\Models\Categorie;
use Modules\Referentiel\Models\Direction;
use Modules\Referentiel\Models\Magasin;
use Modules\Referentiel\Services\PerimetreMagasins;
use Modules\Stock\Models\Document;

/**
 * États d'analyse : consommation par service, couverture et risque de rupture,
 * articles dormants, écarts d'inventaire. Toujours restreints aux magasins visibles.
 *
 * Conventions de calcul :
 * - consommation = sorties validées vers les services, hors sorties annulées et hors
 *   sorties qui annulent une entrée ;
 * - sorties de stock d'un magasin (couverture, dormants) = consommation + transferts
 *   expédiés − réintégrations (un transfert annulé se compense donc à zéro).
 */
class AnalysesService
{
    /** Sorties de stock nettes (voir l'en-tête de classe) : quantité positive = sortie. */
    public const SOMME_SORTIES = "SUM(CASE WHEN d.type = 'REINTEGRATION' THEN -l.quantite ELSE l.quantite END)";

    public function __construct(protected PerimetreMagasins $perimetre) {}

    /**
     * Filtres : date_du, date_au, magasin_id, direction_id, categorie_id.
     */
    public function consommationParService(array $f, User $user): Etat
    {
        [$du, $au] = $this->periode($f, now()->subMonthsNoOverflow(2)->startOfMonth());
        $unites = config('referentiel.unites_mesure');

        $lignes = $this->consommations($user)
            ->join('services as se', 'se.id', '=', 'd.service_id')
            ->join('directions as di', 'di.id', '=', 'd.direction_id')
            ->whereDate('d.date_mouvement', '>=', $du)->whereDate('d.date_mouvement', '<=', $au)
            ->when($f['magasin_id'] ?? null, fn ($q, $id) => $q->where('d.magasin_id', $id))
            ->when($f['direction_id'] ?? null, fn ($q, $id) => $q->where('d.direction_id', $id))
            ->when($f['categorie_id'] ?? null, fn ($q, $id) => $q->where('a.categorie_id', $id))
            ->groupBy('di.libelle', 'se.id', 'se.libelle', 'a.id', 'a.code', 'a.designation', 'a.unite_mesure')
            ->get(['di.libelle as direction', 'se.libelle as service', 'a.code', 'a.designation', 'a.unite_mesure',
                DB::raw('SUM(l.quantite) as quantite'), DB::raw('COUNT(DISTINCT d.id) as bons')]);

        $groupes = $lignes->groupBy(fn ($r) => "{$r->direction} / {$r->service}")
            ->map(function (Collection $articles, $service) use ($unites) {
                $total = (int) $articles->sum('quantite');

                return [
                    'titre' => $service,
                    'total_tri' => $total,
                    'lignes' => $articles->sortByDesc('quantite')->map(fn ($r) => [
                        'code' => $r->code,
                        'designation' => $r->designation,
                        'unite' => $unites[$r->unite_mesure] ?? $r->unite_mesure,
                        'quantite' => (int) $r->quantite,
                        'bons' => (int) $r->bons,
                        'part' => $this->pourcentage((int) $r->quantite, $total),
                    ])->values()->all(),
                    'total' => ['designation' => "Total {$service}", 'quantite' => $total],
                ];
            })
            ->sortByDesc('total_tri')->values()->all();

        return new Etat(
            titre: 'Consommation par service',
            filtres: [
                'Période' => $this->libellePeriode($du, $au),
                'Magasin' => $this->libelle(Magasin::class, $f['magasin_id'] ?? null, 'Tous'),
                'Direction' => $this->libelle(Direction::class, $f['direction_id'] ?? null, 'Toutes'),
                'Catégorie' => $this->libelle(Categorie::class, $f['categorie_id'] ?? null, 'Toutes'),
            ],
            colonnes: ['code' => 'Code', 'designation' => 'Désignation', 'unite' => 'Unité', 'quantite' => 'Quantité consommée', 'bons' => 'Bons de sortie', 'part' => 'Part du service'],
            numeriques: ['quantite', 'bons', 'part'],
            groupes: $groupes,
            pied: ['Quantité totale consommée' => $this->nombre($lignes->sum('quantite')), 'Services servis' => count($groupes)],
        );
    }

    /**
     * Filtres : magasin_id, categorie_id, jours (période d'analyse), cible (couverture visée en jours), a_risque (bool).
     */
    public function couverture(array $f, User $user): Etat
    {
        $jours = (int) ($f['jours'] ?? 90);
        $cible = (int) ($f['cible'] ?? 30);
        $unites = config('referentiel.unites_mesure');
        $magasins = $this->magasins($user, $f['magasin_id'] ?? null);

        $stocks = DB::table('stocks as s')
            ->join('articles as a', 'a.id', '=', 's.article_id')
            ->whereIn('s.magasin_id', $magasins->pluck('id'))
            ->when($f['categorie_id'] ?? null, fn ($q, $id) => $q->where('a.categorie_id', $id))
            ->get(['s.magasin_id', 'a.id as article_id', 'a.code', 'a.designation', 'a.unite_mesure', 's.quantite'])
            ->keyBy(fn ($r) => "{$r->magasin_id}-{$r->article_id}");

        $sorties = $this->sortiesDeStock($magasins->pluck('id'))
            ->whereDate('d.date_mouvement', '>', now()->subDays($jours)->toDateString())
            ->when($f['categorie_id'] ?? null, fn ($q, $id) => $q->where('a.categorie_id', $id))
            ->groupBy('d.magasin_id', 'a.id', 'a.code', 'a.designation', 'a.unite_mesure')
            ->get(['d.magasin_id', 'a.id as article_id', 'a.code', 'a.designation', 'a.unite_mesure', DB::raw(self::SOMME_SORTIES.' as sorties')])
            ->keyBy(fn ($r) => "{$r->magasin_id}-{$r->article_id}");

        $groupes = [];
        foreach ($magasins as $magasin) {
            $cles = $stocks->keys()->merge($sorties->keys())->unique()->filter(fn ($c) => str_starts_with($c, "{$magasin->id}-"));
            $lignes = $cles->map(function ($cle) use ($stocks, $sorties, $jours, $cible, $unites) {
                $article = $stocks[$cle] ?? $sorties[$cle];
                $stock = (int) ($stocks[$cle]->quantite ?? 0);
                $sortie = max(0, (int) ($sorties[$cle]->sorties ?? 0));
                if ($stock === 0 && $sortie === 0) {
                    return null;
                }
                $cmj = $sortie / $jours;
                $couverture = $cmj > 0 ? (int) floor(max(0, $stock) / $cmj) : null;

                return [
                    'code' => $article->code,
                    'designation' => $article->designation,
                    'unite' => $unites[$article->unite_mesure] ?? $article->unite_mesure,
                    'stock' => $stock,
                    'sorties' => $sortie,
                    'cmj' => $cmj > 0 ? number_format($cmj, $cmj < 10 ? 2 : 1, ',', ' ') : '—',
                    'couverture' => $couverture ?? 'Sans sortie',
                    'rupture' => match (true) {
                        $couverture === null => '—',
                        $couverture === 0 => 'En rupture',
                        default => now()->addDays($couverture)->format('d/m/Y'),
                    },
                    'a_commander' => $cmj > 0 ? max(0, (int) ceil($cmj * $cible - max(0, $stock))) : 0,
                    '_tri' => $couverture ?? PHP_INT_MAX,
                    '_classe' => match (true) {
                        $couverture === null => null,
                        $couverture < min(7, $cible) => 'alerte-sous',
                        $couverture < $cible => 'alerte-egal',
                        default => null,
                    },
                ];
            })->filter()
                ->when($f['a_risque'] ?? false, fn ($c) => $c->filter(fn ($l) => $l['_tri'] < $cible))
                ->sortBy([['_tri', 'asc'], ['designation', 'asc']])
                ->values();

            if ($lignes->isNotEmpty() || ! ($f['a_risque'] ?? false)) {
                $groupes[] = [
                    'titre' => "{$magasin->libelle} ({$magasin->code})",
                    'lignes' => $lignes->all(),
                    'total' => ['designation' => $lignes->where('_tri', '<', $cible)->count().' article(s) sous la couverture visée'],
                ];
            }
        }

        return new Etat(
            titre: 'Couverture de stock et risque de rupture',
            filtres: [
                'Période d\'analyse' => "{$jours} derniers jours",
                'Couverture visée' => "{$cible} jours",
                'Magasin' => $this->libelle(Magasin::class, $f['magasin_id'] ?? null, 'Tous'),
                'Catégorie' => $this->libelle(Categorie::class, $f['categorie_id'] ?? null, 'Toutes'),
                'Articles à risque uniquement' => ($f['a_risque'] ?? false) ? 'oui' : 'non',
            ],
            colonnes: ['code' => 'Code', 'designation' => 'Désignation', 'unite' => 'Unité', 'stock' => 'Stock', 'sorties' => "Sorties {$jours} j",
                'cmj' => 'Sorties / jour', 'couverture' => 'Couverture (j)', 'rupture' => 'Rupture estimée', 'a_commander' => 'À commander'],
            numeriques: ['stock', 'sorties', 'cmj', 'couverture', 'a_commander'],
            groupes: $groupes,
        );
    }

    /**
     * Articles en stock sans aucune sortie depuis N jours. Filtres : magasin_id, categorie_id, jours.
     */
    public function dormants(array $f, User $user): Etat
    {
        $jours = (int) ($f['jours'] ?? 90);
        $limite = now()->subDays($jours)->startOfDay();
        $unites = config('referentiel.unites_mesure');
        $magasins = $this->magasins($user, $f['magasin_id'] ?? null);

        $derniereSortie = $this->sortiesDeStock($magasins->pluck('id'))
            ->where('d.type', '!=', Document::REINTEGRATION)
            ->groupBy('d.magasin_id', 'l.article_id')
            ->get(['d.magasin_id', 'l.article_id', DB::raw('MAX(d.date_mouvement) as derniere')])
            ->keyBy(fn ($r) => "{$r->magasin_id}-{$r->article_id}");

        $derniereEntree = DB::table('lignes_mouvement as l')
            ->join('documents as d', 'd.id', '=', 'l.document_id')
            ->whereNotNull('d.validated_at')->where('l.sens', 1)
            ->whereIn('d.magasin_id', $magasins->pluck('id'))
            ->groupBy('d.magasin_id', 'l.article_id')
            ->get(['d.magasin_id', 'l.article_id', DB::raw('MAX(d.date_mouvement) as derniere')])
            ->keyBy(fn ($r) => "{$r->magasin_id}-{$r->article_id}");

        $lignes = DB::table('stocks as s')
            ->join('articles as a', 'a.id', '=', 's.article_id')
            ->join('categories as c', 'c.id', '=', 'a.categorie_id')
            ->join('magasins as m', 'm.id', '=', 's.magasin_id')
            ->whereIn('s.magasin_id', $magasins->pluck('id'))
            ->where('s.quantite', '>', 0)
            ->when($f['categorie_id'] ?? null, fn ($q, $id) => $q->where('a.categorie_id', $id))
            ->get(['s.magasin_id', 'm.libelle as magasin', 'a.id as article_id', 'a.code', 'a.designation', 'a.unite_mesure', 'c.libelle as categorie', 's.quantite'])
            ->map(function ($r) use ($derniereSortie, $derniereEntree, $limite, $unites, $jours) {
                $cle = "{$r->magasin_id}-{$r->article_id}";
                $sortie = isset($derniereSortie[$cle]) ? Carbon::parse($derniereSortie[$cle]->derniere) : null;
                $entree = isset($derniereEntree[$cle]) ? Carbon::parse($derniereEntree[$cle]->derniere) : null;
                // Inactivité mesurée depuis la dernière sortie, ou depuis l'entrée pour un article jamais sorti :
                // un article tout juste reçu n'est pas dormant
                $depuis = $sortie ?? $entree;
                if ($depuis && $depuis->gte($limite->copy()->addDay())) {
                    return null;
                }
                $inactivite = $depuis ? (int) $depuis->copy()->startOfDay()->diffInDays(now()->startOfDay()) : null;

                return [
                    'magasin' => $r->magasin,
                    'code' => $r->code,
                    'designation' => $r->designation,
                    'categorie' => $r->categorie,
                    'unite' => $unites[$r->unite_mesure] ?? $r->unite_mesure,
                    'stock' => (int) $r->quantite,
                    'derniere_entree' => $entree?->format('d/m/Y') ?? '—',
                    'derniere_sortie' => $sortie?->format('d/m/Y') ?? 'Jamais',
                    'inactivite' => $inactivite ?? '—',
                    '_tri' => $inactivite ?? PHP_INT_MAX,
                    '_classe' => ($sortie === null || $inactivite >= 2 * $jours) ? 'alerte-sous' : 'alerte-egal',
                ];
            })->filter()->sortByDesc('_tri')->values();

        return new Etat(
            titre: 'Articles dormants',
            filtres: [
                'Sans sortie depuis' => "{$jours} jours",
                'Magasin' => $this->libelle(Magasin::class, $f['magasin_id'] ?? null, 'Tous'),
                'Catégorie' => $this->libelle(Categorie::class, $f['categorie_id'] ?? null, 'Toutes'),
            ],
            colonnes: ['magasin' => 'Magasin', 'code' => 'Code', 'designation' => 'Désignation', 'categorie' => 'Catégorie', 'unite' => 'Unité',
                'stock' => 'Stock', 'derniere_entree' => 'Dernière entrée', 'derniere_sortie' => 'Dernière sortie', 'inactivite' => 'Jours sans sortie'],
            numeriques: ['stock', 'inactivite'],
            groupes: [['titre' => null, 'lignes' => $lignes->all(), 'total' => null]],
            pied: ['Articles dormants' => $lignes->count(), 'Jamais sortis' => $lignes->where('derniere_sortie', 'Jamais')->count()],
        );
    }

    /**
     * Écarts des inventaires validés. Filtres : date_du, date_au, magasin_id, categorie_id.
     */
    public function ecartsInventaire(array $f, User $user): Etat
    {
        [$du, $au] = $this->periode($f, now()->subYear()->startOfMonth());
        $unites = config('referentiel.unites_mesure');

        $requete = DB::table('lignes_inventaire as li')
            ->join('inventaires as i', 'i.id', '=', 'li.inventaire_id')
            ->join('articles as a', 'a.id', '=', 'li.article_id')
            ->join('categories as c', 'c.id', '=', 'a.categorie_id')
            ->join('magasins as m', 'm.id', '=', 'i.magasin_id')
            ->whereIn('i.magasin_id', $this->perimetre->query($user)->select('id'))
            ->whereDate('i.date_inventaire', '>=', $du)->whereDate('i.date_inventaire', '<=', $au)
            ->when($f['magasin_id'] ?? null, fn ($q, $id) => $q->where('i.magasin_id', $id))
            ->when($f['categorie_id'] ?? null, fn ($q, $id) => $q->where('a.categorie_id', $id));

        $comptes = (clone $requete)->count();
        $lignes = (clone $requete)->where('li.ecart', '!=', 0)
            ->orderBy('m.libelle')->orderByDesc('i.date_inventaire')->orderBy('a.code')
            ->get(['m.libelle as magasin', 'm.code as code_magasin', 'i.date_inventaire', 'a.code', 'a.designation', 'a.unite_mesure', 'c.libelle as categorie',
                'li.stock_theorique', 'li.quantite_reelle', 'li.ecart']);

        $groupes = $lignes->groupBy(fn ($r) => "{$r->magasin} ({$r->code_magasin})")->map(fn (Collection $ecarts, $magasin) => [
            'titre' => $magasin,
            'lignes' => $ecarts->map(fn ($r) => [
                'date' => Carbon::parse($r->date_inventaire)->format('d/m/Y'),
                'code' => $r->code,
                'designation' => $r->designation,
                'categorie' => $r->categorie,
                'unite' => $unites[$r->unite_mesure] ?? $r->unite_mesure,
                'theorique' => (int) $r->stock_theorique,
                'compte' => (int) $r->quantite_reelle,
                'ecart' => ($r->ecart > 0 ? '+' : '').$r->ecart,
                'taux' => $r->stock_theorique > 0 ? $this->pourcentage((int) $r->ecart, (int) $r->stock_theorique, true) : '—',
                '_classe' => $r->ecart < 0 ? 'alerte-sous' : 'alerte-egal',
            ])->all(),
            'total' => ['designation' => 'Excédents +'.$ecarts->where('ecart', '>', 0)->sum('ecart').' · Manquants '.$ecarts->where('ecart', '<', 0)->sum('ecart')],
        ])->values()->all();

        return new Etat(
            titre: 'Écarts d\'inventaire',
            filtres: [
                'Période' => $this->libellePeriode($du, $au),
                'Magasin' => $this->libelle(Magasin::class, $f['magasin_id'] ?? null, 'Tous'),
                'Catégorie' => $this->libelle(Categorie::class, $f['categorie_id'] ?? null, 'Toutes'),
            ],
            colonnes: ['date' => 'Inventaire du', 'code' => 'Code', 'designation' => 'Désignation', 'categorie' => 'Catégorie', 'unite' => 'Unité',
                'theorique' => 'Théorique', 'compte' => 'Compté', 'ecart' => 'Écart', 'taux' => 'Écart %'],
            numeriques: ['theorique', 'compte', 'ecart', 'taux'],
            groupes: $groupes,
            pied: [
                'Lignes comptées' => $comptes,
                'Lignes en écart' => $lignes->count(),
                'Fiabilité du stock' => $comptes ? $this->pourcentage($comptes - $lignes->count(), $comptes) : '—',
            ],
        );
    }

    /**
     * Lignes qui font sortir du stock d'un magasin : consommation, transferts expédiés, réintégrations (à soustraire).
     */
    public function sortiesDeStock(Collection|array $magasinIds): Builder
    {
        return DB::table('lignes_mouvement as l')
            ->join('documents as d', 'd.id', '=', 'l.document_id')
            ->join('articles as a', 'a.id', '=', 'l.article_id')
            ->whereIn('d.magasin_id', $magasinIds)
            ->whereNotNull('d.validated_at')
            ->where(fn ($q) => $q
                ->where(fn ($s) => $s->where('d.type', Document::SORTIE)->where('d.statut', Document::VALIDE)->whereNull('d.document_annule_id'))
                ->orWhere('d.type', Document::TRANSFERT)
                ->orWhere('d.type', Document::REINTEGRATION));
    }

    /**
     * Lignes de consommation : sorties validées vers un service, hors annulations.
     */
    public function consommations(User $user): Builder
    {
        return DB::table('lignes_mouvement as l')
            ->join('documents as d', 'd.id', '=', 'l.document_id')
            ->join('articles as a', 'a.id', '=', 'l.article_id')
            ->where('d.type', Document::SORTIE)
            ->where('d.statut', Document::VALIDE)
            ->whereNull('d.document_annule_id')
            ->whereNotNull('d.validated_at')
            ->whereIn('d.magasin_id', $this->perimetre->query($user)->select('id'));
    }

    protected function magasins(User $user, $id): Collection
    {
        return $this->perimetre->query($user)->when($id, fn ($q) => $q->whereKey($id))->orderBy('libelle')->get();
    }

    /** @return array{0: string, 1: string} */
    protected function periode(array $f, Carbon $debutParDefaut): array
    {
        return [$f['date_du'] ?? $debutParDefaut->toDateString(), $f['date_au'] ?? now()->toDateString()];
    }

    protected function libellePeriode(string $du, string $au): string
    {
        return Carbon::parse($du)->format('d/m/Y').' → '.Carbon::parse($au)->format('d/m/Y');
    }

    protected function libelle(string $modele, $id, string $defaut): string
    {
        return $id ? ($modele::find($id)?->libelle ?? '—') : $defaut;
    }

    protected function pourcentage(int $valeur, int $total, bool $signe = false): string
    {
        if ($total === 0) {
            return '—';
        }
        $taux = round($valeur * 100 / $total, 1);

        return ($signe && $taux > 0 ? '+' : '').number_format($taux, 1, ',', ' ').' %';
    }

    protected function nombre(int|float $n): string
    {
        return number_format($n, 0, ',', ' ');
    }
}
