<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Serve Vue SPA for all routes
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
