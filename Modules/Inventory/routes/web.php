<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\ArticleController;
use Modules\Inventory\Http\Controllers\CategoryController;
use Modules\Inventory\Http\Controllers\StockEntryController;
use Modules\Inventory\Http\Controllers\StoreController;

Route::middleware(['auth'])->prefix('inventory')->name('inventory.')->group(function () {

    /* ── Référentiels ── */
    Route::get('stores/data', [StoreController::class, 'data'])->name('stores.data');
    Route::get('stores/{store}/frequent', [StoreController::class, 'frequentItems'])->name('stores.frequent');
    Route::post('stores/{store}/frequent', [StoreController::class, 'updateFrequentItems']);
    Route::resource('stores', StoreController::class);

    Route::get('articles/data', [ArticleController::class, 'data'])->name('articles.data');
    Route::get('articles/search', [ArticleController::class, 'search'])->name('articles.search');
    Route::post('articles/quick', [ArticleController::class, 'quickCreate'])->name('articles.quick');
    Route::resource('articles', ArticleController::class);

    Route::get('categories/data', [CategoryController::class, 'data'])->name('categories.data');
    Route::resource('categories', CategoryController::class);

    /* ── Flux stock ── */
    Route::post('entries/{entry}/validate', [StockEntryController::class, 'validate'])->name('entries.validate');
    Route::post('entries/draft', [StockEntryController::class, 'saveDraft'])->name('entries.draft');
    Route::get('entries/store/{store}/info', [StockEntryController::class, 'storeInfo'])->name('entries.store-info');
    Route::resource('entries', StockEntryController::class);

});
