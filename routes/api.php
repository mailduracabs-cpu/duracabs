<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MobileApiController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\MasterController;
use App\Http\Controllers\Api\V1\TaxiController;
use App\Http\Controllers\Api\V1\SelfDriveController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\InquiryController;
use App\Http\Controllers\Api\V1\NotificationController;

/*
|--------------------------------------------------------------------------
| Legacy Mobile APIs
|--------------------------------------------------------------------------
| These routes are kept so current Flutter app and website integrations
| keep working while /api/v1 is developed and tested.
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/send-otp', [MobileApiController::class, 'sendOtp']);
Route::post('/verify-otp', [MobileApiController::class, 'verifyOtp']);
Route::get('/home', [MobileApiController::class, 'home']);
Route::get('/cars', [MobileApiController::class, 'cars']);
Route::get('/routes', [MobileApiController::class, 'routes']);
Route::post('/booking', [MobileApiController::class, 'booking']);

/*
|--------------------------------------------------------------------------
| Dura Cabs API V1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Authentication
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // App / Home
    Route::get('/app-config', [HomeController::class, 'appConfig']);
    Route::get('/home', [HomeController::class, 'home']);
    Route::get('/contact-details', [HomeController::class, 'contact']);
    Route::get('/settings', [HomeController::class, 'settings']);

    // Master Data
    Route::get('/cities', [MasterController::class, 'cities']);
    Route::get('/vehicle-categories', [MasterController::class, 'vehicleCategories']);
    Route::get('/offers', [MasterController::class, 'offers']);
    Route::get('/pages', [MasterController::class, 'pages']);

    // Taxi
    Route::get('/taxi/home', [TaxiController::class, 'home']);
    Route::get('/one-way-routes', [TaxiController::class, 'routes']);
    Route::get('/one-way-route-search', [TaxiController::class, 'search']);
    Route::post('/fare-estimate', [TaxiController::class, 'fareEstimate']);

    // Self Drive
    Route::get('/self-drive', [SelfDriveController::class, 'index']);
    Route::get('/self-drive/{slug}', [SelfDriveController::class, 'show']);

    // Booking / Coupons
    Route::post('/booking', [BookingController::class, 'store']);
    Route::get('/my-bookings', [BookingController::class, 'index']);
    Route::get('/booking/{booking_id}', [BookingController::class, 'show']);
    Route::post('/booking-cancel', [BookingController::class, 'cancel']);
    Route::get('/coupons', [BookingController::class, 'coupons']);
    Route::post('/apply-coupon', [BookingController::class, 'applyCoupon']);

    // Profile / Address
    Route::get('/profile', [ProfileController::class, 'profile']);
    Route::post('/profile-update', [ProfileController::class, 'update']);
    Route::get('/addresses', [ProfileController::class, 'addresses']);
    Route::post('/address-save', [ProfileController::class, 'saveAddress']);
    Route::post('/address-delete', [ProfileController::class, 'deleteAddress']);

    // Inquiry
    Route::post('/create-inquiry', [InquiryController::class, 'store']);
    Route::get('/my-inquiries', [InquiryController::class, 'index']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notification-read', [NotificationController::class, 'read']);
});
