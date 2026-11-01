<?php

namespace Modules\Referentiel\Tests\Feature;

use Modules\Referentiel\Services\Parametres;

class ParametresTest extends ReferentielTestCase
{
    public function test_valeurs_par_defaut_puis_enregistrees(): void
    {
        $this->assertSame(7, Parametres::get('mouvement_jours_retroactifs'));

        $this->actingAs($this->utilisateur('admin'))
            ->get(route('referentiel.parametres.index'))->assertOk()->assertSee('Nom de l\'organisation');

        $this->putJson(route('referentiel.parametres.update'), [
            'organisation_nom' => 'Hôpital Test',
            'mouvement_jours_retroactifs' => 30,
        ])->assertOk()->assertJson(['message' => 'Paramètres enregistrés.']);

        $this->assertSame(30, Parametres::get('mouvement_jours_retroactifs'));
        $this->assertSame('Hôpital Test', Parametres::get('organisation_nom'));
        $this->assertDatabaseHas('activity_log', ['description' => 'parametres_modifies']);
    }

    public function test_valeur_invalide_refusee(): void
    {
        $this->actingAs($this->utilisateur('admin'))
            ->putJson(route('referentiel.parametres.update'), ['organisation_nom' => 'X', 'mouvement_jours_retroactifs' => 999])
            ->assertStatus(422)->assertJsonValidationErrors('mouvement_jours_retroactifs');
    }

    public function test_reserve_a_l_administrateur(): void
    {
        $this->actingAs($this->utilisateur('gestionnaire'))
            ->get(route('referentiel.parametres.index'))->assertForbidden();
    }
}
