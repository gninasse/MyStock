<?php

use Illuminate\Support\Facades\Route;
use Modules\Organization\Http\Controllers\OrganizationController;

Route::middleware(['auth'])->prefix('organizations')->name('organization.')->group(function () {
    // Directions AJAX CRUD
    Route::get('directions/data', [OrganizationController::class, 'directionsData'])->name('directions.data');
    Route::post('directions', [OrganizationController::class, 'storeDirection'])->name('directions.store');
    Route::get('directions/{direction}/edit', [OrganizationController::class, 'editDirection'])->name('directions.edit');
    Route::put('directions/{direction}', [OrganizationController::class, 'updateDirection'])->name('directions.update');
    Route::delete('directions/{direction}', [OrganizationController::class, 'destroyDirection'])->name('directions.destroy');

    // Services AJAX CRUD
    Route::get('services/data', [OrganizationController::class, 'servicesData'])->name('services.data');
    Route::post('services', [OrganizationController::class, 'storeService'])->name('services.store');
    Route::get('services/{service}/edit', [OrganizationController::class, 'editService'])->name('services.edit');
    Route::put('services/{service}', [OrganizationController::class, 'updateService'])->name('services.update');
    Route::delete('services/{service}', [OrganizationController::class, 'destroyService'])->name('services.destroy');

    // Units AJAX CRUD
    Route::get('units/data', [OrganizationController::class, 'unitsData'])->name('units.data');
    Route::post('units', [OrganizationController::class, 'storeUnit'])->name('units.store');
    Route::get('units/{unit}/edit', [OrganizationController::class, 'editUnit'])->name('units.edit');
    Route::put('units/{unit}', [OrganizationController::class, 'updateUnit'])->name('units.update');
    Route::delete('units/{unit}', [OrganizationController::class, 'destroyUnit'])->name('units.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::resource('organizations', OrganizationController::class)->names('organization');
});
