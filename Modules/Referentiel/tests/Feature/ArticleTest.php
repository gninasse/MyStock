<?php

namespace Modules\Referentiel\Tests\Feature;

use Modules\Referentiel\Models\Article;
use Modules\Referentiel\Models\Categorie;
use Modules\Referentiel\Models\Magasin;

class ArticleTest extends ReferentielTestCase
{
    protected Categorie $categorie;

    protected function setUp(): void
    {
        parent::setUp();
        $this->categorie = Categorie::create(['code' => 'FOUR', 'libelle' => 'Fournitures', 'unite_par_defaut' => 'PIECE']);
    }

    protected function donnees(array $surcharge = []): array
    {
        return $surcharge + [
            'code' => 'art-001',
            'designation' => 'Papier A4',
            'unite_mesure' => 'RAME',
            'categorie_id' => $this->categorie->id,
            'stock_minimum' => 10,
        ];
    }

    public function test_operateur_cree_un_article_mais_ne_le_modifie_pas(): void
    {
        $operateur = $this->utilisateur('operateur');

        $id = $this->actingAs($operateur)
            ->postJson(route('referentiel.articles.store'), $this->donnees())
            ->assertOk()->json('data.id');

        $this->assertDatabaseHas('articles', ['code' => 'ART-001', 'cree_par' => $operateur->id]);

        $this->putJson(route('referentiel.articles.update', $id), $this->donnees(['designation' => 'Autre']))
            ->assertForbidden();
        $this->postJson(route('referentiel.articles.toggle-status', $id))->assertForbidden();
    }

    public function test_unite_inconnue_refusee(): void
    {
        $this->actingAs($this->utilisateur('admin'))
            ->postJson(route('referentiel.articles.store'), $this->donnees(['unite_mesure' => 'TONNE']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('unite_mesure');
    }

    public function test_article_desactive_absent_de_la_recherche(): void
    {
        Article::create($this->donnees(['code' => 'A1', 'designation' => 'Stylo bleu']));
        Article::create($this->donnees(['code' => 'A2', 'designation' => 'Stylo rouge', 'actif' => false]));

        $this->actingAs($this->utilisateur('gestionnaire'))
            ->getJson(route('referentiel.articles.recherche', ['q' => 'stylo']))
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'A1');
    }

    public function test_categorie_avec_articles_non_supprimable(): void
    {
        Article::create($this->donnees(['code' => 'A1']));

        $this->actingAs($this->utilisateur('admin'))
            ->deleteJson(route('referentiel.categories.destroy', $this->categorie->id))
            ->assertStatus(422);
    }

    public function test_articles_frequents_ordre_limite_et_perimetre(): void
    {
        $a1 = Article::create($this->donnees(['code' => 'A1']));
        $a2 = Article::create($this->donnees(['code' => 'A2']));
        $rattache = Magasin::create(['code' => 'M1', 'libelle' => 'Rattaché']);
        $autre = Magasin::create(['code' => 'M2', 'libelle' => 'Autre']);

        $operateur = $this->utilisateur('operateur');
        $rattache->utilisateurs()->attach($operateur);

        $this->actingAs($operateur)
            ->putJson(route('referentiel.articles-frequents.update', $rattache->id), ['article_ids' => [$a2->id, $a1->id]])
            ->assertOk();

        $this->getJson(route('referentiel.articles-frequents.show', $rattache->id))
            ->assertJsonPath('data.0.code', 'A2')
            ->assertJsonPath('data.1.code', 'A1');

        $this->putJson(route('referentiel.articles-frequents.update', $autre->id), ['article_ids' => [$a1->id]])
            ->assertForbidden();

        config(['referentiel.articles_frequents_max' => 1]);
        $this->putJson(route('referentiel.articles-frequents.update', $rattache->id), ['article_ids' => [$a1->id, $a2->id]])
            ->assertStatus(422);
    }

    public function test_responsable_actif_voit_son_magasin(): void
    {
        $gestionnaire = $this->utilisateur('gestionnaire');
        $magasin = Magasin::create(['code' => 'M1', 'libelle' => 'M1']);
        $magasin->responsables()->create([
            'user_id' => $gestionnaire->id, 'role_responsable' => 'ADJOINT', 'date_debut' => now(), 'actif' => true,
        ]);

        $this->actingAs($gestionnaire)
            ->getJson(route('referentiel.articles-frequents.show', $magasin->id))
            ->assertOk();
    }
}
