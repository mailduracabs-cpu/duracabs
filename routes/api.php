<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\MobileApiController;
use App\Http\Controllers\Api\V1\AdminApiController;
use App\Http\Controllers\Api\V1\AiController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\CustomerJourneyController;
use App\Http\Controllers\Api\V1\EmailController;
use App\Http\Controllers\Api\V1\HomeController;

use App\Http\Controllers\Api\V1\MapsController;
use App\Http\Controllers\Api\V1\MasterController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\RatingController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\SelfDriveController;
use App\Http\Controllers\Api\V1\TaxiController;
use App\Http\Controllers\Api\V1\UploadController;
use App\Http\Controllers\Api\V1\WalletController;
use App\Http\Controllers\Api\V1\WhatsAppController;
use App\Http\Controllers\Api\V1\MediaController;
use App\Http\Controllers\Api\V1\CustomerLeadController;
use App\Http\Controllers\Api\V1\SmartHomeController;





/*
|--------------------------------------------------------------------------
| Smart Home CMS
|--------------------------------------------------------------------------
*/

Route::get('/smart-home', [
    SmartHomeController::class,
    'index',
]);

Route::get('/smart-home/hero-banners', [
    SmartHomeController::class,
    'heroBanners',
]);

Route::get('/smart-home/blocks/{blockType}', [
    SmartHomeController::class,
    'blocks',
])->where(
    'blockType',
    'hero|popular_route|featured_vehicle|recommended_vehicle|self_drive|offer|festival'
);
/*
|--------------------------------------------------------------------------
| inquiries User
|--------------------------------------------------------------------------
*/
Route::post('/inquiries', [CustomerLeadController::class, 'store']);
Route::get('/inquiries', [CustomerLeadController::class, 'index']);
/*
|--------------------------------------------------------------------------
| Authenticated User
|--------------------------------------------------------------------------
*/



Route::middleware('auth:sanctum')->get(
    '/user',
    function (Request $request) {
        return $request->user();
    }
);

/*
|--------------------------------------------------------------------------
| Old Mobile APIs
|--------------------------------------------------------------------------
|
| Existing Flutter/website compatibility ke liye in routes ko preserve
| kiya gaya hai.
|
*/

Route::post('/send-otp', [
    MobileApiController::class,
    'sendOtp',
]);

Route::post('/verify-otp', [
    MobileApiController::class,
    'verifyOtp',
]);

Route::get('/home', [
    MobileApiController::class,
    'home',
]);

Route::get('/cars', [
    MobileApiController::class,
    'cars',
]);

Route::get('/routes', [
    MobileApiController::class,
    'routes',
]);

Route::post('/booking', [
    MobileApiController::class,
    'booking',
]);

/*
|--------------------------------------------------------------------------
| Meta WhatsApp Cloud API Webhook
|--------------------------------------------------------------------------
|
| Meta verification aur incoming webhook dono public rahenge.
|
| GET  /api/whatsapp/webhook  -> Meta verification
| POST /api/whatsapp/webhook  -> Incoming messages/status updates
|
*/

Route::get('/whatsapp/webhook', [
    WhatsAppController::class,
    'verifyWebhook',
])->name('whatsapp.webhook.verify');

Route::post('/whatsapp/webhook', [
    WhatsAppController::class,
    'webhook',
])->name('whatsapp.webhook.receive');

/*
|--------------------------------------------------------------------------
| Dura Cabs App APIs V1
|--------------------------------------------------------------------------
*/

Route::get(
    '/self-drive/hero-vehicles',
    [SelfDriveController::class, 'heroVehicles']
);


Route::prefix('v1')->group(function () {
    // existing routes

    Route::get(
        'self-drive/hero-vehicles',
        [SelfDriveController::class, 'heroVehicles']
    );
Route::prefix('bike-rental')->group(function (): void {
    Route::get('/', [SelfDriveController::class, 'bikeIndex']);
    Route::post('/search', [SelfDriveController::class, 'bikeSearch']);
    Route::post('/check-availability', [SelfDriveController::class, 'bikeCheckAvailability']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/booking', [SelfDriveController::class, 'bikeBooking']);
        Route::get('/pending-booking', [SelfDriveController::class, 'bikePendingBooking']);

        Route::get('/booking/{bookingId}', [SelfDriveController::class, 'bookingDetails'])
            ->where('bookingId', '[A-Za-z0-9\-]+');

        Route::post('/booking/{bookingId}/cancel', [SelfDriveController::class, 'bikeCancelBooking'])
            ->where('bookingId', '[A-Za-z0-9\-]+');

        Route::post('/vendor-confirm', [SelfDriveController::class, 'vendorConfirm']);
        Route::post('/vendor-reject', [SelfDriveController::class, 'vendorReject']);
    });

    Route::get('/{id}', [SelfDriveController::class, 'bikeShow'])
        ->whereNumber('id');
});
    /*
    |--------------------------------------------------------------------------
    | Customer Journey Tracking
    |--------------------------------------------------------------------------
    */

    Route::prefix('customer-journey')->group(function (): void {
        Route::post('/search-performed', [
            CustomerJourneyController::class,
            'searchPerformed',
        ]);

        Route::post('/checkout-started', [
            CustomerJourneyController::class,
            'checkoutStarted',
        ]);

        Route::post('/payment-started', [
            CustomerJourneyController::class,
            'paymentStarted',
        ]);

        Route::post('/payment-succeeded', [
            CustomerJourneyController::class,
            'paymentSucceeded',
        ]);

        Route::post('/payment-failed', [
            CustomerJourneyController::class,
            'paymentFailed',
        ]);

        Route::post('/booking-completed', [
            CustomerJourneyController::class,
            'bookingCompleted',
        ]);

        Route::post('/booking-cancelled', [
            CustomerJourneyController::class,
            'bookingCancelled',
        ]);

        Route::get('/{identifier}', [
            CustomerJourneyController::class,
            'show',
        ])->where(
            'identifier',
            '[A-Za-z0-9\-]+'
        );
    });


    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */

    Route::post('/send-otp', [
        AuthController::class,
        'sendOtp',
    ]);

    Route::post('/verify-otp', [
        AuthController::class,
        'verifyOtp',
    ]);

    Route::post('/firebase-login', [
        AuthController::class,
        'firebaseLogin',
    ]);

    Route::post('/google-login', [
        AuthController::class,
        'googleLogin',
    ]);

    Route::post('/email-login', [
        AuthController::class,
        'emailLogin',
    ]);

    Route::post('/whatsapp-login', [
        AuthController::class,
        'whatsappLogin',
    ]);

    Route::post('/facebook-login', [
        AuthController::class,
        'facebookLogin',
    ]);

    Route::post('/apple-login', [
        AuthController::class,
        'appleLogin',
    ]);

    Route::post('/guest-login', [
        AuthController::class,
        'guestLogin',
    ]);
	Route::middleware('auth:sanctum')
    ->prefix('media')
    ->group(function (): void {
        Route::get(
            '/',
            [MediaController::class, 'index']
        );

        Route::post(
            '/',
            [MediaController::class, 'store']
        );

        Route::get(
            '/{media}',
            [MediaController::class, 'show']
        );

        Route::post(
            '/{media}/replace',
            [MediaController::class, 'replace']
        );

        Route::delete(
            '/{media}',
            [MediaController::class, 'destroy']
        );
    });
    /*
    |--------------------------------------------------------------------------
    | Ratings
    |--------------------------------------------------------------------------
    */

    Route::get('/ratings', [
        RatingController::class,
        'index',
    ]);

    Route::get('/my-ratings', [
        RatingController::class,
        'myRatings',
    ]);

    Route::post('/rating-store', [
        RatingController::class,
        'store',
    ]);

    Route::get('/rating-summary', [
        RatingController::class,
        'summary',
    ]);

    /*
    |--------------------------------------------------------------------------
    | Home
    |--------------------------------------------------------------------------
    */

    Route::get('/app-config', [
        HomeController::class,
        'appConfig',
    ]);

    Route::get('/home', [
        HomeController::class,
        'home',
    ]);

    Route::get('/banners', [
        HomeController::class,
        'banners',
    ]);

    Route::get('/popular-routes', [
        HomeController::class,
        'popularRoutes',
    ]);

    Route::get('/recommended-trips', [
        HomeController::class,
        'recommendedTrips',
    ]);

    Route::get('/contact-details', [
        HomeController::class,
        'contact',
    ]);

    Route::get('/settings', [
        HomeController::class,
        'settings',
    ]);

    Route::get('/tour-packages', [
        HomeController::class,
        'tourPackages',
    ]);

    Route::get('/ai-home-feed', [
        HomeController::class,
        'aiHomeFeed',
    ]);

    /*
    |--------------------------------------------------------------------------
    | Master Data
    |--------------------------------------------------------------------------
    */

    Route::get('/cities', [
        MasterController::class,
        'cities',
    ]);

    Route::get('/vehicle-categories', [
        MasterController::class,
        'vehicleCategories',
    ]);

    Route::get('/offers', [
        MasterController::class,
        'offers',
    ]);

    Route::get('/pages', [
        MasterController::class,
        'pages',
    ]);

    Route::get('/coupons-master', [
        MasterController::class,
        'coupons',
    ]);

    Route::get('/service-types', [
        MasterController::class,
        'serviceTypes',
    ]);

    /*
    |--------------------------------------------------------------------------
    | Taxi / With Driver
    |--------------------------------------------------------------------------
    */

    Route::get('/taxi/home', [
        TaxiController::class,
        'home',
    ]);

    Route::get('/taxi/categories', [
        TaxiController::class,
        'categories',
    ]);

    Route::get('/taxi/popular-destinations', [
        TaxiController::class,
        'popularDestinations',
    ]);

    Route::get('/taxi/airport-routes', [
        TaxiController::class,
        'airportRoutes',
    ]);

    Route::get('/one-way-routes', [
        TaxiController::class,
        'routes',
    ]);

    Route::get('/one-way-route-search', [
        TaxiController::class,
        'search',
    ]);

    Route::post('/taxi/fare-estimate', [
        TaxiController::class,
        'fareEstimate',
    ]);

    Route::post('/round-trip-estimate', [
        TaxiController::class,
        'roundTripFareEstimate',
    ]);

    Route::post('/taxi/round-trip-fare-estimate', [
        TaxiController::class,
        'roundTripFareEstimate',
    ]);

    Route::post('/taxi/round-trip-multicity', [
        TaxiController::class,
        'roundTripMultiCityEstimate',
    ]);

    Route::post('/taxi/local-fare-estimate', [
        TaxiController::class,
        'localFareEstimate',
    ]);

    /*
    |--------------------------------------------------------------------------
    | Self Drive / Without Driver
    |--------------------------------------------------------------------------
    */

    Route::prefix('self-drive')->group(function () {

        /*
        |----------------------------------------------------------------------
        | Public vehicle search
        |----------------------------------------------------------------------
        */

        Route::get('/', [
            SelfDriveController::class,
            'index',
        ]);

        Route::post('/search', [
            SelfDriveController::class,
            'search',
        ]);

        Route::post('/check-availability', [
            SelfDriveController::class,
            'checkAvailability',
        ]);


        /*
        |----------------------------------------------------------------------
        | Customer profile auto-fill
        |----------------------------------------------------------------------
        */

        Route::get('/customer-profile', [
            SelfDriveController::class,
            'customerProfile',
        ]);

        Route::post('/customer-profile-update', [
            SelfDriveController::class,
            'updateCustomerProfile',
        ]);

        /*
        |----------------------------------------------------------------------
        | Booking creation and customer status
        |----------------------------------------------------------------------
        */

        Route::post('/booking', [
            SelfDriveController::class,
            'booking',
        ]);

        Route::get('/pending-booking', [
            SelfDriveController::class,
            'pendingBooking',
        ]);

        Route::post('/booking-status', [
            SelfDriveController::class,
            'bookingStatus',
        ]);

        /*
        |----------------------------------------------------------------------
        | Vendor confirmation
        |----------------------------------------------------------------------
        */

        Route::post('/vendor-confirm', [
            SelfDriveController::class,
            'vendorConfirm',
        ]);

        Route::post('/vendor-reject', [
            SelfDriveController::class,
            'vendorReject',
        ]);

        /*
        |----------------------------------------------------------------------
        | Customer document verification
        |----------------------------------------------------------------------
        */

        Route::post('/aadhaar-upload', [
            SelfDriveController::class,
            'uploadAadhaar',
        ]);

        Route::post('/driving-licence-upload', [
            SelfDriveController::class,
            'uploadDrivingLicence',
        ]);

        Route::post('/documents-verify', [
            SelfDriveController::class,
            'verifyDocuments',
        ]);

        /*
        |----------------------------------------------------------------------
        | Pickup OTP and secure document unlock
        |----------------------------------------------------------------------
        */

        Route::post('/pickup-otp-generate', [
            SelfDriveController::class,
            'generatePickupOtp',
        ]);

        Route::post('/pickup-otp-verify', [
            SelfDriveController::class,
            'verifyPickupOtp',
        ]);

        /*
        |----------------------------------------------------------------------
        | Pickup inspection
        |----------------------------------------------------------------------
        */

        Route::post('/pickup-upload', [
            SelfDriveController::class,
            'pickupUpload',
        ]);

        /*
        |----------------------------------------------------------------------
        | End booking OTP
        |----------------------------------------------------------------------
        */

        Route::post('/end-otp-generate', [
            SelfDriveController::class,
            'generateEndOtp',
        ]);

        Route::post('/end-otp-verify', [
            SelfDriveController::class,
            'verifyEndOtp',
        ]);

        /*
        |----------------------------------------------------------------------
        | Return inspection
        |----------------------------------------------------------------------
        */

        Route::post('/drop-upload', [
            SelfDriveController::class,
            'dropUpload',
        ]);

        Route::post('/km-calculate', [
            SelfDriveController::class,
            'kmCalculate',
        ]);

        Route::post('/damage-detect', [
            SelfDriveController::class,
            'damageDetect',
        ]);

        /*
        |----------------------------------------------------------------------
        | Final bill and settlement
        |----------------------------------------------------------------------
        */

        Route::get('/final-bill/{bookingId}', [
            SelfDriveController::class,
            'finalBill',
        ])->where('bookingId', '[A-Za-z0-9\-]+');

        Route::get('/refund-status/{bookingId}', [
            SelfDriveController::class,
            'refundStatus',
        ])->where('bookingId', '[A-Za-z0-9\-]+');

        /*
        |----------------------------------------------------------------------
        | Self drive booking details
        |----------------------------------------------------------------------
        */

        Route::get('/booking/{bookingId}', [
            SelfDriveController::class,
            'bookingDetails',
        ])->where('bookingId', '[A-Za-z0-9\-]+');

        /*
        |----------------------------------------------------------------------
        | Vehicle detail
        |--------------------------------------------------------------------------
        |
        | Dynamic route hamesha sab static routes ke baad honi chahiye.
        |
        */

        Route::get('/{id}', [
            SelfDriveController::class,
            'show',
        ])->whereNumber('id');
    });

    /*
    |--------------------------------------------------------------------------
    | General Booking
    |--------------------------------------------------------------------------
    */

    Route::post('/booking', [
        BookingController::class,
        'store',
    ]);

    Route::get('/my-bookings', [
        BookingController::class,
        'index',
    ]);

    Route::get('/booking/{booking_id}', [
        BookingController::class,
        'show',
    ]);

    Route::post('/booking-cancel', [
        BookingController::class,
        'cancel',
    ]);

    Route::post('/booking/reschedule', [
        BookingController::class,
        'reschedule',
    ]);

    Route::post('/booking/confirm', [
        BookingController::class,
        'confirm',
    ]);

    Route::post('/booking/driver-details', [
        BookingController::class,
        'driverDetails',
    ]);

    Route::get('/coupons', [
        BookingController::class,
        'coupons',
    ]);

    Route::post('/apply-coupon', [
        BookingController::class,
        'applyCoupon',
    ]);

    /*
    |--------------------------------------------------------------------------
    | Authenticated Customer APIs
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')->group(function () {

        /*
        |----------------------------------------------------------------------
        | Profile
        |----------------------------------------------------------------------
        */

        Route::get('/profile', [
            ProfileController::class,
            'profile',
        ]);

        Route::post('/profile-update', [
            ProfileController::class,
            'update',
        ]);

        Route::get('/addresses', [
            ProfileController::class,
            'addresses',
        ]);

        Route::post('/address-save', [
            ProfileController::class,
            'saveAddress',
        ]);

        Route::post('/address-delete', [
            ProfileController::class,
            'deleteAddress',
        ]);

        Route::post('/device-token-save', [
            ProfileController::class,
            'saveDeviceToken',
        ]);

        /*
        |----------------------------------------------------------------------
        | Wallet
        |----------------------------------------------------------------------
        */

        Route::get('/wallet/balance', [
            WalletController::class,
            'balance',
        ]);

        Route::get('/wallet/history', [
            WalletController::class,
            'history',
        ]);

        Route::post('/wallet/add-money', [
            WalletController::class,
            'addMoney',
        ]);

        Route::post('/wallet/recharge/order', [
            WalletController::class,
            'createRechargeOrder',
        ]);

        Route::post('/wallet/recharge/verify', [
            WalletController::class,
            'verifyRechargePayment',
        ]);

        Route::post('/wallet/payment', [
            WalletController::class,
            'payment',
        ]);

        Route::post('/wallet/refund', [
            WalletController::class,
            'refund',
        ]);

        Route::post('/wallet/cashback', [
            WalletController::class,
            'cashback',
        ]);


        Route::post('/wallet/admin-recharge/send-otp', [
            WalletController::class,
            'sendAdminRechargeOtp',
        ]);

        Route::post('/wallet/admin-recharge/verify-otp', [
            WalletController::class,
            'verifyAdminRechargeOtp',
        ]);

        /*
        |----------------------------------------------------------------------
        | Logout and Account
        |----------------------------------------------------------------------
        */

        Route::post('/logout', [
            AuthController::class,
            'logout',
        ]);

        Route::post('/delete-account', [
            AuthController::class,
            'deleteAccount',
        ]);
    });

    /*
    |--------------------------------------------------------------------------
    | Inquiry
    |--------------------------------------------------------------------------
    */

  /*
    |--------------------------------------------------------------------------
    | notification
    |--------------------------------------------------------------------------
    */
	

	Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/device-token-save', [
        NotificationController::class,
        'saveDeviceToken',
    ]);

    Route::post('/device-token-remove', [
        NotificationController::class,
        'removeDeviceToken',
    ]);
});	
    /*
    |--------------------------------------------------------------------------
    | Reviews
    |--------------------------------------------------------------------------
    */

    Route::get('/reviews', [
        ReviewController::class,
        'index',
    ]);

    Route::post('/review-store', [
        ReviewController::class,
        'store',
    ]);

    Route::get('/my-reviews', [
        ReviewController::class,
        'myReviews',
    ]);

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    Route::get('/notifications', [
        NotificationController::class,
        'index',
    ]);

    Route::post('/notification-read', [
        NotificationController::class,
        'read',
    ]);

    Route::post('/notification-read-all', [
        NotificationController::class,
        'readAll',
    ]);

    Route::get('/notification-count', [
        NotificationController::class,
        'count',
    ]);

    Route::post('/send-notification', [
        NotificationController::class,
        'send',
    ]);

	
	    /*
    |--------------------------------------------------------------------------
    | WhatsApp Cloud API
    |--------------------------------------------------------------------------
    |
    | Internal sending endpoints.
    | In production, sending and testing routes should be protected by
    | authentication or admin middleware.
    |
    */

    Route::prefix('whatsapp')->group(function (): void {

        /*
        |--------------------------------------------------------------------------
        | Normal text message
        |--------------------------------------------------------------------------
        */

        Route::post('/send-message', [
            WhatsAppController::class,
            'sendMessage',
        ])->name('api.v1.whatsapp.send-message');

        /*
        |--------------------------------------------------------------------------
        | Booking messages
        |--------------------------------------------------------------------------
        */

        Route::post('/booking-confirmation', [
            WhatsAppController::class,
            'bookingConfirmation',
        ])->name('api.v1.whatsapp.booking-confirmation');

        Route::post('/booking-cancellation', [
            WhatsAppController::class,
            'bookingCancellation',
        ])->name('api.v1.whatsapp.booking-cancellation');

        /*
        |--------------------------------------------------------------------------
        | Driver and vehicle details
        |--------------------------------------------------------------------------
        */

        Route::post('/driver-details', [
            WhatsAppController::class,
            'driverDetails',
        ])->name('api.v1.whatsapp.driver-details');

        Route::post('/car-details', [
            WhatsAppController::class,
            'carDetails',
        ])->name('api.v1.whatsapp.car-details');

        /*
        |--------------------------------------------------------------------------
        | Payment messages
        |--------------------------------------------------------------------------
        */

        Route::post('/payment-reminder', [
            WhatsAppController::class,
            'paymentReminder',
        ])->name('api.v1.whatsapp.payment-reminder');

        /*
        |--------------------------------------------------------------------------
        | Offer and marketing messages
        |--------------------------------------------------------------------------
        */

        Route::post('/offer-message', [
            WhatsAppController::class,
            'offerMessage',
        ])->name('api.v1.whatsapp.offer-message');

        /*
        |--------------------------------------------------------------------------
        | Approved Meta template message
        |--------------------------------------------------------------------------
        */

        Route::post('/template-message', [
            WhatsAppController::class,
            'templateMessage',
        ])->name('api.v1.whatsapp.template-message');

        /*
        |--------------------------------------------------------------------------
        | Meta connection test
        |--------------------------------------------------------------------------
        */

        Route::get('/test-connection', [
            WhatsAppController::class,
            'testConnection',
        ])->name('api.v1.whatsapp.test-connection');
    });
    /*
    |--------------------------------------------------------------------------
    | Email
    |--------------------------------------------------------------------------
    */

    Route::post('/email/booking-confirmation', [
        EmailController::class,
        'bookingConfirmation',
    ]);

    Route::post('/email/invoice', [
        EmailController::class,
        'invoice',
    ]);

    Route::post('/email/payment-receipt', [
        EmailController::class,
        'paymentReceipt',
    ]);

    Route::post('/email/cancellation', [
        EmailController::class,
        'cancellation',
    ]);

    Route::post('/email/offer-newsletter', [
        EmailController::class,
        'offerNewsletter',
    ]);

    /*
    |--------------------------------------------------------------------------
    | Maps
    |--------------------------------------------------------------------------
    */

    Route::get('/maps/place-autocomplete', [
        MapsController::class,
        'placeAutocomplete',
    ]);

    Route::get('/maps/place-details', [
        MapsController::class,
        'placeDetails',
    ]);

    Route::post('/maps/distance', [
        MapsController::class,
        'distance',
    ]);

    Route::post('/maps/route', [
        MapsController::class,
        'route',
    ]);

    Route::get('/maps/geocode', [
        MapsController::class,
        'geocode',
    ]);

    Route::get('/maps/reverse-geocode', [
        MapsController::class,
        'reverseGeocode',
    ]);

    /*
    |--------------------------------------------------------------------------
    | Payment
    |--------------------------------------------------------------------------
    */

    Route::post('/payment/razorpay-order', [
        PaymentController::class,
        'razorpayOrder',
    ]);

    Route::post('/payment/verify', [
        PaymentController::class,
        'verify',
    ]);

    Route::post('/payment/status', [
        PaymentController::class,
        'status',
    ]);

    Route::post('/payment/refund', [
        PaymentController::class,
        'refund',
    ]);

    Route::match(
        ['get', 'post'],
        '/payment/webhook',
        [PaymentController::class, 'webhook']
    );

    Route::get('/payment/history', [
        PaymentController::class,
        'history',
    ]);

    /*
    |--------------------------------------------------------------------------
    | AI
    |--------------------------------------------------------------------------
    */

    Route::post('/ai/chat', [
        AiController::class,
        'chat',
    ]);

    Route::post('/ai/trip-planner', [
        AiController::class,
        'tripPlanner',
    ]);

    Route::post('/ai/image-generate', [
        AiController::class,
        'imageGenerate',
    ]);

    Route::post('/ai/recommendation', [
        AiController::class,
        'recommendation',
    ]);

    Route::post('/ai/search', [
        AiController::class,
        'search',
    ]);

    Route::post('/ai/self-drive-damage-detection', [
        AiController::class,
        'selfDriveDamageDetection',
    ]);

    Route::post('/ai/banner-suggestion', [
        AiController::class,
        'bannerSuggestion',
    ]);

    /*
    |--------------------------------------------------------------------------
    | Upload
    |--------------------------------------------------------------------------
    */

    Route::post('/upload/image', [
        UploadController::class,
        'uploadImage',
    ]);

    Route::post('/upload/multiple-images', [
        UploadController::class,
        'uploadMultipleImages',
    ]);

    Route::post('/upload/video', [
        UploadController::class,
        'uploadVideo',
    ]);

    Route::post('/upload/document', [
        UploadController::class,
        'uploadDocument',
    ]);

    Route::post('/upload/delete', [
        UploadController::class,
        'delete',
    ]);

    Route::post('/upload/replace', [
        UploadController::class,
        'replace',
    ]);

    /*
    |--------------------------------------------------------------------------
    | Admin App
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')
        ->prefix('admin')
        ->group(function (): void {
        Route::get('/dashboard', [
            AdminApiController::class,
            'dashboard',
        ]);

        Route::get('/customers', [
            AdminApiController::class,
            'customers',
        ]);

        Route::get('/bookings', [
            AdminApiController::class,
            'bookings',
        ]);

        Route::get('/inquiries', [
            AdminApiController::class,
            'inquiries',
        ]);

        Route::post('/banner/save', [
            AdminApiController::class,
            'saveBanner',
        ]);

        Route::post('/banner/delete', [
            AdminApiController::class,
            'deleteBanner',
        ]);

        Route::post('/offer/save', [
            AdminApiController::class,
            'saveOffer',
        ]);

        Route::post('/offer/delete', [
            AdminApiController::class,
            'deleteOffer',
        ]);

        Route::post('/car-image/save', [
            AdminApiController::class,
            'saveCarImage',
        ]);

        Route::post('/route-image/save', [
            AdminApiController::class,
            'saveRouteImage',
        ]);

        Route::post('/tour-image/save', [
            AdminApiController::class,
            'saveTourImage',
        ]);

        Route::post('/ai-image/approve', [
            AdminApiController::class,
            'approveAiImage',
        ]);

        Route::post('/ai-image/delete', [
            AdminApiController::class,
            'deleteAiImage',
        ]);
		Route::post('/payment/wallet', [
    PaymentController::class,
    'walletPayment',
]);

        Route::post('/ai-image/replace', [
            AdminApiController::class,
            'replaceAiImage',
        ]);

        Route::post('/notification/send', [
            AdminApiController::class,
            'sendNotification',
        ]);
    });
});