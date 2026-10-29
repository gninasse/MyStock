<?php

namespace Modules\Referentiel\Tests\Feature;

use Modules\Core\Models\Role;

class RolesTest extends ReferentielTestCase
{
    public function test_les_quatre_roles_existent(): void
    {
        $this->assertEqualsCanonicalizing(
            ['admin', 'gestionnaire', 'operateur', 'consultant'],
            Role::pluck('name')->all()
        );
    }

    public function test_matrice_des_droits(): void
    {
        $admin = $this->utilisateur('admin');
        $gestionnaire = $this->utilisateur('gestionnaire');
        $operateur = $this->utilisateur('operateur');
        $consultant = $this->utilisateur('consultant');

        $this->assertTrue($admin->can('referentiel.articles.update'));
        $this->assertTrue($admin->can('cores.users.store'));

        foreach ([$gestionnaire, $operateur] as $user) {
            $this->assertTrue($user->can('referentiel.articles.store'));
            $this->assertFalse($user->can('referentiel.articles.update'));
            $this->assertFalse($user->can('referentiel.magasins.store'));
            $this->assertTrue($user->can('referentiel.articles-frequents.manage'));
        }

        $this->assertFalse($consultant->can('referentiel.articles.store'));
        $this->assertTrue($consultant->can('referentiel.magasins.tous'));
    }

    public function test_le_seeder_est_rejouable(): void
    {
        $this->seed(\Modules\Referentiel\Database\Seeders\RolesSeeder::class);

        $this->assertSame(4, Role::count());
    }
}
