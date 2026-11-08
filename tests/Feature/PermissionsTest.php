<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Routes;
use Modules\Core\Models\User;
use Modules\Referentiel\Database\Seeders\RolesSeeder;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Contrôle des droits de toute l'application : chaque route protégée, matrice des rôles,
 * administration réservée, rôle super-admin intouchable par un administrateur.
 */
class PermissionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Routes accessibles à tout utilisateur connecté, avec la raison.
     * Les droits y sont vérifiés dans le contrôleur (type de document, magasin, statut).
     */
    protected const SANS_MIDDLEWARE_PERMISSION = [
        'cores.profile' => 'son propre profil',
        'cores.profile.avatar' => 'son propre profil',
        'cores.profile.password' => 'son propre mot de passe',
        'accueil' => 'redirection vers le tableau de bord',
        'stock.saisie.create' => 'permission vérifiée selon le type (entrées, sorties, transferts)',
        'stock.saisie.store' => 'permission vérifiée selon le type',
        'stock.documents.edit' => 'permission vérifiée selon le type du brouillon',
        'stock.documents.update' => 'permission vérifiée selon le type du brouillon',
        'stock.documents.destroy' => 'permission vérifiée selon le type du brouillon',
        'etats.impression' => 'etats.export vérifiée dans le contrôleur',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    protected function utilisateur(string $role): User
    {
        return User::factory()->create()->assignRole($role);
    }

    public function test_chaque_route_est_protegee(): void
    {
        $publiques = ['login', 'login.post', 'logout'];
        $fautives = [];

        foreach (Routes::getRoutes() as $route) {
            /** @var Route $route */
            $nom = $route->getName();
            $uri = $route->uri();
            if (str_starts_with($uri, '_') || in_array($uri, ['up', 'storage/{path}'], true) || in_array($nom, $publiques, true)) {
                continue;
            }
            $middlewares = $route->gatherMiddleware();
            $connecte = collect($middlewares)->contains(fn ($m) => str_starts_with($m, 'auth'));
            $permission = collect($middlewares)->contains(fn ($m) => str_starts_with($m, 'permission:'));

            if (! $connecte || (! $permission && ! array_key_exists($nom, self::SANS_MIDDLEWARE_PERMISSION))) {
                $fautives[] = implode('|', $route->methods()).' '.$uri.' ('.($nom ?? 'sans nom').')';
            }
        }

        $this->assertSame([], $fautives, "Routes sans contrôle d'accès :\n".implode("\n", $fautives));
    }

    public function test_seul_le_super_admin_administre_roles_permissions_et_modules(): void
    {
        $reservees = ['cores.roles.index', 'cores.permissions.index', 'cores.modules.index', 'cores.dashboard'];

        foreach (['admin', 'gestionnaire', 'operateur', 'consultant'] as $role) {
            $user = $this->utilisateur($role);
            foreach ($reservees as $route) {
                $this->actingAs($user)->get(route($route))->assertForbidden();
            }
            $this->actingAs($user)->postJson(route('cores.permissions.toggle'))->assertForbidden();
            $this->actingAs($user)->postJson(route('cores.modules.install'))->assertForbidden();
        }

        $super = $this->utilisateur('super-admin');
        foreach ($reservees as $route) {
            $this->actingAs($super)->get(route($route))->assertOk();
        }
    }

    public function test_seul_l_admin_gere_utilisateurs_parametres_et_journal(): void
    {
        $pages = ['cores.users.index', 'cores.activities.index', 'referentiel.parametres.index', 'referentiel.parametres.entete'];

        foreach (['gestionnaire', 'operateur', 'consultant'] as $role) {
            $user = $this->utilisateur($role);
            foreach ($pages as $route) {
                $this->actingAs($user)->get(route($route))->assertForbidden();
            }
            // Escalade de privilèges impossible
            $this->actingAs($user)->postJson(route('cores.users.assign-role', $user->id), ['role_id' => Role::findByName('admin')->id])->assertForbidden();
        }

        $admin = $this->utilisateur('admin');
        foreach ($pages as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
    }

    public function test_l_admin_ne_touche_pas_au_super_admin(): void
    {
        $admin = $this->utilisateur('admin');
        $super = $this->utilisateur('super-admin');
        $superAdmin = Role::findByName('super-admin');
        $this->actingAs($admin);

        // Rôle super-admin : ni proposé, ni attribuable, ni retirable
        $this->getJson(route('cores.users.available-roles', $admin->id))->assertOk()
            ->assertJsonMissing(['name' => 'super-admin']);
        $this->postJson(route('cores.users.assign-role', $admin->id), ['role_id' => $superAdmin->id])->assertForbidden();
        $this->deleteJson(route('cores.users.remove-role', $super->id), ['role_id' => $superAdmin->id])->assertForbidden();
        $this->assertFalse($admin->fresh()->hasRole('super-admin'));

        // Compte super-admin : ni modifiable, ni réinitialisable, ni désactivable
        $this->postJson(route('cores.users.reset-password', $super->id))->assertForbidden();
        $this->postJson(route('cores.users.toggle-status', $super->id))->assertForbidden();
        $this->deleteJson(route('cores.users.destroy', $super->id))->assertForbidden();

        // Permissions directes : réservées au super-admin
        $this->getJson(route('cores.users.available-permissions', $admin->id))->assertForbidden();

        // Rôles métier : attribuables par l'admin
        $gestionnaire = User::factory()->create();
        $this->postJson(route('cores.users.assign-role', $gestionnaire->id), ['role_id' => Role::findByName('gestionnaire')->id])->assertOk();
        $this->assertTrue($gestionnaire->fresh()->hasRole('gestionnaire'));

        // Le super-admin, lui, peut tout
        $this->actingAs($super)->postJson(route('cores.users.assign-role', $admin->id), ['role_id' => $superAdmin->id])->assertOk();
    }

    public function test_matrice_des_roles(): void
    {
        $attendu = [
            'admin' => [
                'oui' => ['cores.users.roles', 'cores.users.update', 'cores.activities.index', 'referentiel.parametres.gerer', 'referentiel.entete.gerer', 'stock.documents.annuler', 'etats.statistiques'],
                'non' => ['cores.roles.index', 'cores.permissions.toggle', 'cores.modules.install', 'cores.users.destroy'],
            ],
            'gestionnaire' => [
                'oui' => ['stock.entrees.saisir', 'stock.sorties.saisir', 'stock.transferts.saisir', 'stock.inventaires.saisir', 'etats.analyses', 'etats.statistiques'],
                'non' => ['stock.documents.annuler', 'referentiel.entete.gerer', 'referentiel.parametres.gerer', 'cores.users.index'],
            ],
            'operateur' => [
                'oui' => ['stock.entrees.saisir', 'stock.transferts.receptionner', 'etats.stock-magasin', 'etats.alertes'],
                'non' => ['stock.sorties.saisir', 'etats.analyses', 'etats.statistiques', 'etats.journal', 'cores.users.index'],
            ],
            'consultant' => [
                'oui' => ['referentiel.magasins.tous', 'etats.analyses', 'etats.statistiques', 'etats.fiche-stock'],
                'non' => ['stock.entrees.saisir', 'etats.journal', 'etats.alertes', 'cores.users.index'],
            ],
        ];

        foreach ($attendu as $role => $droits) {
            $r = Role::findByName($role);
            foreach ($droits['oui'] as $p) {
                $this->assertTrue($r->hasPermissionTo($p), "{$role} devrait avoir {$p}");
            }
            foreach ($droits['non'] as $p) {
                $this->assertFalse($r->hasPermissionTo($p), "{$role} ne devrait pas avoir {$p}");
            }
        }
    }
}
