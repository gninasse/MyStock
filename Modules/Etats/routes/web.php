<?php

use Illuminate\Support\Facades\Route;
use Modules\Etats\Http\Controllers\EtatController;
use Modules\Etats\Http\Controllers\TableauDeBordController;

Route::middleware(['auth'])->group(function () {
    Route::get('/', fn () => redirect()->route('etats.tableau-de-bord'));
    Route::get('/tableau-de-bord', [TableauDeBordController::class, 'index'])
        ->middleware('permission:cores.dashboard.view')
        ->name('etats.tableau-de-bord');

    Route::prefix('etats')->name('etats.')->group(function () {
        Route::get('/stock-par-magasin', [EtatController::class, 'stockMagasin'])->middleware('permission:etats.stock-magasin')->name('stock-magasin');
        Route::get('/stock-par-categorie', [EtatController::class, 'stockCategorie'])->middleware('permission:etats.stock-categorie')->name('stock-categorie');
        Route::get('/journal-des-mouvements', [EtatController::class, 'journal'])->middleware('permission:etats.journal')->name('journal');
        Route::get('/fiche-de-stock', [EtatController::class, 'ficheStock'])->middleware('permission:etats.fiche-stock')->name('fiche-stock');
        Route::post('/impression', [EtatController::class, 'impression'])->name('impression');
    });
});
