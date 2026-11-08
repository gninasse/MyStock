<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\ActivityController;
use Modules\Core\Http\Controllers\AuthController;
use Modules\Core\Http\Controllers\DashboardController;
use Modules\Core\Http\Controllers\ModuleController;
use Modules\Core\Http\Controllers\PermissionController;
use Modules\Core\Http\Controllers\ProfileController;
use Modules\Core\Http\Controllers\RoleController;
use Modules\Core\Http\Controllers\UserController;

Route::get('login', [AuthController::class, 'showLogin'])->name('login');
Route::post('login', [AuthController::class, 'login'])->name('login.post');
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::prefix('cores')->name('cores.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('permission:cores.modules.index');

        // Routes pour la gestion des utilisateurs
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index')->middleware('permission:cores.users.index');
            Route::get('/data', [UserController::class, 'getData'])->name('data')->middleware('permission:cores.users.index');
            Route::get('/{id}', [UserController::class, 'show'])->name('show')->middleware('permission:cores.users.index');
            Route::post('/', [UserController::class, 'store'])->name('store')->middleware('permission:cores.users.store');
            Route::put('/{id}', [UserController::class, 'update'])->name('update')->middleware('permission:cores.users.update');
            Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy')->middleware('permission:cores.users.destroy');
            Route::post('/{id}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password')->middleware('permission:cores.users.reset-password');
            Route::post('/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status')->middleware('permission:cores.users.toggle-status');
            Route::put('/{id}/profile', [UserController::class, 'updateProfile'])->name('update-profile')->middleware('permission:cores.users.update');
            Route::post('/{id}/avatar', [UserController::class, 'updateAvatar'])->name('update-avatar')->middleware('permission:cores.users.update');

            // Gestion des rôles via AJAX sur la page show
            Route::get('/{id}/roles/available', [UserController::class, 'getAvailableRoles'])->name('available-roles')->middleware('permission:cores.users.roles');
            Route::post('/{id}/roles', [UserController::class, 'assignRole'])->name('assign-role')->middleware('permission:cores.users.roles');
            Route::delete('/{id}/roles', [UserController::class, 'removeRole'])->name('remove-role')->middleware('permission:cores.users.roles');
            Route::delete('/{id}/permissions', [UserController::class, 'removePermission'])->name('remove-permission')->middleware('permission:cores.permissions.toggle');
            Route::get('/{id}/permissions/available', [UserController::class, 'getAvailablePermissions'])->name('available-permissions')->middleware('permission:cores.permissions.toggle');
            Route::post('/{id}/permissions', [UserController::class, 'assignPermissions'])->name('assign-permissions')->middleware('permission:cores.permissions.toggle');
        });

        // Routes pour la gestion des rôles
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('index')->middleware('permission:cores.roles.index');
            Route::get('/data', [RoleController::class, 'getData'])->name('data')->middleware('permission:cores.roles.index');
            Route::get('/{id}', [RoleController::class, 'show'])->name('show')->middleware('permission:cores.roles.index');
            Route::post('/', [RoleController::class, 'store'])->name('store')->middleware('permission:cores.roles.store');
            Route::put('/{id}', [RoleController::class, 'update'])->name('update')->middleware('permission:cores.roles.update');
            Route::delete('/{id}', [RoleController::class, 'destroy'])->name('destroy')->middleware('permission:cores.roles.destroy');

            // Gestion des permissions du rôle
            Route::get('/{id}/permissions', [RoleController::class, 'getPermissions'])->name('permissions')->middleware('permission:cores.roles.index');
            Route::post('/{id}/toggle-permission', [RoleController::class, 'togglePermission'])->name('toggle-permission')->middleware('permission:cores.permissions.toggle');
        });

        // Routes pour la gestion des permissions
        Route::prefix('permissions')->name('permissions.')->group(function () {
            Route::get('/', [PermissionController::class, 'index'])->name('index')->middleware('permission:cores.permissions.index');
            Route::post('/toggle', [PermissionController::class, 'toggle'])->name('toggle')->middleware('permission:cores.permissions.toggle');
            Route::post('/sync', [ModuleController::class, 'syncPermissions'])->name('sync')->middleware('permission:cores.permissions.sync');
        });

        // Routes pour la gestion des modules
        Route::prefix('modules')->name('modules.')->group(function () {
            Route::get('/', [ModuleController::class, 'index'])->name('index')->middleware('permission:cores.modules.index');
            Route::post('/install', [ModuleController::class, 'install'])->name('install')->middleware('permission:cores.modules.install');
            Route::get('/{slug}', [ModuleController::class, 'show'])->name('show')->middleware('permission:cores.modules.show');
            Route::post('/{slug}/enable', [ModuleController::class, 'enable'])->name('enable')->middleware('permission:cores.modules.enable');
            Route::post('/{slug}/disable', [ModuleController::class, 'disable'])->name('disable')->middleware('permission:cores.modules.disable');
            Route::delete('/{slug}', [ModuleController::class, 'uninstall'])->name('uninstall')->middleware('permission:cores.modules.uninstall');
            Route::get('/{slug}/configure', [ModuleController::class, 'configure'])->name('configure')->middleware('permission:cores.modules.configure');
            Route::post('/{slug}/configure', [ModuleController::class, 'updateConfiguration'])->name('configure.update')->middleware('permission:cores.modules.configure');
        });

        // Routes pour la gestion des activités
        Route::prefix('activities')->name('activities.')->group(function () {
            Route::get('/', [ActivityController::class, 'index'])->name('index')->middleware('permission:cores.activities.index');
            Route::get('/data', [ActivityController::class, 'getData'])->name('data')->middleware('permission:cores.activities.data');
            Route::get('/{id}', [ActivityController::class, 'show'])->name('show')->middleware('permission:cores.activities.show');
        });

        // Profil de l'utilisateur connecté : pas de permission, chacun gère le sien
        Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
        Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
        Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    });
});
