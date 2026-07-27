<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\CommonController;

// Add these routes inside your existing Route::prefix('v1')->group(function () { ... }); block.
Route::get('/notifications', [NotificationController::class, 'index']);
Route::post('/notification-read', [NotificationController::class, 'read']);

Route::get('/reviews', [ReviewController::class, 'index']);
Route::post('/review', [ReviewController::class, 'store']);

Route::get('/contact-details', [CommonController::class, 'contactDetails']);
Route::get('/settings', [CommonController::class, 'settings']);
