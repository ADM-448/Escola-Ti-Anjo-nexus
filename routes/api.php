<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlatformController;

Route::post('/v1/pitch-report', [PlatformController::class, 'generatePitchReport']);
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
