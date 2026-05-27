<?php

use App\Http\Controllers\EditalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Editais (FINEP Scraping)
|--------------------------------------------------------------------------
*/

Route::get('/editais', [EditalController::class, 'apiIndex']);

Route::apiResource('editais', EditalController::class);
