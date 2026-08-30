<?php

use App\Filament\Resources\ReviewsResource\Pages\Reviews;
use App\Http\Controllers\RazorpayPaymentController;
use App\Http\Controllers\SiteMapController;
use App\Http\Controllers\SocialController;
use App\Http\Controllers\SecureBookingController;
use App\Http\Controllers\InvoiceController;
use App\Models\AppMedia;

use App\Livewire\AboutUs;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;

use App\Livewire\CabCategoriesPage;
use App\Livewire\CancelPage;
use App\Livewire\CaroselCard;

use App\Livewire\CheckoutPage;
use App\Livewire\ContactUs;
use App\Livewire\Counter;
use App\Livewire\EditQueryPage;
use App\Livewire\Homepage;
use App\Livewire\MyAccount;
use App\Livewire\MyOrderDetailPage;
use App\Livewire\MyOrdersPage;
use App\Livewire\Page;
use App\Livewire\ProductDetailedPage;
use App\Livewire\RazorePay;
use App\Livewire\RidesPage;
use App\Livewire\SuccessPage;
use App\Livewire\TermsAndConditions;
use App\Livewire\PartnerDashboard;

use App\Livewire\VendorRegistration;
use App\Livewire\PartnerLogin;




use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::permanentRedirect('/page/self-drive-service-in-agra', '/pages/self-drive-service-in-agra');
Route::permanentRedirect('/page/b2b', '/vendor-register');
Route::permanentRedirect('/page/event-planner', '/');
Route::permanentRedirect('/tours', '/');

/*
|--------------------------------------------------------------------------
| Homepage
|--------------------------------------------------------------------------
*/

Route::get('/', Homepage::class)->name('homepage');

Route::get('/cab-categories', CabCategoriesPage::class)->name('cab-categories');
Route::get('/rides', RidesPage::class)->name('rides');
Route::get('/routes', RidesPage::class)->name('routes');
Route::get('/edit-query', EditQueryPage::class)->name('edit-query');

Route::get('/about-us', AboutUs::class)->name('about');
Route::get('/contact-us', ContactUs::class)->name('contact-us');
Route::get('/terms-and-conditions', TermsAndConditions::class)->name('terms-and-conditions');
Route::view('/cookie-policy', 'cookie-policy')->name('cookie-policy');

Route::get('/route/{slug}', ProductDetailedPage::class)
    ->name('route.show');

Route::get('/self-drive/{slug}', ProductDetailedPage::class)
    ->name('self-drive.show');

Route::get('/bike-rental/{slug}', ProductDetailedPage::class)
    ->name('bike-rental.show');

Route::get('/pages/{slug}', Page::class)
    ->name('pages.show');

Route::get('/blog/{slug}', Page::class)
    ->name('blog.show');

Route::get('/tour/{slug}', Page::class)
    ->name('tour.show');


Route::get('/reviews', Reviews::class)->name('reviews');
Route::get('/card', CaroselCard::class)->name('card');
Route::get('/counter', Counter::class)->name('counter');

/*
|--------------------------------------------------------------------------
| Partner Module
|--------------------------------------------------------------------------
*/

Route::get('/partner/register', VendorRegistration::class)
    ->name('partner.register');

Route::get('/vendor-register', VendorRegistration::class)
    ->name('vendor-register');

Route::middleware('guest:vendor')->group(function () {
    Route::get('/partner/login', PartnerLogin::class)
        ->name('partner.login');
});

Route::middleware('auth:vendor')->group(function () {
    Route::get('/partner/dashboard', function () {
        return redirect('/transporter');
    })->name('partner.dashboard');
});

/*
|--------------------------------------------------------------------------
| Customer
|--------------------------------------------------------------------------
*/

Route::middleware('guest:customer')->group(function () {
    Route::get('/login', Login::class)->name('login');

    Route::get('/register', Register::class)->name('register');

    Route::get('/forgot', ForgotPassword::class)
        ->name('password.request');

    Route::get('/reset/{token}', ResetPassword::class)
        ->name('password.reset');
});

Route::middleware('auth:customer')->group(function () {
    Route::get('/my-account', MyAccount::class)
        ->name('my-account');

    Route::get('/logout', function () {
        Auth::guard('customer')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    })->name('logout');

    Route::get('/checkout', CheckoutPage::class)
        ->name('checkout');

    Route::get('/my-orders', MyOrdersPage::class)
        ->name('my-orders');

    Route::get('/my-orders/{order_id}', MyOrderDetailPage::class)
        ->name('my-orders.show');

    Route::get('/success', SuccessPage::class)
        ->name('success');

    Route::get(
        '/booking/{booking}',
        [SecureBookingController::class, 'show']
    )->name('booking.secure.show');

    Route::get(
        '/booking/{booking}/invoice',
        [InvoiceController::class, 'download']
    )->name('orders.invoice');

    Route::get('/cancel', CancelPage::class)
        ->name('cancel');
});


/*
|--------------------------------------------------------------------------
| Secure Admin Vehicle Documents
|--------------------------------------------------------------------------
|
| Vehicle RC, insurance and PUC documents are stored privately. These
| routes stream them only to an authenticated admin instead of exposing
| private storage through /storage URLs.
|
*/

Route::middleware('auth:admin')->prefix('admin/vehicle-documents')->group(function () {
    Route::get('/{media}', function (AppMedia $media) {
        abort_unless(
            in_array($media->module, ['vehicle-rc', 'vehicle-insurance', 'vehicle-puc'], true),
            404
        );

        $disk = (string) ($media->disk ?: 'local');
        $path = (string) $media->path;

        abort_if($path === '' || ! Storage::disk($disk)->exists($path), 404);

        $absolutePath = Storage::disk($disk)->path($path);
        $mimeType = $media->mime_type ?: Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream';
        $fileName = $media->original_name ?: $media->name ?: basename($path);

        return response()->file($absolutePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . addslashes($fileName) . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=300',
        ]);
    })->whereNumber('media')->name('admin.vehicle-documents.view');

    Route::get('/{media}/download', function (AppMedia $media) {
        abort_unless(
            in_array($media->module, ['vehicle-rc', 'vehicle-insurance', 'vehicle-puc'], true),
            404
        );

        $disk = (string) ($media->disk ?: 'local');
        $path = (string) $media->path;

        abort_if($path === '' || ! Storage::disk($disk)->exists($path), 404);

        $fileName = $media->original_name ?: $media->name ?: basename($path);

        return Storage::disk($disk)->download($path, $fileName);
    })->whereNumber('media')->name('admin.vehicle-documents.download');
});

/*
|--------------------------------------------------------------------------
| Google Login
|--------------------------------------------------------------------------
*/

Route::middleware('guest:customer')->group(function () {
    Route::get(
        '/auth/google',
        [SocialController::class, 'redirectToGoogle']
    )->name('auth.google');

    Route::get(
        '/auth/google/callback',
        [SocialController::class, 'handleGoogleCallback']
    )->name('auth.google.callback');
});


/*
|--------------------------------------------------------------------------
| Google Search Console
|--------------------------------------------------------------------------
|
| Admin-authenticated OAuth routes for connecting the Dura Cabs SEO Control
| Center with Google Search Console. Customer Google Login remains separate.
|
*/

Route::middleware('auth:admin')->group(function () {

    Route::get(
        '/auth/google/search-console/connect',
        [SocialController::class, 'redirectToSearchConsole']
    )->name('search-console.connect');

    Route::get(
        '/auth/google/search-console/callback',
        [SocialController::class, 'handleSearchConsoleCallback']
    )->name('search-console.callback');

    Route::post(
        '/auth/google/search-console/disconnect',
        [SocialController::class, 'disconnectSearchConsole']
    )->name('search-console.disconnect');

});


/*
|--------------------------------------------------------------------------
| Secure Public Invoice Share
|--------------------------------------------------------------------------
*/

Route::middleware('signed')->group(function () {

    Route::get(
        '/invoice/share/{booking}',
        [InvoiceController::class, 'shared']
    )->name('invoice.shared');

    Route::get(
        '/agreement/self-drive/share/{booking}',
        [InvoiceController::class, 'sharedSelfDriveAgreement']
    )->name('self-drive.agreement.shared');

});

/*
|--------------------------------------------------------------------------
| Razorpay
|--------------------------------------------------------------------------
*/

Route::get('/razorpay', RazorePay::class)->name('razorpay');

Route::get('/razorpay-payment', [RazorpayPaymentController::class, 'index'])
    ->name('razorpay.payment.index');

Route::post('/razorpay-payment', [RazorpayPaymentController::class, 'store'])
    ->name('razorpay.payment.store');

/*
|--------------------------------------------------------------------------
| Sitemap
|--------------------------------------------------------------------------
*/

Route::get('/sitemap.xml', [SiteMapController::class, 'index'])
    ->name('sitemap');