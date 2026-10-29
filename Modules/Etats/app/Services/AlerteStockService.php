<?php

namespace Modules\Etats\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;
use Modules\Referentiel\Services\PerimetreMagasins;

/**
 * Articles sous le seuil minimum (SFD 5.2.4 et 12.4).
 * Un couple article / magasin est surveillé dès que l'article a déjà eu du
 * stock dans ce magasin ou fait partie de ses articles fréquents : sinon
 * chaque article à seuil serait en alerte dans tous les magasins.
 */
class AlerteStockService
{
    public function __construct(protected PerimetreMagasins $perimetre) {}

    public function alertes(User $user, ?int $limite = null): Collection
    {
        return $this->requete($user)
            ->orderByRaw('COALESCE(s.quantite, 0) - a.stock_minimum')
            ->orderBy('a.designation')
            ->when($limite, fn ($q) => $q->limit($limite))
            ->get([
                'm.id as magasin_id', 'm.libelle as magasin', 'a.id as article_id', 'a.code', 'a.designation',
                'a.unite_mesure', 'a.stock_minimum', DB::raw('COALESCE(s.quantite, 0) as quantite'),
            ])
            ->map(function ($alerte) {
                $alerte->quantite = (int) $alerte->quantite;
                $alerte->niveau = self::niveau($alerte->quantite, $alerte->stock_minimum);

                return $alerte;
            });
    }

    public function nombre(User $user): int
    {
        return $this->requete($user)->count();
    }

    /**
     * « sous » (rouge) si stock < minimum, « egal » (orange) si stock = minimum, null sinon.
     */
    public static function niveau(int $quantite, ?int $minimum): ?string
    {
        if ($minimum === null || $quantite > $minimum) {
            return null;
        }

        return $quantite < $minimum ? 'sous' : 'egal';
    }

    protected function requete(User $user)
    {
        $couples = DB::table('stocks')->select('magasin_id', 'article_id')
            ->union(DB::table('articles_frequents')->select('magasin_id', 'article_id'));

        return DB::query()->fromSub($couples, 'p')
            ->join('articles as a', 'a.id', '=', 'p.article_id')
            ->join('magasins as m', 'm.id', '=', 'p.magasin_id')
            ->leftJoin('stocks as s', fn ($j) => $j->on('s.magasin_id', '=', 'p.magasin_id')->on('s.article_id', '=', 'p.article_id'))
            ->where('a.actif', true)
            ->where('m.actif', true)
            ->whereNotNull('a.stock_minimum')
            ->whereRaw('COALESCE(s.quantite, 0) <= a.stock_minimum')
            ->whereIn('p.magasin_id', $this->perimetre->query($user)->select('id'));
    }
}
