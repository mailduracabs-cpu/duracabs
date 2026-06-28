<?php

use App\Http\Controllers\Api\V1\ProfileController;
use Illuminate\Support\Facades\Route;

// Add these inside Route::prefix('v1')->group(function () { ... });
// Use auth:sanctum after real Sanctum token is enabled.

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'profile']);
    Route::post('/profile-update', [ProfileController::class, 'update']);
    Route::get('/addresses', [ProfileController::class, 'addresses']);
    Route::post('/address-save', [ProfileController::class, 'saveAddress']);
    Route::post('/address-delete', [ProfileController::class, 'deleteAddress']);
    Route::post('/logout', [ProfileController::class, 'logout']);
});
