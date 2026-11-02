<?php

namespace Modules\Etats\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Modules\Core\Models\User;
use Modules\Etats\Services\AnalysesService;
use Modules\Referentiel\Database\Seeders\RolesSeeder;
use Modules\Referentiel\Models\Article;
use Modules\Referentiel\Models\Categorie;
use Modules\Referentiel\Models\Direction;
use Modules\Referentiel\Models\Magasin;
use Modules\Referentiel\Models\Service;
use Modules\Stock\Models\Document;
use Tests\TestCase;

/**
 * États d'analyse : consommation nette des annulations, couverture, dormants, écarts d'inventaire.
 */
class AnalysesTest extends TestCase
{
    use RefreshDatabase;

    protected Magasin $central;

    protected Magasin $annexe;

    protected Article $papier;

    protected Article $stylo;

    protected Article $souris;

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
        $this->papier = Article::create(['code' => 'A1', 'designation' => 'Papier A4', 'unite_mesure' => 'RAME', 'categorie_id' => $fournitures->id]);
        $this->stylo = Article::create(['code' => 'A2', 'designation' => 'Stylo bleu', 'unite_mesure' => 'PIECE', 'categorie_id' => $fournitures->id]);
        $this->souris = Article::create(['code' => 'A3', 'designation' => 'Souris', 'unite_mesure' => 'PIECE', 'categorie_id' => $fournitures->id]);
        $direction = Direction::create(['code' => 'DG', 'libelle' => 'Direction Générale']);
        $this->service = $direction->services()->create(['code' => 'INFO', 'libelle' => 'Service Informatique']);

        $this->gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $this->central->utilisateurs()->attach($this->gestionnaire);
        $this->actingAs($this->gestionnaire);

        // Papier : +100, −30 (sortie), −20 (sortie annulée), −10 (transfert) → stock 60
        $this->mouvement('entrees', '2026-05-15', [[$this->papier, 100], [$this->stylo, 20], [$this->souris, 5]]);
        $this->mouvement('sorties', '2026-05-16', [[$this->papier, 30]]);
        $annulee = $this->mouvement('sorties', '2026-05-17', [[$this->papier, 20]]);
        $admin = User::factory()->create()->assignRole('admin');
        $this->central->utilisateurs()->attach($admin);
        $this->actingAs($admin)->postJson(route('stock.documents.annuler', $annulee))->assertOk();
        $this->actingAs($this->gestionnaire);
        $this->mouvement('transferts', '2026-05-18', [[$this->papier, 10]], ['magasin_destination_id' => $this->annexe->id]);
    }

    protected function mouvement(string $type, string $date, array $lignes, array $surcharge = []): int
    {
        $this->postJson(route('stock.saisie.store', $type), $surcharge + [
            'action' => 'valider',
            'date_mouvement' => $date,
            'magasin_id' => $this->central->id,
            'direction_id' => $this->service->direction_id,
            'service_id' => $this->service->id,
            'lignes' => array_map(fn ($l) => ['article_id' => $l[0]->id, 'quantite' => $l[1]], $lignes),
        ])->assertOk();

        return Document::latest('id')->where('type', '!=', Document::RECEPTION)->value('id');
    }

    protected function lignes($etat): array
    {
        return collect($etat->groupes)->flatMap(fn ($g) => $g['lignes'])->keyBy('code')->all();
    }

    public function test_consommation_par_service_nette_des_annulations_et_hors_transferts(): void
    {
        $etat = app(AnalysesService::class)->consommationParService(['date_du' => '2026-05-01'], $this->gestionnaire);

        $this->assertCount(1, $etat->groupes);
        $this->assertSame('Direction Générale / Service Informatique', $etat->groupes[0]['titre']);
        $papier = $this->lignes($etat)['A1'];
        $this->assertSame(30, $papier['quantite']);
        $this->assertSame(1, $papier['bons']);
        $this->assertSame('100,0 %', $papier['part']);
    }

    public function test_couverture_de_stock(): void
    {
        $etat = app(AnalysesService::class)->couverture(['jours' => 30, 'cible' => 60], $this->gestionnaire);
        $lignes = $this->lignes($etat);

        // Sorties 30 j = 30 (sortie) + 10 (transfert) ; 40 / 30 = 1,33 par jour ; 60 / 1,33 = 45 jours
        $this->assertSame(60, $lignes['A1']['stock']);
        $this->assertSame(40, $lignes['A1']['sorties']);
        $this->assertSame('1,33', $lignes['A1']['cmj']);
        $this->assertSame(45, $lignes['A1']['couverture']);
        $this->assertSame('06/07/2026', $lignes['A1']['rupture']);
        $this->assertSame(20, $lignes['A1']['a_commander']);
        $this->assertSame('alerte-egal', $lignes['A1']['_classe']);
        $this->assertSame('Sans sortie', $lignes['A2']['couverture']);

        $risque = app(AnalysesService::class)->couverture(['jours' => 30, 'cible' => 60, 'a_risque' => true], $this->gestionnaire);
        $this->assertSame(['A1'], array_keys($this->lignes($risque)));
    }

    public function test_articles_dormants(): void
    {
        $etat = app(AnalysesService::class)->dormants(['jours' => 7], $this->gestionnaire);
        $lignes = $this->lignes($etat);

        $this->assertArrayNotHasKey('A1', $lignes);
        $this->assertSame('Jamais', $lignes['A2']['derniere_sortie']);
        $this->assertSame('15/05/2026', $lignes['A2']['derniere_entree']);
        $this->assertSame(7, $lignes['A2']['inactivite']);
        $this->assertSame('alerte-sous', $lignes['A2']['_classe']);

        // Reçu il y a 7 jours seulement : pas dormant sur un horizon de 30 jours
        $this->assertSame([], $this->lignes(app(AnalysesService::class)->dormants(['jours' => 30], $this->gestionnaire)));
    }

    public function test_ecarts_d_inventaire_et_fiabilite(): void
    {
        $this->postJson(route('stock.inventaires.store'), [
            'magasin_id' => $this->central->id, 'date_inventaire' => '2026-05-22', 'commentaire' => 'Deux stylos manquants',
            'comptages' => [
                ['article_id' => $this->papier->id, 'quantite_reelle' => 60],
                ['article_id' => $this->stylo->id, 'quantite_reelle' => 18],
                ['article_id' => $this->souris->id, 'quantite_reelle' => 5],
            ],
        ])->assertOk();

        $etat = app(AnalysesService::class)->ecartsInventaire([], $this->gestionnaire);
        $stylo = $this->lignes($etat)['A2'];

        $this->assertSame('-2', $stylo['ecart']);
        $this->assertSame('-10,0 %', $stylo['taux']);
        $this->assertSame('66,7 %', $etat->pied['Fiabilité du stock']);
    }

    public function test_pages_exports_et_droits(): void
    {
        foreach (['etats.consommation', 'etats.couverture', 'etats.dormants', 'etats.ecarts-inventaire'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('data-apercu-pdf', false)->assertDontSee('@section', false);
            $this->get(route($route, ['format' => 'pdf']))->assertOk()->assertHeader('content-type', 'application/pdf');
            $this->get(route($route, ['format' => 'excel']))->assertOk();
        }

        $operateur = User::factory()->create()->assignRole('operateur');
        $this->actingAs($operateur)->get(route('etats.couverture'))->assertForbidden();
        $consultant = User::factory()->create()->assignRole('consultant');
        $this->actingAs($consultant)->get(route('etats.couverture'))->assertOk();
    }
}
