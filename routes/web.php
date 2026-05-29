<?php

use Illuminate\Support\Facades\Route;

// Redireciona raiz para avisar que é uma API
Route::get('/', function () {
    return response()->json([
        'message' => 'API REST. Por favor, utilize os endpoints em /api.'
    ]);
});
