<?php

use App\Filament\Resources\ReviewsResource\Pages\Reviews;
use App\Http\Controllers\RazorpayPaymentController;
use App\Http\Controllers\SiteMapController;
use App\Http\Controllers\SocialController;
use App\Http\Controllers\SecureBookingController;
use App\Http\Controllers\InvoiceController;

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

use Illuminate\Support\Facades\Route;

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

Route::get('/route/{slug}', ProductDetailedPage::class)->name('route.show');
Route::get('/pages/{slug}', Page::class)->name('pages.show');


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

Route::get('/partner/login', PartnerLogin::class)
    ->name('partner.login');

Route::middleware('auth')->group(function () {

Route::get('/partner/dashboard', function () {
    return redirect('/transporter');
		})->name('partner.dashboard');

});

/*
|--------------------------------------------------------------------------
| Customer
|--------------------------------------------------------------------------
*/

Route::get('/my-account', MyAccount::class)->name('my-account');

Route::middleware('guest')->group(function () {

    Route::get('/login', Login::class)->name('login');

    Route::get('/register', Register::class)->name('register');

    Route::get('/forgot', ForgotPassword::class)->name('password.request');

    Route::get('/reset/{token}', ResetPassword::class)->name('password.reset');

});

Route::middleware('auth')->group(function () {

    Route::get('/logout', function () {

        auth()->logout();

        request()->session()->invalidate();

        request()->session()->regenerateToken();

        return redirect('/');

    })->name('logout');

    Route::get('/checkout', CheckoutPage::class)->name('checkout');

    Route::get('/my-orders', MyOrdersPage::class)->name('my-orders');

    Route::get('/my-orders/{order_id}', MyOrderDetailPage::class)
        ->name('my-orders.show');

    Route::get('/success', SuccessPage::class)->name('success');


    Route::get(
        '/booking/{booking}',
        [SecureBookingController::class, 'show']
    )->name('booking.secure.show');

    Route::get(
        '/booking/{booking}/invoice',
        [InvoiceController::class, 'download']
    )->name('orders.invoice');

    Route::get('/cancel', CancelPage::class)->name('cancel');

});

/*
|--------------------------------------------------------------------------
| Google Login
|--------------------------------------------------------------------------
*/

Route::get('/auth/google', [SocialController::class, 'redirectToGoogle'])
    ->name('auth.google');

Route::get('/auth/google/callback', [SocialController::class, 'handleGoogleCallback'])
    ->name('auth.google.callback');


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