<?php

namespace Modules\Etats\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Modules\Core\Models\User;
use Modules\Etats\Services\AlerteStockService;
use Modules\Referentiel\Database\Seeders\RolesSeeder;
use Modules\Referentiel\Models\Article;
use Modules\Referentiel\Models\Categorie;
use Modules\Referentiel\Models\Direction;
use Modules\Referentiel\Models\Magasin;
use Modules\Referentiel\Models\Service;
use Tests\TestCase;

class EtatsTest extends TestCase
{
    use RefreshDatabase;

    protected Magasin $central;

    protected Magasin $annexe;

    protected Article $papier;

    protected Article $stylo;

    protected Article $classeur;

    protected Service $service;

    protected User $gestionnaire;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-05-22 10:00:00');
        $this->seed(RolesSeeder::class);

        $this->central = Magasin::create(['code' => 'MAG-001', 'libelle' => 'Magasin Central']);
        $this->annexe = Magasin::create(['code' => 'MAG-002', 'libelle' => 'Magasin Annexe']);
        $fournitures = Categorie::create(['code' => 'FOUR', 'libelle' => 'Fournitures']);
        $info = Categorie::create(['code' => 'INFO', 'libelle' => 'Informatique']);
        $this->papier = Article::create(['code' => 'A1', 'designation' => 'Papier A4', 'unite_mesure' => 'RAME', 'categorie_id' => $fournitures->id, 'stock_minimum' => 10]);
        $this->stylo = Article::create(['code' => 'A2', 'designation' => 'Stylo bleu', 'unite_mesure' => 'PIECE', 'categorie_id' => $fournitures->id, 'stock_minimum' => 5]);
        $this->classeur = Article::create(['code' => 'A3', 'designation' => 'Souris', 'unite_mesure' => 'PIECE', 'categorie_id' => $info->id]);
        $direction = Direction::create(['code' => 'DG', 'libelle' => 'Direction Générale']);
        $this->service = $direction->services()->create(['code' => 'INFO', 'libelle' => 'Service Informatique']);

        $this->gestionnaire = $this->utilisateur('gestionnaire', [$this->central]);
        $this->actingAs($this->gestionnaire);

        // Central : papier 40 - 30 = 10 (au seuil), stylo 3 (sous le seuil), souris 7
        $this->mouvement('entrees', '2026-05-20', [[$this->papier, 40], [$this->stylo, 3], [$this->classeur, 7]]);
        $this->mouvement('sorties', '2026-05-21', [[$this->papier, 30]]);
    }

    protected function utilisateur(string $role, array $magasins = []): User
    {
        $user = User::factory()->create()->assignRole($role);
        foreach ($magasins as $magasin) {
            $magasin->utilisateurs()->attach($user);
        }

        return $user;
    }

    protected function mouvement(string $type, string $date, array $lignes, ?Magasin $magasin = null): void
    {
        $this->postJson(route('stock.saisie.store', $type), [
            'action' => 'valider',
            'date_mouvement' => $date,
            'magasin_id' => ($magasin ?? $this->central)->id,
            'direction_id' => $this->service->direction_id,
            'service_id' => $this->service->id,
            'lignes' => array_map(fn ($l) => ['article_id' => $l[0]->id, 'quantite' => $l[1]], $lignes),
        ])->assertOk();
    }

    public function test_alertes_de_stock_minimum(): void
    {
        $alertes = app(AlerteStockService::class)->alertes($this->gestionnaire);

        $this->assertSame(['Stylo bleu' => 'sous', 'Papier A4' => 'egal'], $alertes->pluck('niveau', 'designation')->all());
        $this->assertSame(2, app(AlerteStockService::class)->nombre($this->gestionnaire));

        // Article fréquent jamais approvisionné dans l'Annexe : surveillé aussi (0 < 10)
        $admin = $this->utilisateur('admin');
        $this->annexe->articlesFrequents()->attach($this->papier->id, ['ordre' => 1]);
        $this->assertSame(3, app(AlerteStockService::class)->nombre($admin));
        $this->assertSame(2, app(AlerteStockService::class)->nombre($this->gestionnaire)); // hors périmètre
    }

    public function test_stock_par_magasin(): void
    {
        $this->get(route('etats.stock-magasin'))
            ->assertOk()
            ->assertSeeInOrder(['Magasin Central (MAG-001)', 'Papier A4', '10', 'Au seuil', 'Stylo bleu', 'Sous le seuil', 'Total Magasin Central (3 articles)', '20'])
            ->assertDontSee('Magasin Annexe (MAG-002)');

        $this->get(route('etats.stock-magasin', ['envoye' => 1, 'alerte' => 1]))
            ->assertOk()
            ->assertSee('Stylo bleu')
            ->assertDontSee('Souris');
    }

    public function test_stock_par_categorie(): void
    {
        $this->get(route('etats.stock-categorie'))
            ->assertOk()
            ->assertSeeInOrder(['Fournitures — 2 article(s), quantité totale 13', 'Informatique — 1 article(s), quantité totale 7']);
    }

    public function test_journal_des_mouvements(): void
    {
        $this->get(route('etats.journal', ['date_du' => '2026-05-01', 'date_au' => '2026-05-22']))
            ->assertOk()
            ->assertSeeInOrder(['ENT-20260520-001', 'Papier A4 (A1)', '40', 'SOR-20260521-001', '-30', 'Service Informatique']);

        $this->get(route('etats.journal', ['envoye' => 1, 'date_du' => '2026-05-01', 'date_au' => '2026-05-22', 'types' => ['SORTIE']]))
            ->assertOk()
            ->assertSee('SOR-20260521-001')
            ->assertDontSee('ENT-20260520-001');
    }

    public function test_fiche_de_stock(): void
    {
        $this->mouvement('entrees', '2026-05-22', [[$this->papier, 5]]);

        $this->get(route('etats.fiche-stock', [
            'article_id' => $this->papier->id,
            'magasin_id' => $this->central->id,
            'date_du' => '2026-05-21',
            'date_au' => '2026-05-22',
        ]))
            ->assertOk()
            ->assertSeeInOrder(['Stock au 21/05/2026', '40', 'SOR-20260521-001', '-30', '10', 'ENT-20260522-001', '5', '15', 'Stock au 22/05/2026 : 15']);
    }

    public function test_exports_excel_et_pdf_journalises(): void
    {
        $this->get(route('etats.stock-magasin', ['format' => 'excel']))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertDownload();

        $this->get(route('etats.journal', ['format' => 'pdf']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->postJson(route('etats.impression'), ['titre' => 'Fiche de stock'])->assertOk();

        $this->assertSame(
            ['Excel', 'PDF', 'Impression'],
            \Modules\Core\Models\Activity::where('description', 'export')->orderBy('id')->get()->pluck('properties.format')->all()
        );
    }

    public function test_droits_par_role(): void
    {
        $operateur = $this->utilisateur('operateur', [$this->central]);
        $this->actingAs($operateur);
        $this->get(route('etats.stock-magasin'))->assertOk();
        $this->get(route('etats.journal'))->assertForbidden();
        $this->get(route('etats.fiche-stock'))->assertForbidden();

        $consultant = $this->utilisateur('consultant');
        $this->actingAs($consultant);
        $this->get(route('etats.fiche-stock'))->assertOk();
        $this->get(route('etats.journal'))->assertForbidden();
        $this->get(route('etats.stock-magasin'))->assertOk()->assertSee('Magasin Annexe (MAG-002)'); // tous les magasins

        // Fiche d'un magasin hors périmètre
        $this->actingAs($operateur);
        $this->actingAs($this->gestionnaire)
            ->get(route('etats.fiche-stock', ['article_id' => $this->papier->id, 'magasin_id' => $this->annexe->id]))
            ->assertForbidden();
    }

    public function test_tableau_de_bord_selon_le_role(): void
    {
        $this->get('/')->assertRedirect(route('etats.tableau-de-bord'));

        $this->get(route('etats.tableau-de-bord'))
            ->assertOk()
            ->assertSee('Articles sous le seuil minimum')
            ->assertSee('Derniers mouvements')
            ->assertSee('SOR-20260521-001')
            ->assertDontSee('Dernières actions')
            ->assertSee('Nouvelle sortie')
            ->assertSee('nav-badge', false);

        $this->actingAs($this->utilisateur('consultant'))
            ->get(route('etats.tableau-de-bord'))
            ->assertOk()
            ->assertDontSee('Articles sous le seuil minimum')
            ->assertDontSee('Derniers mouvements')
            ->assertDontSee('Nouvelle entrée');

        $this->actingAs($this->utilisateur('admin'))
            ->get(route('etats.tableau-de-bord'))
            ->assertOk()
            ->assertSee('Dernières actions');
    }
}
