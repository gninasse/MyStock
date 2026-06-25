<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('cores.dashboard');
    }

    return view('welcome');
});

Route::get('/dashboard', function () {
    return redirect()->route('cores.dashboard');
})->name('dashboard');
