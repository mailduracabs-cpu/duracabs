<?php
use App\Http\Controllers\Api\V1\TripReviewController;
use Illuminate\Support\Facades\Route;

// Loaded by TripReviewServiceProvider, independently of the existing API file.
foreach (['api/v1/trip-reviews', 'api/v1/partner/trip-reviews'] as $prefix) {
    Route::prefix($prefix)->middleware(['api', 'auth:sanctum', 'throttle:60,1'])->group(function (): void {
        Route::get('/profile', [TripReviewController::class, 'profile']);
        Route::get('/booking/{type}/{trip}', [TripReviewController::class, 'show'])
            ->where('type', 'taxi|self_drive|bike_rental')->where('trip', '[A-Za-z0-9\-]+');
        Route::post('/booking/{type}/{trip}', [TripReviewController::class, 'store'])
            ->where('type', 'taxi|self_drive|bike_rental')->where('trip', '[A-Za-z0-9\-]+')->middleware('throttle:10,1');
    });
}
