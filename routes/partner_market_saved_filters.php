<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\PartnerMarketSavedFilterController as Controller;
Route::middleware(['auth:sanctum',\App\Http\Middleware\PartnerEndpointThrottle::class.':90,1'])->group(function(){
 Route::get('v1/partner/marketplace/saved-filter',[Controller::class,'show']);
 Route::get('v1/partner/marketplace/filter-events',[Controller::class,'feed']);
});
Route::post('v1/partner/marketplace/saved-filter',[Controller::class,'update'])->middleware(['auth:sanctum',\App\Http\Middleware\PartnerEndpointThrottle::class.':20,1']);
