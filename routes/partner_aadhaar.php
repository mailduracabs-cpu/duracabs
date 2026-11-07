<?php
use App\Http\Controllers\Api\V1\PartnerAadhaarController;
use Illuminate\Support\Facades\Route;

// Require this ONCE at the TOP LEVEL of routes/api.php, not inside an existing prefix.
Route::prefix('v1/partner/identity/aadhaar')->middleware(['auth:sanctum','App\Http\Middleware\PartnerEndpointThrottle:30,1'])->group(function (): void {
    Route::get('/', [PartnerAadhaarController::class,'status']);
    Route::post('/sessions', [PartnerAadhaarController::class,'start'])->middleware('App\Http\Middleware\PartnerEndpointThrottle:5,1');
    Route::post('/sessions/{sessionId}/sync', [PartnerAadhaarController::class,'sync'])->whereUuid('sessionId');
});
// This return page has NO authority to verify an account. Client query parameters
// and OAuth callback claims are deliberately ignored; only server-to-server sync verifies.
Route::get('v1/partner/identity/aadhaar/return', fn () => response()->view('partner.aadhaar-return')
    ->header('Cache-Control','no-store')->header('Referrer-Policy','no-referrer')
    ->header('Content-Security-Policy',"default-src 'none'; style-src 'unsafe-inline'; base-uri 'none'; frame-ancestors 'none'"));
