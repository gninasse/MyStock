<?php

namespace Modules\Referentiel\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Models\Role;
use Modules\Core\Services\PermissionService;
use Spatie\Permission\PermissionRegistrar;

/**
 * Les quatre rôles métier de MyStock (SFD 2.1 et 2.2).
 * Rejouable : les permissions des rôles sont resynchronisées à chaque exécution.
 */
class RolesSeeder extends Seeder
{
    public function run(PermissionService $permissionService): void
    {
        $permissionService->syncModulePermissions('Core');
        $permissionService->syncModulePermissions('Referentiel');

        $referentiel = array_keys(require module_path('Referentiel', 'config/permissions.php'));

        $roles = [
            'admin' => [
                'description' => 'Administrateur : tous les droits métier, utilisateurs et journal d\'audit',
                'permissions' => array_merge($referentiel, [
                    'cores.dashboard.view',
                    'cores.users.index',
                    'cores.users.store',
                    'cores.users.update',
                    'cores.users.reset-password',
                    'cores.users.toggle-status',
                    'cores.activities.index',
                    'cores.activities.data',
                    'cores.activities.show',
                    'cores.activities.export',
                ]),
            ],
            'gestionnaire' => [
                'description' => 'Gestionnaire : entrées, sorties, inventaires et articles fréquents de ses magasins',
                'permissions' => [
                    'cores.dashboard.view',
                    'referentiel.articles.index',
                    'referentiel.articles.store',
                    'referentiel.articles-frequents.manage',
                ],
            ],
            'operateur' => [
                'description' => 'Opérateur : réception (entrées) et articles fréquents de ses magasins',
                'permissions' => [
                    'cores.dashboard.view',
                    'referentiel.articles.index',
                    'referentiel.articles.store',
                    'referentiel.articles-frequents.manage',
                ],
            ],
            'consultant' => [
                'description' => 'Consultant : consultation des états de tous les magasins',
                'permissions' => [
                    'cores.dashboard.view',
                    'referentiel.magasins.tous',
                ],
            ],
        ];

        foreach ($roles as $name => $definition) {
            $role = Role::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $definition['description']]
            );
            $role->syncPermissions($definition['permissions']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
