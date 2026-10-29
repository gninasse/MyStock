<?php

namespace Modules\Referentiel\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Referentiel\Models\Article;
use Modules\Referentiel\Models\Categorie;
use Modules\Referentiel\Models\Magasin;
use Modules\Referentiel\Services\PerimetreMagasins;

/**
 * Paramétrage manuel des articles fréquents par magasin (SFD 6).
 */
class ArticleFrequentController extends Controller
{
    public function __construct(protected PerimetreMagasins $perimetre) {}

    public function index(Request $request)
    {
        return view('referentiel::articles-frequents.index', [
            'magasins' => $this->perimetre->query($request->user())->where('actif', true)->orderBy('libelle')->get(),
            'categories' => Categorie::orderBy('libelle')->get(),
            'maximum' => config('referentiel.articles_frequents_max'),
        ]);
    }

    public function show(Request $request, $magasinId)
    {
        $magasin = $this->magasinAccessible($request, $magasinId);

        return response()->json([
            'success' => true,
            'data' => $magasin->articlesFrequents()->get(['articles.id', 'code', 'designation', 'unite_mesure', 'actif']),
        ]);
    }

    public function update(Request $request, $magasinId)
    {
        $magasin = $this->magasinAccessible($request, $magasinId);
        $maximum = config('referentiel.articles_frequents_max');

        $data = $request->validate([
            'article_ids' => ['nullable', 'array', "max:{$maximum}"],
            'article_ids.*' => ['integer', 'distinct', 'exists:articles,id'],
        ], [
            'article_ids.max' => "Un magasin ne peut pas avoir plus de {$maximum} articles fréquents.",
            'article_ids.*.distinct' => 'Un article ne peut figurer qu\'une fois dans la liste.',
        ]);

        $avant = $magasin->articlesFrequents()->pluck('articles.id')->all();
        $apres = array_values($data['article_ids'] ?? []);

        $magasin->articlesFrequents()->sync(
            collect($apres)->mapWithKeys(fn ($id, $index) => [$id => ['ordre' => $index + 1]])->all()
        );

        if ($avant !== $apres) {
            $codes = fn (array $ids) => Article::whereIn('id', $ids)->pluck('code', 'id');
            activity('referentiel')
                ->performedOn($magasin)
                ->causedBy($request->user())
                ->withProperties([
                    'ajoutes' => $codes(array_diff($apres, $avant))->values(),
                    'retires' => $codes(array_diff($avant, $apres))->values(),
                    'ordre' => $apres,
                ])
                ->tap(function ($activity) {
                    $activity->module = 'referentiel';
                    $activity->ip_address = request()->ip();
                    $activity->user_agent = request()->userAgent();
                })
                ->log('articles_frequents_modifies');
        }

        return response()->json([
            'success' => true,
            'message' => 'Articles fréquents enregistrés ('.count($apres)."/{$maximum})",
        ]);
    }

    protected function magasinAccessible(Request $request, $magasinId): Magasin
    {
        $magasin = Magasin::findOrFail($magasinId);
        abort_unless($this->perimetre->peutAcceder($request->user(), $magasin), 403, 'Vous n\'êtes pas rattaché à ce magasin.');

        return $magasin;
    }
}
