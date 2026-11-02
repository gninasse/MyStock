<?php

namespace Modules\Etats\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;
use Modules\Referentiel\Services\Parametres;
use Modules\Referentiel\Services\PerimetreMagasins;
use Modules\Stock\Models\Document;

/**
 * Indicateurs et séries de la page Statistiques, sur la période choisie et comparés à la
 * période précédente de même durée. Quantités en unités de gestion des articles.
 */
class StatistiquesService
{
    public const PERIODES = [
        '30j' => '30 derniers jours',
        '90j' => '3 derniers mois',
        '6m' => '6 derniers mois',
        '12m' => '12 derniers mois',
        'annee' => 'Année en cours',
    ];

    public function __construct(protected PerimetreMagasins $perimetre, protected AnalysesService $analyses) {}

    /**
     * Filtres : periode (clé de PERIODES), magasin_id.
     */
    public function tableau(array $f, User $user): array
    {
        [$du, $au] = $this->bornes($f['periode'] ?? '90j');
        $duree = $du->diffInDays($au) + 1;
        [$duPrec, $auPrec] = [$du->copy()->subDays($duree), $du->copy()->subDay()];
        $magasins = $this->perimetre->query($user)
            ->when($f['magasin_id'] ?? null, fn ($q, $id) => $q->whereKey($id))
            ->orderBy('libelle')->get(['id', 'libelle', 'code']);
        $ids = $magasins->pluck('id');

        $consommation = fn (Carbon $a, Carbon $b) => $this->analyses->consommations($user)->whereIn('d.magasin_id', $ids)
            ->whereDate('d.date_mouvement', '>=', $a->toDateString())->whereDate('d.date_mouvement', '<=', $b->toDateString());

        $actuelle = $consommation($du, $au)->selectRaw('COALESCE(SUM(l.quantite), 0) as quantite, COUNT(DISTINCT d.id) as bons, COUNT(DISTINCT l.article_id) as articles, COUNT(DISTINCT d.service_id) as services')->first();
        $precedente = $consommation($duPrec, $auPrec)->selectRaw('COALESCE(SUM(l.quantite), 0) as quantite, COUNT(DISTINCT d.id) as bons')->first();

        return [
            'periode' => [
                'cle' => $f['periode'] ?? '90j',
                'libelle' => self::PERIODES[$f['periode'] ?? '90j'],
                'du' => $du->format('d/m/Y'),
                'au' => $au->format('d/m/Y'),
                'precedente' => $duPrec->format('d/m/Y').' – '.$auPrec->format('d/m/Y'),
            ],
            'magasins' => $magasins->pluck('libelle')->all(),
            'kpi' => [
                'consommation' => $this->comparaison((int) $actuelle->quantite, (int) $precedente->quantite),
                'bons' => $this->comparaison((int) $actuelle->bons, (int) $precedente->bons),
                'articles' => (int) $actuelle->articles,
                'services' => (int) $actuelle->services,
                'transferts' => $this->transferts($ids, $du, $au),
                'alertes' => $this->sante($magasins)['totaux'],
                'fiabilite' => $this->fiabilite($ids),
                'peremptions' => Parametres::get('lots_actifs') ? $this->peremptions($ids) : null,
            ],
            'evolution' => $this->evolution($user, $ids, $du, $au),
            'top_articles' => $consommation($du, $au)
                ->groupBy('a.id', 'a.code', 'a.designation', 'a.unite_mesure')
                ->orderByRaw('SUM(l.quantite) DESC')->limit(10)
                ->get(['a.designation', 'a.unite_mesure', DB::raw('SUM(l.quantite) as quantite')])
                ->map(fn ($r) => ['libelle' => $r->designation, 'quantite' => (int) $r->quantite, 'unite' => config('referentiel.unites_mesure')[$r->unite_mesure] ?? $r->unite_mesure])
                ->all(),
            'services' => $this->parService($consommation($du, $au)),
            'sante' => $this->sante($magasins),
            'flux' => $this->flux($ids, $du, $au),
        ];
    }

    /** @return array{0: Carbon, 1: Carbon} */
    public function bornes(string $periode): array
    {
        $au = now()->endOfDay();

        return [match ($periode) {
            '30j' => now()->subDays(29)->startOfDay(),
            '6m' => now()->subMonthsNoOverflow(6)->addDay()->startOfDay(),
            '12m' => now()->subMonthsNoOverflow(12)->addDay()->startOfDay(),
            'annee' => now()->startOfYear(),
            default => now()->subMonthsNoOverflow(3)->addDay()->startOfDay(),
        }, $au];
    }

    protected function comparaison(int $valeur, int $precedent): array
    {
        return [
            'valeur' => $valeur,
            'precedent' => $precedent,
            'variation' => $precedent > 0 ? round(($valeur - $precedent) * 100 / $precedent, 1) : null,
        ];
    }

    /**
     * Entrées, consommation et transferts expédiés regroupés par jour, semaine ou mois selon la durée.
     */
    protected function evolution(User $user, Collection $ids, Carbon $du, Carbon $au): array
    {
        $duree = $du->diffInDays($au);
        [$pas, $format, $cle] = match (true) {
            $duree <= 31 => ['day', 'd/m', fn (Carbon $d) => $d->format('Y-m-d')],
            $duree <= 120 => ['week', 'd/m', fn (Carbon $d) => $d->copy()->startOfWeek()->format('Y-m-d')],
            default => ['month', 'M Y', fn (Carbon $d) => $d->format('Y-m')],
        };

        $periodes = [];
        $debut = match ($pas) {
            'week' => $du->copy()->startOfWeek(), 'month' => $du->copy()->startOfMonth(), default => $du->copy()
        };
        for ($d = $debut; $d->lte($au); $d->add(1, $pas)) {
            $periodes[$cle($d)] = ucfirst($d->copy()->locale('fr')->translatedFormat($format));
        }
        // Dernière semaine ou dernier mois incomplet : signalé pour ne pas lire la baisse comme une chute
        if ($pas !== 'day' && $au->copy()->endOf($pas)->gt(now()->endOfDay())) {
            $periodes[array_key_last($periodes)] .= ' (en cours)';
        }

        $parDate = fn ($requete) => $requete
            ->whereDate('d.date_mouvement', '>=', $du->toDateString())->whereDate('d.date_mouvement', '<=', $au->toDateString())
            ->groupBy('d.date_mouvement')
            ->get(['d.date_mouvement', DB::raw('SUM(l.quantite) as quantite')])
            ->groupBy(fn ($r) => $cle(Carbon::parse($r->date_mouvement)))
            ->map(fn ($g) => (int) $g->sum('quantite'));

        $mouvements = fn (string $type) => DB::table('lignes_mouvement as l')
            ->join('documents as d', 'd.id', '=', 'l.document_id')
            ->whereIn('d.magasin_id', $ids)->where('d.type', $type)->whereNotNull('d.validated_at')
            ->whereNull('d.document_annule_id')->where('d.statut', '!=', Document::ANNULE);

        $series = [
            'entrees' => $parDate($mouvements(Document::ENTREE)),
            'consommation' => $parDate($this->analyses->consommations($user)->whereIn('d.magasin_id', $ids)),
            'transferts' => $parDate($mouvements(Document::TRANSFERT)),
        ];

        return [
            'libelles' => array_values($periodes),
            'series' => collect($series)->map(fn ($valeurs) => array_map(fn ($k) => $valeurs[$k] ?? 0, array_keys($periodes)))->all(),
        ];
    }

    /** Six premiers services, le reste regroupé. */
    protected function parService($requete): array
    {
        $lignes = $requete->join('services as se', 'se.id', '=', 'd.service_id')
            ->groupBy('se.id', 'se.libelle')->orderByRaw('SUM(l.quantite) DESC')
            ->get(['se.libelle', DB::raw('SUM(l.quantite) as quantite')]);
        $tete = $lignes->take(6)->map(fn ($r) => ['libelle' => $r->libelle, 'quantite' => (int) $r->quantite]);
        $reste = (int) $lignes->slice(6)->sum('quantite');

        return $tete->when($reste > 0, fn ($c) => $c->push(['libelle' => 'Autres services', 'quantite' => $reste]))->values()->all();
    }

    /**
     * Santé du stock par magasin : articles suivis en rupture, sous le seuil, au seuil ou normaux.
     */
    protected function sante(Collection $magasins): array
    {
        $lignes = DB::table('stocks as s')
            ->join('articles as a', 'a.id', '=', 's.article_id')
            ->whereIn('s.magasin_id', $magasins->pluck('id'))->where('a.actif', true)
            ->get(['s.magasin_id', 's.quantite', 'a.stock_minimum'])
            ->groupBy('magasin_id');

        $resultat = ['magasins' => [], 'rupture' => [], 'sous' => [], 'seuil' => [], 'normal' => []];
        foreach ($magasins as $magasin) {
            $compte = ['rupture' => 0, 'sous' => 0, 'seuil' => 0, 'normal' => 0];
            foreach ($lignes[$magasin->id] ?? [] as $l) {
                $niveau = AlerteStockService::niveau((int) $l->quantite, $l->stock_minimum);
                $compte[match (true) {
                    $l->quantite <= 0 => 'rupture',
                    $niveau === 'sous' => 'sous',
                    $niveau === 'egal' => 'seuil',
                    default => 'normal',
                }]++;
            }
            $resultat['magasins'][] = $magasin->libelle;
            foreach ($compte as $cle => $nombre) {
                $resultat[$cle][] = $nombre;
            }
        }
        $resultat['totaux'] = [
            'rupture' => array_sum($resultat['rupture']),
            'sous' => array_sum($resultat['sous']),
            'seuil' => array_sum($resultat['seuil']),
        ];

        return $resultat;
    }

    /**
     * Transferts expédiés sur la période : nombre, lignes reçues sans écart, délai moyen de réception.
     */
    protected function transferts(Collection $ids, Carbon $du, Carbon $au): array
    {
        $documents = Document::where('type', Document::TRANSFERT)->whereNotNull('validated_at')
            ->where(fn ($q) => $q->whereIn('magasin_id', $ids)->orWhereIn('magasin_destination_id', $ids))
            ->whereBetween('date_mouvement', [$du->toDateString(), $au->toDateString()])
            ->get(['id', 'statut', 'validated_at', 'recu_at']);

        $lignes = DB::table('lignes_mouvement')->whereIn('document_id', $documents->where('statut', Document::VALIDE)->pluck('id'))
            ->whereNotNull('quantite_recue')->get(['quantite', 'quantite_recue']);
        $recus = $documents->whereNotNull('recu_at');

        return [
            'nombre' => $documents->where('statut', '!=', Document::ANNULE)->count(),
            'en_transit' => $documents->where('statut', Document::EN_TRANSIT)->count(),
            'conformite' => $lignes->count() ? round($lignes->filter(fn ($l) => $l->quantite_recue >= $l->quantite)->count() * 100 / $lignes->count(), 1) : null,
            'delai_heures' => $recus->count() ? round($recus->avg(fn ($d) => $d->validated_at->diffInMinutes($d->recu_at)) / 60, 1) : null,
        ];
    }

    /** Fiabilité des inventaires des 12 derniers mois : part des lignes comptées sans écart. */
    protected function fiabilite(Collection $ids): ?float
    {
        $r = DB::table('lignes_inventaire as li')->join('inventaires as i', 'i.id', '=', 'li.inventaire_id')
            ->whereIn('i.magasin_id', $ids)->whereDate('i.date_inventaire', '>=', now()->subYear()->toDateString())
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN li.ecart = 0 THEN 1 ELSE 0 END) as conformes')->first();

        return $r->total ? round($r->conformes * 100 / $r->total, 1) : null;
    }

    protected function peremptions(Collection $ids): int
    {
        return DB::table('stocks_lots as s')->join('lots as l', 'l.id', '=', 's.lot_id')
            ->whereIn('s.magasin_id', $ids)->where('s.quantite', '>', 0)->whereNotNull('l.date_peremption')
            ->whereDate('l.date_peremption', '<=', now()->addDays((int) Parametres::get('peremption_alerte_jours'))->toDateString())
            ->count();
    }

    /** Flux entre magasins : transferts, quantités expédiées, écarts de réception. */
    protected function flux(Collection $ids, Carbon $du, Carbon $au): array
    {
        return DB::table('lignes_mouvement as l')
            ->join('documents as d', 'd.id', '=', 'l.document_id')
            ->join('magasins as ms', 'ms.id', '=', 'd.magasin_id')
            ->join('magasins as md', 'md.id', '=', 'd.magasin_destination_id')
            ->where('d.type', Document::TRANSFERT)->whereNotNull('d.validated_at')->where('d.statut', '!=', Document::ANNULE)
            ->where(fn ($q) => $q->whereIn('d.magasin_id', $ids)->orWhereIn('d.magasin_destination_id', $ids))
            ->whereDate('d.date_mouvement', '>=', $du->toDateString())->whereDate('d.date_mouvement', '<=', $au->toDateString())
            ->groupBy('ms.libelle', 'md.libelle')
            ->orderByRaw('COUNT(DISTINCT d.id) DESC')
            ->get(['ms.libelle as source', 'md.libelle as destination', DB::raw('COUNT(DISTINCT d.id) as transferts'),
                DB::raw('SUM(l.quantite) as quantite'), DB::raw('SUM(COALESCE(l.quantite_recue, l.quantite)) as recu')])
            ->map(fn ($r) => [
                'source' => $r->source,
                'destination' => $r->destination,
                'transferts' => (int) $r->transferts,
                'quantite' => (int) $r->quantite,
                'ecart' => (int) $r->recu - (int) $r->quantite,
            ])->all();
    }
}
