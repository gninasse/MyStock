<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Modules\Core\Models\User;
use Modules\Referentiel\Database\Seeders\RolesSeeder;
use Modules\Referentiel\Models\Article;
use Modules\Referentiel\Models\Categorie;
use Modules\Referentiel\Models\Direction;
use Modules\Referentiel\Models\Magasin;
use Modules\Referentiel\Models\Service;
use Modules\Stock\Models\Document;
use Modules\Stock\Models\Stock;
use Tests\TestCase;

class MouvementTest extends TestCase
{
    use RefreshDatabase;

    protected Magasin $magasin;

    protected Article $papier;

    protected Article $stylo;

    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-05-22 10:00:00');
        $this->seed(RolesSeeder::class);

        $this->magasin = Magasin::create(['code' => 'MAG-001', 'libelle' => 'Magasin Central']);
        $categorie = Categorie::create(['code' => 'FOUR', 'libelle' => 'Fournitures']);
        $this->papier = Article::create(['code' => 'ART-001', 'designation' => 'Papier A4', 'unite_mesure' => 'RAME', 'categorie_id' => $categorie->id]);
        $this->stylo = Article::create(['code' => 'ART-002', 'designation' => 'Stylo bleu', 'unite_mesure' => 'PIECE', 'categorie_id' => $categorie->id]);
        $direction = Direction::create(['code' => 'DG', 'libelle' => 'Direction Générale']);
        $this->service = $direction->services()->create(['code' => 'INFO', 'libelle' => 'Service Informatique']);
    }

    protected function utilisateur(string $role, bool $rattache = true): User
    {
        $user = User::factory()->create()->assignRole($role);
        if ($rattache) {
            $this->magasin->utilisateurs()->attach($user);
        }

        return $user;
    }

    protected function entree(array $lignes, string $action = 'valider', array $surcharge = []): array
    {
        return $surcharge + [
            'action' => $action,
            'date_mouvement' => '2026-05-22',
            'magasin_id' => $this->magasin->id,
            'commentaire' => 'Livraison fournisseur BuroPlus - BC 458',
            'lignes' => $lignes,
        ];
    }

    protected function sortie(array $lignes, string $action = 'valider', array $surcharge = []): array
    {
        return $this->entree($lignes, $action, $surcharge + [
            'direction_id' => $this->service->direction_id,
            'service_id' => $this->service->id,
        ]);
    }

    protected function stock(Article $article): int
    {
        return (int) Stock::where('magasin_id', $this->magasin->id)->where('article_id', $article->id)->value('quantite');
    }

    public function test_entree_validee_met_a_jour_le_stock_et_numerote(): void
    {
        $this->actingAs($this->utilisateur('operateur'))
            ->postJson(route('stock.saisie.store', 'entrees'), $this->entree([
                ['article_id' => $this->papier->id, 'quantite' => 30],
                ['article_id' => $this->papier->id, 'quantite' => 20], // dédoublonné : 50
                ['article_id' => $this->stylo->id, 'quantite' => 200],
            ]))
            ->assertOk()
            ->assertJsonPath('data.reference', 'ENT-20260522-001')
            ->assertJsonPath('message', 'Entrée ENT-20260522-001 validée avec succès. Stock mis à jour.');

        $this->assertSame(50, $this->stock($this->papier));
        $this->assertSame(200, $this->stock($this->stylo));

        $this->postJson(route('stock.saisie.store', 'entrees'), $this->entree([['article_id' => $this->papier->id, 'quantite' => 1]]))
            ->assertJsonPath('data.reference', 'ENT-20260522-002');
    }

    public function test_brouillon_n_impacte_pas_le_stock_puis_validation(): void
    {
        $this->actingAs($this->utilisateur('gestionnaire'));

        $id = $this->postJson(route('stock.saisie.store', 'entrees'), $this->entree([['article_id' => $this->papier->id, 'quantite' => 5]], 'brouillon'))
            ->assertOk()
            ->assertJsonPath('data.statut', 'BROUILLON')
            ->assertJsonPath('data.reference', null)
            ->json('data.id');
        $this->assertSame(0, $this->stock($this->papier));

        $this->putJson(route('stock.documents.update', $id), $this->entree([['article_id' => $this->papier->id, 'quantite' => 8]]))
            ->assertOk()
            ->assertJsonPath('data.statut', 'VALIDE');
        $this->assertSame(8, $this->stock($this->papier));

        // Un document validé ne se modifie ni ne se supprime
        $this->putJson(route('stock.documents.update', $id), $this->entree([['article_id' => $this->papier->id, 'quantite' => 1]]))
            ->assertStatus(422);
        $this->deleteJson(route('stock.documents.destroy', $id))->assertStatus(422);
    }

    public function test_controles_de_validation(): void
    {
        $this->actingAs($this->utilisateur('admin'));
        $url = route('stock.saisie.store', 'entrees');

        $this->postJson($url, $this->entree([]))
            ->assertStatus(422)
            ->assertJsonPath('errors.lignes.0', 'Veuillez ajouter au moins un article avec une quantité supérieure à zéro.');

        $this->postJson($url, $this->entree([['article_id' => $this->papier->id, 'quantite' => 0]]))
            ->assertStatus(422)
            ->assertJsonPath('errors', ['lignes.0.quantite' => ['La quantité doit être supérieure à zéro pour l\'article Papier A4.']]);

        $this->postJson($url, $this->entree([['article_id' => $this->papier->id, 'quantite' => 1]], 'valider', ['date_mouvement' => '2026-05-23']))
            ->assertJsonPath('errors.date_mouvement.0', 'La date du mouvement ne peut pas être dans le futur.');

        $this->postJson($url, $this->entree([['article_id' => $this->papier->id, 'quantite' => 1]], 'valider', ['date_mouvement' => '2026-05-14']))
            ->assertJsonPath('errors.date_mouvement.0', 'La date du mouvement ne peut pas être antérieure à 7 jours.');

        $this->stylo->update(['actif' => false]);
        $this->postJson($url, $this->entree([['article_id' => $this->stylo->id, 'quantite' => 1]]))
            ->assertJsonPath('errors.lignes.0', 'L\'article Stylo bleu est désactivé et ne peut pas faire l\'objet d\'un mouvement.');

        $this->magasin->update(['actif' => false]);
        $this->postJson($url, $this->entree([['article_id' => $this->papier->id, 'quantite' => 1]]))
            ->assertJsonPath('errors.magasin_id.0', 'Le magasin sélectionné est désactivé. Veuillez en choisir un autre.');

        // Aucun échec n'a laissé de brouillon derrière lui
        $this->assertSame(0, Document::count());
    }

    public function test_sortie_controle_le_stock_et_le_beneficiaire(): void
    {
        $this->actingAs($this->utilisateur('gestionnaire'));
        $this->postJson(route('stock.saisie.store', 'entrees'), $this->entree([['article_id' => $this->papier->id, 'quantite' => 45]]))->assertOk();
        $url = route('stock.saisie.store', 'sorties');

        $this->postJson($url, $this->sortie([
            ['article_id' => $this->papier->id, 'quantite' => 50],
            ['article_id' => $this->stylo->id, 'quantite' => 1],
        ]))
            ->assertStatus(422)
            ->assertJsonPath('errors.lignes', [
                'Stock insuffisant pour Papier A4. Disponible : 45, Demandé : 50.',
                'Stock insuffisant pour Stylo bleu. Disponible : 0, Demandé : 1.',
            ]);
        $this->assertSame(45, $this->stock($this->papier));

        $this->postJson($url, $this->entree([['article_id' => $this->papier->id, 'quantite' => 10]]))
            ->assertJsonPath('errors.service_id.0', 'Veuillez sélectionner une Direction et un Service.');

        $this->postJson($url, $this->sortie([['article_id' => $this->papier->id, 'quantite' => 10]]))
            ->assertOk()
            ->assertJsonPath('data.reference', 'SOR-20260522-001');
        $this->assertSame(35, $this->stock($this->papier));
    }

    public function test_service_d_une_autre_direction_refuse(): void
    {
        $autre = Direction::create(['code' => 'DAF', 'libelle' => 'Finances']);

        $this->actingAs($this->utilisateur('gestionnaire'))
            ->postJson(route('stock.saisie.store', 'sorties'), $this->sortie([['article_id' => $this->papier->id, 'quantite' => 1]], 'valider', ['direction_id' => $autre->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('service_id');
    }

    public function test_droits_par_role_et_par_magasin(): void
    {
        $this->actingAs($this->utilisateur('operateur'))
            ->postJson(route('stock.saisie.store', 'sorties'), $this->sortie([['article_id' => $this->papier->id, 'quantite' => 1]]))
            ->assertForbidden();

        $this->actingAs($this->utilisateur('gestionnaire', rattache: false))
            ->postJson(route('stock.saisie.store', 'entrees'), $this->entree([['article_id' => $this->papier->id, 'quantite' => 1]]))
            ->assertForbidden();

        $this->actingAs($this->utilisateur('consultant'))
            ->get(route('stock.documents.index'))
            ->assertForbidden();
    }

    public function test_annulation_genere_un_mouvement_inverse(): void
    {
        $admin = $this->utilisateur('admin');
        $this->actingAs($admin);
        $id = $this->postJson(route('stock.saisie.store', 'entrees'), $this->entree([['article_id' => $this->papier->id, 'quantite' => 20]]))->json('data.id');

        $this->postJson(route('stock.documents.annuler', $id))
            ->assertOk()
            ->assertJsonPath('data.reference', 'SOR-20260522-001');

        $this->assertSame(0, $this->stock($this->papier));
        $this->assertSame(Document::ANNULE, Document::find($id)->statut);
        $this->assertDatabaseHas('documents', ['commentaire' => 'Annulation du mouvement ENT-20260522-001', 'document_annule_id' => $id]);

        // Une seule annulation possible, et l'annulation elle-même n'est pas annulable
        $this->postJson(route('stock.documents.annuler', $id))->assertStatus(422);
        $inverse = Document::where('document_annule_id', $id)->value('id');
        $this->postJson(route('stock.documents.annuler', $inverse))
            ->assertStatus(422)
            ->assertJsonPath('errors.document.0', 'Un mouvement d\'annulation ne peut pas être annulé.');
    }

    public function test_annulation_refusee_si_le_stock_a_ete_consomme(): void
    {
        $this->actingAs($this->utilisateur('admin'));
        $id = $this->postJson(route('stock.saisie.store', 'entrees'), $this->entree([['article_id' => $this->papier->id, 'quantite' => 20]]))->json('data.id');
        $this->postJson(route('stock.saisie.store', 'sorties'), $this->sortie([['article_id' => $this->papier->id, 'quantite' => 15]]))->assertOk();

        $this->postJson(route('stock.documents.annuler', $id))
            ->assertStatus(422)
            ->assertJsonPath('errors.lignes.0', 'Stock insuffisant pour Papier A4. Disponible : 5, Demandé : 20.');
        $this->assertSame(Document::VALIDE, Document::find($id)->statut);
    }

    public function test_seul_l_admin_annule(): void
    {
        $this->actingAs($this->utilisateur('gestionnaire'));
        $id = $this->postJson(route('stock.saisie.store', 'entrees'), $this->entree([['article_id' => $this->papier->id, 'quantite' => 2]]))->json('data.id');

        $this->postJson(route('stock.documents.annuler', $id))->assertForbidden();
    }

    public function test_liste_et_ecrans(): void
    {
        $gestionnaire = $this->utilisateur('gestionnaire');
        $this->actingAs($gestionnaire);
        $id = $this->postJson(route('stock.saisie.store', 'sorties'), $this->sortie([['article_id' => $this->papier->id, 'quantite' => 1]], 'brouillon'))->json('data.id');

        // Document d'un magasin hors périmètre : invisible
        $autre = Magasin::create(['code' => 'MAG-002', 'libelle' => 'Annexe']);
        Document::create(['type' => 'ENTREE', 'date_mouvement' => '2026-05-22', 'magasin_id' => $autre->id, 'user_id' => $gestionnaire->id]);

        $this->getJson(route('stock.documents.data', ['date_du' => '2026-05-01', 'date_au' => '2026-05-22']))
            ->assertJsonPath('total', 1)
            ->assertJsonPath('rows.0.beneficiaire', 'Service Informatique')
            ->assertJsonPath('rows.0.modifiable', true)
            ->assertJsonPath('rows.0.annulable', false);

        $this->get(route('stock.documents.index'))->assertOk()->assertSee('Mouvements');
        $this->get(route('stock.saisie.create', 'entrees'))->assertOk()->assertSee('Création rapide');
        $this->get(route('stock.saisie.create', 'sorties'))->assertOk()->assertSee('Bénéficiaire');
        $this->get(route('stock.documents.edit', $id))->assertOk();
        $this->get(route('stock.documents.show', $id))->assertOk()->assertSee('Papier A4');
    }

    public function test_donnees_de_saisie(): void
    {
        $this->actingAs($this->utilisateur('gestionnaire'));
        $this->magasin->articlesFrequents()->attach([$this->papier->id => ['ordre' => 1], $this->stylo->id => ['ordre' => 2]]);
        $this->postJson(route('stock.saisie.store', 'entrees'), $this->entree([['article_id' => $this->papier->id, 'quantite' => 12]]));

        $this->getJson(route('stock.saisie.articles-frequents', $this->magasin->id))
            ->assertJsonPath('data.0.stock', 12)
            ->assertJsonPath('data.1.stock', 0);

        $this->getJson(route('stock.saisie.stocks', [$this->magasin->id, 'article_ids' => [$this->papier->id]]))
            ->assertJsonPath("data.{$this->papier->id}", 12);

        $this->getJson(route('stock.saisie.beneficiaires'))
            ->assertJsonPath('data.0.services.0.libelle', 'Service Informatique');
    }

    public function test_structure_utilisee_non_supprimable_et_code_article_automatique(): void
    {
        $this->actingAs($this->utilisateur('admin'));
        $this->postJson(route('stock.saisie.store', 'entrees'), $this->entree([['article_id' => $this->papier->id, 'quantite' => 5]]));
        $this->postJson(route('stock.saisie.store', 'sorties'), $this->sortie([['article_id' => $this->papier->id, 'quantite' => 1]]))->assertOk();

        $this->deleteJson(route('referentiel.structures.destroy', ['service', $this->service->id]))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Impossible de supprimer : cet élément est lié à des mouvements de stock. Voulez-vous le désactiver ?');

        $this->postJson(route('referentiel.articles.store'), [
            'designation' => 'Ruban adhésif transparent',
            'unite_mesure' => 'PIECE',
            'categorie_id' => $this->papier->categorie_id,
        ])->assertOk()->assertJsonPath('data.code', 'ART-003');
    }
}
