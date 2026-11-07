<?php
use Illuminate\Support\Facades\Route;
Route::post('v1/partner/live-bookings/{id}/outcome',[\App\Http\Controllers\Api\V1\LiveBookingsController::class,'updateOutcome'])
 ->whereNumber('id')->middleware(['auth:sanctum',\App\Http\Middleware\PartnerEndpointThrottle::class.':20,1']);
