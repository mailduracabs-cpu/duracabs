<?php

use App\Http\Controllers\Api\V1\TaxiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Paste these routes inside your existing Route::prefix('v1')->group(...)
|--------------------------------------------------------------------------
*/

Route::get('/taxi/home', [TaxiController::class, 'home']);
Route::get('/one-way-routes', [TaxiController::class, 'routes']);
Route::get('/one-way-route-search', [TaxiController::class, 'search']);
Route::post('/fare-estimate', [TaxiController::class, 'fareEstimate']);
