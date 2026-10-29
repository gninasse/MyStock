<?php

use Illuminate\Support\Facades\Route;
use Modules\Stock\Http\Controllers\DocumentController;
use Modules\Stock\Http\Controllers\InventaireController;
use Modules\Stock\Http\Controllers\SaisieController;

Route::middleware(['auth'])->prefix('stock')->name('stock.')->group(function () {
    Route::prefix('documents')->name('documents.')->group(function () {
        Route::middleware('permission:stock.documents.index')->group(function () {
            Route::get('/', [DocumentController::class, 'index'])->name('index');
            Route::get('/data', [DocumentController::class, 'getData'])->name('data');
            Route::get('/{id}', [DocumentController::class, 'show'])->whereNumber('id')->name('show');
        });
        // Les droits de saisie dépendent du type du document : contrôlés dans le contrôleur
        Route::get('/{id}/modifier', [DocumentController::class, 'modifier'])->name('edit');
        Route::put('/{id}', [DocumentController::class, 'update'])->name('update');
        Route::delete('/{id}', [DocumentController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/annuler', [DocumentController::class, 'annuler'])->middleware('permission:stock.documents.annuler')->name('annuler');
    });

    Route::get('/{type}/nouvelle', [DocumentController::class, 'creer'])->whereIn('type', ['entrees', 'sorties'])->name('saisie.create');
    Route::post('/{type}', [DocumentController::class, 'store'])->whereIn('type', ['entrees', 'sorties'])->name('saisie.store');

    Route::prefix('inventaires')->name('inventaires.')->middleware('permission:stock.inventaires.saisir')->group(function () {
        Route::get('/', [InventaireController::class, 'index'])->name('index');
        Route::get('/data', [InventaireController::class, 'getData'])->name('data');
        Route::get('/nouveau', [InventaireController::class, 'create'])->name('create');
        Route::get('/magasins/{id}/articles', [InventaireController::class, 'articles'])->name('articles');
        Route::post('/', [InventaireController::class, 'store'])->name('store');
        Route::get('/{id}', [InventaireController::class, 'show'])->whereNumber('id')->name('show');
    });

    Route::middleware('permission:stock.entrees.saisir|stock.sorties.saisir')->group(function () {
        Route::get('/magasins/{id}/articles-frequents', [SaisieController::class, 'articlesFrequents'])->name('saisie.articles-frequents');
        Route::get('/magasins/{id}/stocks', [SaisieController::class, 'stocks'])->name('saisie.stocks');
        Route::get('/beneficiaires', [SaisieController::class, 'beneficiaires'])->name('saisie.beneficiaires');
    });
});
