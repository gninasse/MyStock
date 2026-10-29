<?php

use Illuminate\Support\Facades\Route;
use Modules\Referentiel\Http\Controllers\ArticleController;
use Modules\Referentiel\Http\Controllers\ArticleFrequentController;
use Modules\Referentiel\Http\Controllers\CategorieController;
use Modules\Referentiel\Http\Controllers\MagasinController;
use Modules\Referentiel\Http\Controllers\ResponsableMagasinController;
use Modules\Referentiel\Http\Controllers\StructureController;

Route::middleware(['auth'])->prefix('referentiel')->name('referentiel.')->group(function () {
    // Magasins
    Route::prefix('magasins')->name('magasins.')->group(function () {
        Route::middleware('permission:referentiel.magasins.index')->group(function () {
            Route::get('/', [MagasinController::class, 'index'])->name('index');
            Route::get('/data', [MagasinController::class, 'getData'])->name('data');
            Route::get('/{id}', [MagasinController::class, 'show'])->whereNumber('id')->name('show');
        });
        Route::post('/', [MagasinController::class, 'store'])->middleware('permission:referentiel.magasins.store')->name('store');
        Route::put('/{id}', [MagasinController::class, 'update'])->middleware('permission:referentiel.magasins.update')->name('update');
        Route::post('/{id}/toggle-status', [MagasinController::class, 'toggleStatus'])->middleware('permission:referentiel.magasins.toggle-status')->name('toggle-status');

        // Responsables et utilisateurs rattachés
        Route::middleware('permission:referentiel.responsables.manage')->group(function () {
            Route::get('/{id}/responsables', [ResponsableMagasinController::class, 'index'])->name('responsables.index');
            Route::get('/{id}/responsables/data', [ResponsableMagasinController::class, 'getData'])->name('responsables.data');
            Route::post('/{id}/responsables', [ResponsableMagasinController::class, 'store'])->name('responsables.store');
            Route::post('/{id}/responsables/{responsableId}/terminer', [ResponsableMagasinController::class, 'terminer'])->name('responsables.terminer');
            Route::post('/{id}/utilisateurs', [ResponsableMagasinController::class, 'rattacher'])->name('utilisateurs.store');
            Route::delete('/{id}/utilisateurs/{userId}', [ResponsableMagasinController::class, 'detacher'])->name('utilisateurs.destroy');
        });
    });

    // Structures organisationnelles (niveau : direction, service, unite)
    Route::prefix('structures')->name('structures.')->group(function () {
        Route::middleware('permission:referentiel.structures.index')->group(function () {
            Route::get('/', [StructureController::class, 'index'])->name('index');
            Route::get('/arbre', [StructureController::class, 'arbre'])->name('arbre');
        });
        Route::post('/{niveau}', [StructureController::class, 'store'])->middleware('permission:referentiel.structures.store')->name('store');
        Route::put('/{niveau}/{id}', [StructureController::class, 'update'])->middleware('permission:referentiel.structures.update')->name('update');
        Route::delete('/{niveau}/{id}', [StructureController::class, 'destroy'])->middleware('permission:referentiel.structures.destroy')->name('destroy');
    });

    // Catégories
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::middleware('permission:referentiel.categories.index')->group(function () {
            Route::get('/', [CategorieController::class, 'index'])->name('index');
            Route::get('/data', [CategorieController::class, 'getData'])->name('data');
            Route::get('/{id}', [CategorieController::class, 'show'])->whereNumber('id')->name('show');
        });
        Route::post('/', [CategorieController::class, 'store'])->middleware('permission:referentiel.categories.store')->name('store');
        Route::put('/{id}', [CategorieController::class, 'update'])->middleware('permission:referentiel.categories.update')->name('update');
        Route::delete('/{id}', [CategorieController::class, 'destroy'])->middleware('permission:referentiel.categories.destroy')->name('destroy');
    });

    // Articles
    Route::prefix('articles')->name('articles.')->group(function () {
        Route::middleware('permission:referentiel.articles.index')->group(function () {
            Route::get('/', [ArticleController::class, 'index'])->name('index');
            Route::get('/data', [ArticleController::class, 'getData'])->name('data');
            Route::get('/recherche', [ArticleController::class, 'recherche'])->name('recherche');
            Route::get('/{id}', [ArticleController::class, 'show'])->whereNumber('id')->name('show');
        });
        Route::post('/', [ArticleController::class, 'store'])->middleware('permission:referentiel.articles.store')->name('store');
        Route::put('/{id}', [ArticleController::class, 'update'])->middleware('permission:referentiel.articles.update')->name('update');
        Route::post('/{id}/toggle-status', [ArticleController::class, 'toggleStatus'])->middleware('permission:referentiel.articles.toggle-status')->name('toggle-status');
    });

    // Articles fréquents par magasin
    Route::prefix('articles-frequents')->name('articles-frequents.')->middleware('permission:referentiel.articles-frequents.manage')->group(function () {
        Route::get('/', [ArticleFrequentController::class, 'index'])->name('index');
        Route::get('/{magasinId}', [ArticleFrequentController::class, 'show'])->name('show');
        Route::put('/{magasinId}', [ArticleFrequentController::class, 'update'])->name('update');
    });
});
