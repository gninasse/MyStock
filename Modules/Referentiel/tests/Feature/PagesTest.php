<?php

namespace Modules\Referentiel\Tests\Feature;

use Modules\Referentiel\Models\Magasin;

class PagesTest extends ReferentielTestCase
{
    public function test_les_ecrans_s_affichent_pour_l_admin(): void
    {
        $magasin = Magasin::create(['code' => 'M1', 'libelle' => 'Central']);
        $this->actingAs($this->utilisateur('admin'));

        foreach ([
            route('referentiel.magasins.index'),
            route('referentiel.magasins.responsables.index', $magasin->id),
            route('referentiel.structures.index'),
            route('referentiel.categories.index'),
            route('referentiel.articles.index'),
            route('referentiel.articles-frequents.index'),
        ] as $url) {
            $this->get($url)->assertOk()->assertSee('Référentiels');
        }
    }

    public function test_l_operateur_ne_voit_que_ses_ecrans(): void
    {
        $this->actingAs($this->utilisateur('operateur'));

        $this->get(route('referentiel.articles.index'))->assertOk()->assertDontSee('Magasins</p>', false);
        $this->get(route('referentiel.articles-frequents.index'))->assertOk();
        $this->get(route('referentiel.magasins.index'))->assertForbidden();
        $this->get(route('referentiel.structures.index'))->assertForbidden();
    }

    public function test_liste_vide_d_articles_frequents_acceptee(): void
    {
        $magasin = Magasin::create(['code' => 'M1', 'libelle' => 'Central']);

        $this->actingAs($this->utilisateur('admin'))
            ->putJson(route('referentiel.articles-frequents.update', $magasin->id), [])
            ->assertOk();
    }
}
