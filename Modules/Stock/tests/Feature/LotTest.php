<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;
use Modules\Referentiel\Database\Seeders\RolesSeeder;
use Modules\Referentiel\Models\Article;
use Modules\Referentiel\Models\Categorie;
use Modules\Referentiel\Models\Direction;
use Modules\Referentiel\Models\Magasin;
use Modules\Referentiel\Models\Service;
use Modules\Referentiel\Services\Parametres;
use Modules\Stock\Models\Document;
use Modules\Stock\Models\Lot;
use Modules\Stock\Models\Stock;
use Modules\Stock\Services\TransfertService;
use Tests\TestCase;

class LotTest extends TestCase
{
    use RefreshDatabase;

    protected Magasin $magasin;

    protected Magasin $annexe;

    protected Article $amox;

    protected Service $service;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-05-22 10:00:00');
        $this->seed(RolesSeeder::class);

        $this->magasin = Magasin::create(['code' => 'PHA', 'libelle' => 'Pharmacie centrale']);
        $this->annexe = Magasin::create(['code' => 'PHB', 'libelle' => 'Pharmacie annexe']);
        $categorie = Categorie::create(['code' => 'MED', 'libelle' => 'Médicaments']);
        $this->amox = Article::create(['code' => 'ART-001', 'designation' => 'Amoxicilline 500 mg', 'unite_mesure' => 'BOITE', 'categorie_id' => $categorie->id]);
        $direction = Direction::create(['code' => 'DG', 'libelle' => 'Direction Générale']);
        $this->service = $direction->services()->create(['code' => 'PED', 'libelle' => 'Pédiatrie']);

        $this->user = User::factory()->create()->assignRole('gestionnaire');
        $this->magasin->utilisateurs()->attach($this->user);
        $this->annexe->utilisateurs()->attach($this->user);
    }

    protected function entree(array $lignes)
    {
        return $this->actingAs($this->user)->postJson(route('stock.saisie.store', 'entrees'), [
            'action' => 'valider',
            'date_mouvement' => '2026-05-22',
            'magasin_id' => $this->magasin->id,
            'lignes' => $lignes,
        ]);
    }

    protected function sortie(int $quantite)
    {
        return $this->actingAs($this->user)->postJson(route('stock.saisie.store', 'sorties'), [
            'action' => 'valider',
            'date_mouvement' => '2026-05-22',
            'magasin_id' => $this->magasin->id,
            'direction_id' => $this->service->direction_id,
            'service_id' => $this->service->id,
            'lignes' => [['article_id' => $this->amox->id, 'quantite' => $quantite]],
        ]);
    }

    protected function stockLot(Magasin $magasin, string $numero): int
    {
        return (int) DB::table('stocks_lots')->where('magasin_id', $magasin->id)
            ->where('lot_id', Lot::where('numero', $numero)->value('id'))->value('quantite');
    }

    protected function stockTotal(Magasin $magasin): int
    {
        return (int) Stock::where('magasin_id', $magasin->id)->where('article_id', $this->amox->id)->value('quantite');
    }

    protected function livrer(): void
    {
        $this->entree([
            ['article_id' => $this->amox->id, 'quantite' => 10, 'lot_numero' => 'l1', 'date_peremption' => '2026-08-01'],
            ['article_id' => $this->amox->id, 'quantite' => 5, 'lot_numero' => 'L2', 'date_peremption' => '2026-07-01'],
            ['article_id' => $this->amox->id, 'quantite' => 5],
        ])->assertOk();
    }

    public function test_entree_ventilee_par_lot(): void
    {
        $this->livrer();

        $this->assertSame(20, $this->stockTotal($this->magasin));
        $this->assertSame(10, $this->stockLot($this->magasin, 'L1'));
        $this->assertSame(5, $this->stockLot($this->magasin, 'L2'));
        $this->assertSame('2026-08-01', Lot::where('numero', 'L1')->first()->date_peremption->toDateString());
    }

    public function test_sortie_en_fefo_puis_stock_sans_lot(): void
    {
        $this->livrer();

        // 8 : d'abord L2 (périme le 01/07), puis 3 de L1
        $this->sortie(8)->assertOk();
        $this->assertSame([0, 7], [$this->stockLot($this->magasin, 'L2'), $this->stockLot($this->magasin, 'L1')]);

        $sortie = Document::where('type', Document::SORTIE)->first();
        $this->assertSame(['L2' => 5, 'L1' => 3], $sortie->lignes->first()->lots()->with('lot')->orderBy('id')->get()
            ->mapWithKeys(fn ($v) => [$v->lot->numero => $v->quantite])->all());

        // 12 : les 7 restants de L1 puis 5 sans lot
        $this->sortie(12)->assertOk();
        $this->assertSame([0, 0], [$this->stockTotal($this->magasin), $this->stockLot($this->magasin, 'L1')]);

        $this->get(route('stock.documents.show', $sortie->id))->assertSee('L2 (exp. 01/07/2026)');
    }

    public function test_annulation_restitue_les_memes_lots(): void
    {
        $this->livrer();
        $this->sortie(8);
        $sortie = Document::where('type', Document::SORTIE)->first();

        $this->actingAs($this->user)->postJson(route('stock.documents.annuler', $sortie->id))->assertForbidden();
        $admin = User::factory()->create()->assignRole('admin');
        $this->actingAs($admin)->postJson(route('stock.documents.annuler', $sortie->id))->assertOk();

        $this->assertSame([5, 10, 20], [$this->stockLot($this->magasin, 'L2'), $this->stockLot($this->magasin, 'L1'), $this->stockTotal($this->magasin)]);
    }

    public function test_annulation_d_une_entree_dont_le_lot_est_consomme_refusee(): void
    {
        $this->livrer();
        $entree = Document::where('type', Document::ENTREE)->first();
        $this->entree([['article_id' => $this->amox->id, 'quantite' => 5]]);
        $this->sortie(5); // consomme L2 ; le stock total (20) suffirait à annuler l'entrée

        $admin = User::factory()->create()->assignRole('admin');
        $this->actingAs($admin)->postJson(route('stock.documents.annuler', $entree->id))
            ->assertStatus(422)->assertJsonFragment(['Stock du lot L2 insuffisant pour Amoxicilline 500 mg.']);
        $this->assertSame(20, $this->stockTotal($this->magasin));
    }

    public function test_lot_existant_avec_une_autre_date_refuse(): void
    {
        $this->livrer();

        $this->entree([['article_id' => $this->amox->id, 'quantite' => 1, 'lot_numero' => 'L1', 'date_peremption' => '2027-01-01']])
            ->assertStatus(422)->assertJsonFragment(['Le lot L1 existe déjà avec la date de péremption 01/08/2026.']);
    }

    public function test_les_lots_suivent_le_transfert(): void
    {
        $this->livrer();

        $this->actingAs($this->user)->postJson(route('stock.saisie.store', 'transferts'), [
            'action' => 'valider',
            'date_mouvement' => '2026-05-22',
            'magasin_id' => $this->magasin->id,
            'magasin_destination_id' => $this->annexe->id,
            'lignes' => [['article_id' => $this->amox->id, 'quantite' => 12]],
        ])->assertOk();
        $transfert = Document::where('type', Document::TRANSFERT)->first();

        // Expédié : L2 5 + L1 7 ; reçu 9 → L2 5 + L1 4 ; écart 3 de L1 réintégré à la source
        app(TransfertService::class)->receptionner($transfert, [$this->amox->id => 9], 'Casse', $this->user);

        $this->assertSame([5, 4], [$this->stockLot($this->annexe, 'L2'), $this->stockLot($this->annexe, 'L1')]);
        $this->assertSame([0, 6], [$this->stockLot($this->magasin, 'L2'), $this->stockLot($this->magasin, 'L1')]);
        $this->assertSame([11, 9], [$this->stockTotal($this->magasin), $this->stockTotal($this->annexe)]);
    }

    public function test_saisie_des_lots_selon_le_parametre(): void
    {
        $this->actingAs($this->user)->get(route('stock.saisie.create', 'entrees'))->assertOk()->assertDontSee('N° de lot');

        app(Parametres::class)->enregistrer(['lots_actifs' => true], $this->user);
        $this->get(route('stock.saisie.create', 'entrees'))->assertSee('N° de lot');
        $this->get(route('stock.saisie.create', 'sorties'))->assertDontSee('N° de lot');

        // Le lot d'un brouillon est conservé à la modification
        $this->postJson(route('stock.saisie.store', 'entrees'), [
            'action' => 'brouillon',
            'date_mouvement' => '2026-05-22',
            'magasin_id' => $this->magasin->id,
            'lignes' => [['article_id' => $this->amox->id, 'quantite' => 3, 'lot_numero' => 'B7', 'date_peremption' => '2027-02-28']],
        ])->assertOk();
        $this->get(route('stock.documents.edit', Document::first()->id))
            ->assertSee('&quot;lot_numero&quot;:&quot;B7&quot;', false)->assertSee('2027-02-28');
    }

    public function test_etat_des_peremptions_et_apercu_fefo(): void
    {
        app(Parametres::class)->enregistrer(['lots_actifs' => true, 'peremption_alerte_jours' => 60], $this->user);
        $this->livrer(); // L2 périme le 01/07 (dans 40 jours), L1 le 01/08 (dans 71 jours)

        $this->actingAs($this->user)->get(route('etats.peremptions'))
            ->assertOk()->assertSee('L2')->assertDontSee('>L1<', false)->assertSee('40');
        $this->get(route('etats.peremptions', ['jours' => 90]))->assertSee('L1');
        $this->get(route('etats.tableau-de-bord'))->assertSee('1 lot(s) en stock périmé(s) ou qui périment dans les 60 jours');

        Carbon::setTestNow('2026-07-05 10:00:00');
        $this->get(route('etats.peremptions'))->assertSee('Périmé');

        $this->getJson(route('stock.saisie.stocks', [$this->magasin->id, 'article_ids' => [$this->amox->id]]))
            ->assertJsonPath('data.'.$this->amox->id, 20)
            ->assertJsonPath('lots.'.$this->amox->id.'.0.numero', 'L2')
            ->assertJsonPath('lots.'.$this->amox->id.'.1.numero', 'L1');
    }
}
