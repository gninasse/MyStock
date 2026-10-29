<?php

namespace Modules\Referentiel\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Core\Models\Role;
use Modules\Core\Services\PermissionService;
use Nwidart\Modules\Facades\Module;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Les quatre rôles métier de MyStock (SFD 2.1 et 2.2), assemblés à partir
 * du config/roles.php de chaque module actif. Un nom de permission terminé
 * par « .* » désigne toutes les permissions du préfixe.
 * Rejouable : les permissions des rôles sont resynchronisées à chaque exécution.
 */
class RolesSeeder extends Seeder
{
    public function run(PermissionService $permissionService): void
    {
        $roles = [];

        foreach (Module::allEnabled() as $module) {
            $permissionService->syncModulePermissions($module->getName());

            $fichier = $module->getPath().'/config/roles.php';
            if (! file_exists($fichier)) {
                continue;
            }

            foreach (require $fichier as $nom => $definition) {
                $roles[$nom]['description'] ??= $definition['description'] ?? null;
                $roles[$nom]['permissions'] = array_merge($roles[$nom]['permissions'] ?? [], $definition['permissions'] ?? []);
            }
        }

        $toutes = Permission::pluck('name');

        foreach ($roles as $nom => $definition) {
            $permissions = collect($definition['permissions'])
                ->flatMap(fn (string $p) => Str::endsWith($p, '.*')
                    ? $toutes->filter(fn ($nomPermission) => Str::startsWith($nomPermission, Str::beforeLast($p, '*')))
                    : [$p])
                ->unique()
                ->values();

            $role = Role::firstOrCreate(
                ['name' => $nom, 'guard_name' => 'web'],
                ['description' => $definition['description']]
            );
            $role->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
