<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
// use App\Http\Controllers\JogoController;
use App\Http\Controllers\EditalController;

// Rota de Login (pública)
Route::post('/login', [AuthController::class, 'login']);

// Rotas públicas de jogos
// Route::get('/jogos', [JogoController::class, 'index']);
// Route::get('/jogos/{id}', [JogoController::class, 'show']);
// Route::post('/jogos', [JogoController::class, 'store']);
// Route::put('/jogos/{id}', [JogoController::class, 'update']);
// Route::delete('/jogos/{id}', [JogoController::class, 'destroy']);

Route::get('/editais', [EditalController::class, 'index']);
Route::get('/editais/{id}', [EditalController::class, 'show']);