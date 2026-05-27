<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EditalController;

Route::get('/editais', [EditalController::class, 'index'])->name('editais.index');

Route::get('/editais/{id}', [EditalController::class, 'show'])->name('editais.show');

Route::get('/', function () {
    return view('welcome');
});

