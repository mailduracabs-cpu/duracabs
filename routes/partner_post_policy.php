<?php
use Illuminate\Support\Facades\Route;
Route::get('v1/partner/marketplace/post-policy',[\App\Http\Controllers\Api\V1\PartnerMarketPostPolicyController::class,'show'])
 ->middleware(['auth:sanctum',\App\Http\Middleware\PartnerEndpointThrottle::class.':60,1']);
