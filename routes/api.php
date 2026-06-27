<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MobileApiController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/send-otp', [MobileApiController::class, 'sendOtp']);
Route::post('/verify-otp', [MobileApiController::class, 'verifyOtp']);

Route::get('/home', [MobileApiController::class, 'home']);
Route::get('/cars', [MobileApiController::class, 'cars']);
Route::get('/routes', [MobileApiController::class, 'routes']);

Route::post('/booking', [MobileApiController::class, 'booking']);
