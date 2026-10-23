<?php

use App\Http\Controllers\Api\V1\CustomerAppReleaseController;
use Illuminate\Support\Facades\Route;

// Loaded once from routes/api.php; route stays outside auth:sanctum.
Route::get('/v1/app-release', CustomerAppReleaseController::class)->middleware('throttle:60,1');
