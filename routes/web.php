<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/editais');
});

Route::get('/editais', function () {
    return response()->file(resource_path('views/editais/index.html'));
});

Route::get('/editais/show', function () {
    return response()->file(resource_path('views/editais/show.html'));
});
