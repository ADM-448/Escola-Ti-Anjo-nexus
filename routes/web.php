<?php


use App\Http\Controllers\PlatformController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PlatformController::class, 'indexWeb']);