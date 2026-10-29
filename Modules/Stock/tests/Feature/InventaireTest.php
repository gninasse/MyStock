<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Modules\Core\Models\User;
use Modules\Referentiel\Database\Seeders\RolesSeeder;
use Modules\Referentiel\Models\Article;
use Modules\Referentiel\Models\Categorie;
use Modules\Referentiel\Models\Magasin;
use Modules\Stock\Models\Document;
use Modules\Stock\Models\Inventaire;
use Modules\Stock\Models\Stock;
use Tests\TestCase;

class InventaireTest extends TestCase
{
    use RefreshDatabase;

    protected Magasin $magasin;

    protected array $articles = [];

    protected User $gestionnaire;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-05-22 10:00:00');
        $this->seed(RolesSeeder::class);

        $this->magasin = Magasin::create(['code' => 'MAG-001', 'libelle' => 'Magasin Central']);
        $categorie = Categorie::create(['code' => 'FOUR', 'libelle' => 'Fournitures']);
        foreach (['ART001' => 'Papier A4', 'ART002' => 'Stylo bleu', 'ART003' => 'Enveloppes kraft', 'ART004' => 'Classeur dos 8cm'] as $code => $designation) {
            $this->articles[$code] = Article::create(['code' => $code, 'designation' => $designation, 'unite_mesure' => 'PIECE', 'categorie_id' => $categorie->id]);
        }

        $this->gestionnaire = User::factory()->create()->assignRole('gestionnaire');
        $this->magasin->utilisateurs()->attach($this->gestionnaire);
        $this->actingAs($this->gestionnaire);

        // Stock de départ (exemple SFD 8.1) : 35 / 12 / 0 / 5
        $this->postJson(route('stock.saisie.store', 'entrees'), [
            'action' => 'valider',
            'date_mouvement' => '2026-05-22',
            'magasin_id' => $this->magasin->id,
            'lignes' => [
                ['article_id' => $this->articles['ART001']->id, 'quantite' => 35],
                ['article_id' => $this->articles['ART002']->id, 'quantite' => 12],
                ['article_id' => $this->articles['ART004']->id, 'quantite' => 5],
            ],
        ])->assertOk();
    }

    protected function stock(string $code): int
    {
        return (int) Stock::where('magasin_id', $this->magasin->id)->where('article_id', $this->articles[$code]->id)->value('quantite');
    }

    protected function comptages(array $quantites): array
    {
        return collect($quantites)->map(fn ($q, $code) => ['article_id' => $this->articles[$code]->id, 'quantite_reelle' => $q])->values()->all();
    }

    public function test_articles_a_inventorier_avec_stock_theorique(): void
    {
        $this->articles['ART003']->update(['actif' => false]); // inactif et sans stock : exclu

        $this->getJson(route('stock.inventaires.articles', $this->magasin->id))
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.code', 'ART001')
            ->assertJsonPath('data.0.stock_theorique', 35)
            ->assertJsonPath('data.2.code', 'ART004');
    }

    public function test_validation_genere_un_ajustement_et_met_le_stock_a_jour(): void
    {
        $this->postJson(route('stock.inventaires.store'), [
            'magasin_id' => $this->magasin->id,
            'date_inventaire' => '2026-05-22',
            'commentaire' => 'Inventaire trimestriel - Écarts justifiés',
            'comptages' => $this->comptages(['ART001' => 38, 'ART002' => 10, 'ART003' => 0, 'ART004' => null]),
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Inventaire du 22/05/2026 validé. 3 ajustement(s) généré(s).')
            ->assertJsonPath('data.reference_ajustement', 'AJU-20260522-001');

        // Champ vide = 0 : ART004 passe de 5 à 0
        $this->assertSame([38, 10, 0, 0], [$this->stock('ART001'), $this->stock('ART002'), $this->stock('ART003'), $this->stock('ART004')]);

        $inventaire = Inventaire::with('lignes')->first();
        $this->assertSame(4, $inventaire->lignes->count());
        $this->assertSame([3, -2, 0, -5], $inventaire->lignes->sortBy('article_id')->pluck('ecart')->all());

        $ajustement = Document::where('type', Document::AJUSTEMENT)->with('lignes')->first();
        $this->assertSame(Document::VALIDE, $ajustement->statut);
        $this->assertCount(3, $ajustement->lignes);
        $this->assertStringStartsWith('Ajustement inventaire du 22/05/2026 — Magasin Central', $ajustement->commentaire);
    }

    public function test_commentaire_obligatoire_si_ecarts(): void
    {
        $this->postJson(route('stock.inventaires.store'), [
            'magasin_id' => $this->magasin->id,
            'date_inventaire' => '2026-05-22',
            'comptages' => $this->comptages(['ART001' => 30]),
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.commentaire.0', 'Un commentaire est obligatoire pour justifier les écarts constatés.');

        $this->assertSame(0, Inventaire::count());
        $this->assertSame(35, $this->stock('ART001'));
    }

    public function test_inventaire_sans_ecart_ne_genere_pas_d_ajustement(): void
    {
        $this->postJson(route('stock.inventaires.store'), [
            'magasin_id' => $this->magasin->id,
            'date_inventaire' => '2026-05-22',
            'comptages' => $this->comptages(['ART001' => 35, 'ART002' => 12]),
        ])
            ->assertOk()
            ->assertJsonPath('data.reference_ajustement', null);

        $this->assertSame(0, Document::where('type', Document::AJUSTEMENT)->count());
    }

    public function test_ecart_calcule_sur_le_stock_au_moment_de_la_validation(): void
    {
        // Une entrée arrive pendant le comptage : l'écart se calcule sur 40, pas sur 35
        $this->postJson(route('stock.saisie.store', 'entrees'), [
            'action' => 'valider',
            'date_mouvement' => '2026-05-22',
            'magasin_id' => $this->magasin->id,
            'lignes' => [['article_id' => $this->articles['ART001']->id, 'quantite' => 5]],
        ])->assertOk();

        $this->postJson(route('stock.inventaires.store'), [
            'magasin_id' => $this->magasin->id,
            'date_inventaire' => '2026-05-22',
            'commentaire' => 'Casse',
            'comptages' => $this->comptages(['ART001' => 39]),
        ])->assertOk();

        $this->assertSame(-1, Inventaire::first()->lignes()->value('ecart'));
        $this->assertSame(39, $this->stock('ART001'));
    }

    public function test_droits_et_ecrans(): void
    {
        $operateur = User::factory()->create()->assignRole('operateur');
        $this->magasin->utilisateurs()->attach($operateur);
        $this->actingAs($operateur)->get(route('stock.inventaires.create'))->assertForbidden();

        $horsPerimetre = Magasin::create(['code' => 'MAG-002', 'libelle' => 'Annexe']);
        $this->actingAs($this->gestionnaire)
            ->getJson(route('stock.inventaires.articles', $horsPerimetre->id))
            ->assertForbidden();

        $id = $this->postJson(route('stock.inventaires.store'), [
            'magasin_id' => $this->magasin->id,
            'date_inventaire' => '2026-05-22',
            'commentaire' => 'Écart constaté',
            'comptages' => $this->comptages(['ART002' => 11]),
        ])->json('data.id');

        $this->get(route('stock.inventaires.create'))->assertOk()->assertSee('Inventaire physique');
        $this->get(route('stock.inventaires.index'))->assertOk();
        $this->getJson(route('stock.inventaires.data'))->assertJsonPath('rows.0.ecarts_count', 1);
        $this->get(route('stock.inventaires.show', $id))->assertOk()->assertSee('AJU-20260522-001');
        $this->get(route('stock.documents.show', Document::where('type', 'AJUSTEMENT')->value('id')))
            ->assertOk()->assertSee('Inventaire du 22/05/2026');
    }

    public function test_date_et_quantite_negative_refusees(): void
    {
        $this->postJson(route('stock.inventaires.store'), [
            'magasin_id' => $this->magasin->id,
            'date_inventaire' => '2026-05-23',
            'comptages' => $this->comptages(['ART001' => 35]),
        ])->assertJsonPath('errors.date_inventaire.0', 'La date de l\'inventaire ne peut pas être dans le futur.');

        $this->postJson(route('stock.inventaires.store'), [
            'magasin_id' => $this->magasin->id,
            'date_inventaire' => '2026-05-22',
            'comptages' => $this->comptages(['ART001' => -1]),
        ])->assertStatus(422)->assertJsonValidationErrors('comptages.0.quantite_reelle');
    }
}
