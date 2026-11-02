<?php

namespace Modules\Etats\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Modules\Core\Models\User;
use Modules\Etats\Services\StatistiquesService;
use Modules\Referentiel\Database\Seeders\RolesSeeder;
use Modules\Referentiel\Models\Article;
use Modules\Referentiel\Models\Categorie;
use Modules\Referentiel\Models\Direction;
use Modules\Referentiel\Models\Magasin;
use Modules\Referentiel\Models\Service;
use Tests\TestCase;

/**
 * Page Statistiques : indicateurs, séries, comparaison à la période précédente, droits, rapport PDF.
 */
class StatistiquesTest extends TestCase
{
    use RefreshDatabase;

    protected Magasin $central;

    protected Magasin $annexe;

    protected Article $papier;

    protected Article $stylo;

    protected Service $service;

    protected User $gestionnaire;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-05-22 10:00:00');
        $this->seed(RolesSeeder::class);

        $this->central = Magasin::create(['code' => 'MAG-001', 'libelle' => 'Magasin Central']);
        $this->annexe = Magasin::create(['code' => 'MAG-002', 'libelle' => 'Magasin Annexe']);
        $categorie = Categorie::create(['code' => 'FOUR', 'libelle' => 'Fournitures']);
        $this->papier = Article::create(['code' => 'A1', 'designation' => 'Papier A4', 'unite_mesure' => 'RAME', 'categorie_id' => $categorie->id, 'stock_minimum' => 80]);
        $this->stylo = Article::create(['code' => 'A2', 'designation' => 'Stylo bleu', 'unite_mesure' => 'PIECE', 'categorie_id' => $categorie->id]);
        $direction = Direction::create(['code' => 'DG', 'libelle' => 'Direction Générale']);
        $this->service = $direction->services()->create(['code' => 'INFO', 'libelle' => 'Service Informatique']);

        $this->gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $this->central->utilisateurs()->attach($this->gestionnaire);
        $this->actingAs($this->gestionnaire);

        $this->mouvement('entrees', '2026-05-15', [[$this->papier, 100], [$this->stylo, 10]]);
        $this->mouvement('sorties', '2026-05-16', [[$this->papier, 30], [$this->stylo, 4]]);
        $this->mouvement('sorties', '2026-05-20', [[$this->papier, 5]]);
        $this->mouvement('transferts', '2026-05-21', [[$this->papier, 10]], ['magasin_destination_id' => $this->annexe->id]);
    }

    protected function mouvement(string $type, string $date, array $lignes, array $surcharge = []): void
    {
        $this->postJson(route('stock.saisie.store', $type), $surcharge + [
            'action' => 'valider', 'date_mouvement' => $date, 'magasin_id' => $this->central->id,
            'direction_id' => $this->service->direction_id, 'service_id' => $this->service->id,
            'lignes' => array_map(fn ($l) => ['article_id' => $l[0]->id, 'quantite' => $l[1]], $lignes),
        ])->assertOk();
    }

    public function test_indicateurs_et_classements(): void
    {
        $stats = app(StatistiquesService::class)->tableau(['periode' => '30j'], $this->gestionnaire);

        $this->assertSame(39, $stats['kpi']['consommation']['valeur']);
        $this->assertNull($stats['kpi']['consommation']['variation']);
        $this->assertSame(2, $stats['kpi']['bons']['valeur']);
        $this->assertSame(1, $stats['kpi']['services']);
        $this->assertSame(1, $stats['kpi']['transferts']['nombre']);
        $this->assertSame(1, $stats['kpi']['transferts']['en_transit']);
        // Papier : 100 − 35 − 10 = 55 < 80 → sous le seuil
        $this->assertSame(1, $stats['kpi']['alertes']['sous']);
        $this->assertSame([['libelle' => 'Papier A4', 'quantite' => 35, 'unite' => 'Rame'], ['libelle' => 'Stylo bleu', 'quantite' => 4, 'unite' => 'Pièce']], $stats['top_articles']);
        $this->assertSame([['libelle' => 'Service Informatique', 'quantite' => 39]], $stats['services']);
        $this->assertSame([['source' => 'Magasin Central', 'destination' => 'Magasin Annexe', 'transferts' => 1, 'quantite' => 10, 'ecart' => 0]], $stats['flux']);

        // Séries journalières sur 30 jours, alignées sur les libellés
        $this->assertCount(30, $stats['evolution']['libelles']);
        $this->assertSame(110, array_sum($stats['evolution']['series']['entrees']));
        $this->assertSame(39, array_sum($stats['evolution']['series']['consommation']));
        $this->assertSame(10, array_sum($stats['evolution']['series']['transferts']));
    }

    public function test_comparaison_a_la_periode_precedente(): void
    {
        Carbon::setTestNow('2026-06-20 10:00:00');
        $this->mouvement('sorties', '2026-06-18', [[$this->papier, 26]]);

        $stats = app(StatistiquesService::class)->tableau(['periode' => '30j'], $this->gestionnaire);

        // 26 sur la période contre 39 sur les 30 jours précédents : −33,3 %
        $this->assertSame(26, $stats['kpi']['consommation']['valeur']);
        $this->assertSame(39, $stats['kpi']['consommation']['precedent']);
        $this->assertSame(-33.3, $stats['kpi']['consommation']['variation']);
    }

    public function test_page_rapport_et_droits(): void
    {
        $this->get(route('etats.statistiques', ['periode' => '6m']))
            ->assertOk()
            ->assertSee('id="donnees-stats"', false)
            ->assertSee('data-apercu-pdf="'.route('etats.statistiques.rapport', ['periode' => '6m']).'"', false);
        $this->get(route('etats.statistiques.rapport', ['periode' => '12m']))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('etats.statistiques', ['periode' => 'nimporte']))->assertSessionHasErrors('periode');

        $this->actingAs(User::factory()->create()->assignRole('consultant'))->get(route('etats.statistiques'))->assertOk();
        $this->actingAs(User::factory()->create()->assignRole('operateur'))->get(route('etats.statistiques'))->assertForbidden();
    }
}
