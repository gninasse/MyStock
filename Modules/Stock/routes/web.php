<?php

use Illuminate\Support\Facades\Route;
use Modules\Stock\Http\Controllers\DocumentController;
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

    Route::middleware('permission:stock.entrees.saisir|stock.sorties.saisir')->group(function () {
        Route::get('/magasins/{id}/articles-frequents', [SaisieController::class, 'articlesFrequents'])->name('saisie.articles-frequents');
        Route::get('/magasins/{id}/stocks', [SaisieController::class, 'stocks'])->name('saisie.stocks');
        Route::get('/beneficiaires', [SaisieController::class, 'beneficiaires'])->name('saisie.beneficiaires');
    });
});
