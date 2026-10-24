<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CustomerReferralController;
Route::middleware(['api', 'auth:sanctum', 'throttle:30,1'])->prefix('api/v1/referrals')->group(function () {
    Route::get('/', [CustomerReferralController::class, 'show']);
    Route::post('/apply', [CustomerReferralController::class, 'apply']);
});
