<?php

namespace Modules\Referentiel\Tests\Feature;

use Illuminate\Support\Facades\Notification;
use Modules\Referentiel\Models\Magasin;
use Modules\Referentiel\Notifications\ResponsableAssigneNotification;

class MagasinTest extends ReferentielTestCase
{
    public function test_admin_cree_un_magasin_avec_code_en_majuscules(): void
    {
        $this->actingAs($this->utilisateur('admin'))
            ->postJson(route('referentiel.magasins.store'), ['code' => 'mag-001', 'libelle' => 'Magasin Central'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('magasins', ['code' => 'MAG-001', 'actif' => true]);
        $this->assertDatabaseHas('activity_log', [
            'module' => 'referentiel',
            'subject_type' => Magasin::class,
            'event' => 'created',
        ]);
    }

    public function test_code_magasin_unique(): void
    {
        Magasin::create(['code' => 'MAG-001', 'libelle' => 'A']);

        $this->actingAs($this->utilisateur('admin'))
            ->postJson(route('referentiel.magasins.store'), ['code' => 'MAG-001', 'libelle' => 'B'])
            ->assertStatus(422)
            ->assertJsonPath('errors.code.0', 'Ce code existe déjà. Veuillez en choisir un autre.');
    }

    public function test_gestionnaire_ne_peut_pas_gerer_les_magasins(): void
    {
        $this->actingAs($this->utilisateur('gestionnaire'))
            ->postJson(route('referentiel.magasins.store'), ['code' => 'X', 'libelle' => 'X'])
            ->assertForbidden();
    }

    public function test_desactivation_et_liste_avec_responsables(): void
    {
        $admin = $this->utilisateur('admin');
        $magasin = Magasin::create(['code' => 'MAG-001', 'libelle' => 'Central']);
        $magasin->responsables()->create([
            'user_id' => $admin->id, 'role_responsable' => 'PRINCIPAL', 'date_debut' => now(), 'actif' => true,
        ]);

        $this->actingAs($admin)->postJson(route('referentiel.magasins.toggle-status', $magasin->id))->assertOk();
        $this->assertFalse($magasin->fresh()->actif);

        $this->getJson(route('referentiel.magasins.data'))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('rows.0.responsables_actuels', mb_substr($admin->name, 0, 1).'. '.$admin->last_name.' (Principal)');
    }

    public function test_regles_des_responsables(): void
    {
        Notification::fake();
        $admin = $this->utilisateur('admin');
        $jean = $this->utilisateur('gestionnaire');
        $marie = $this->utilisateur('gestionnaire');
        $magasin = Magasin::create(['code' => 'MAG-001', 'libelle' => 'Central']);
        $url = route('referentiel.magasins.responsables.store', $magasin->id);

        $this->actingAs($admin)
            ->postJson($url, ['user_id' => $jean->id, 'role_responsable' => 'PRINCIPAL', 'date_debut' => '2026-01-01'])
            ->assertOk();
        Notification::assertSentTo($jean, ResponsableAssigneNotification::class);

        // Doublon utilisateur
        $this->postJson($url, ['user_id' => $jean->id, 'role_responsable' => 'ADJOINT', 'date_debut' => '2026-02-01'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Cet utilisateur est déjà responsable actif de ce magasin.');

        // Un seul principal
        $this->postJson($url, ['user_id' => $marie->id, 'role_responsable' => 'PRINCIPAL', 'date_debut' => '2026-02-01'])
            ->assertStatus(422);

        // Adjoints multiples
        $this->postJson($url, ['user_id' => $marie->id, 'role_responsable' => 'ADJOINT', 'date_debut' => '2026-02-01'])
            ->assertOk();

        // Terminer le principal libère la place et garde l'historique
        $principal = $magasin->responsablesActifs()->where('role_responsable', 'PRINCIPAL')->first();
        $this->postJson(route('referentiel.magasins.responsables.terminer', [$magasin->id, $principal->id]), ['date_fin' => '2026-03-01'])
            ->assertOk();

        $this->getJson(route('referentiel.magasins.responsables.data', $magasin->id))
            ->assertJsonCount(1, 'actuels')
            ->assertJsonCount(2, 'historique');

        $this->postJson($url, ['user_id' => $admin->id, 'role_responsable' => 'PRINCIPAL', 'date_debut' => '2026-03-02'])
            ->assertOk();
    }
}
