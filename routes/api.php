<?php

use App\Http\Controllers\EditalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Editais (FINEP Scraping)
|--------------------------------------------------------------------------
*/

Route::get('/editais', [EditalController::class, 'index']);
Route::get('/editais/{id}', [EditalController::class, 'show']);
