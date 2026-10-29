<?php

namespace Modules\Referentiel\Tests\Feature;

use Modules\Referentiel\Models\Direction;

class StructureTest extends ReferentielTestCase
{
    public function test_creation_de_l_arborescence(): void
    {
        $this->actingAs($this->utilisateur('admin'));

        $direction = $this->postJson(route('referentiel.structures.store', 'direction'), ['code' => 'dir-001', 'libelle' => 'Direction Générale'])
            ->assertOk()
            ->assertJsonPath('message', 'Direction créée avec succès')
            ->json('data');
        $service = $this->postJson(route('referentiel.structures.store', 'service'), ['code' => 'SERV-001', 'libelle' => 'Informatique', 'direction_id' => $direction['id']])
            ->assertOk()->json('data');
        $this->postJson(route('referentiel.structures.store', 'unite'), ['code' => 'UNIT-001', 'libelle' => 'Support', 'service_id' => $service['id']])
            ->assertOk();

        $this->getJson(route('referentiel.structures.arbre'))
            ->assertJsonPath('data.0.code', 'DIR-001')
            ->assertJsonPath('data.0.services.0.unites.0.code', 'UNIT-001');
    }

    public function test_service_sans_direction_refuse(): void
    {
        $this->actingAs($this->utilisateur('admin'))
            ->postJson(route('referentiel.structures.store', 'service'), ['code' => 'S', 'libelle' => 'S'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('direction_id');
    }

    public function test_suppression_refusee_si_sous_niveaux(): void
    {
        $direction = Direction::create(['code' => 'D', 'libelle' => 'D']);
        $direction->services()->create(['code' => 'S', 'libelle' => 'S']);

        $this->actingAs($this->utilisateur('admin'))
            ->deleteJson(route('referentiel.structures.destroy', ['direction', $direction->id]))
            ->assertStatus(422);
    }

    public function test_niveau_inconnu(): void
    {
        $this->actingAs($this->utilisateur('admin'))
            ->postJson(route('referentiel.structures.store', 'pole'), ['code' => 'P', 'libelle' => 'P'])
            ->assertNotFound();
    }
}
