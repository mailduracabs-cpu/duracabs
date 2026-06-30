<?php

use App\Filament\Resources\ReviewsResource\Pages\Reviews;
use App\Http\Controllers\RazorpayPaymentController;
use App\Http\Controllers\SiteMapController;
use App\Http\Controllers\SocialController;
use App\Livewire\AboutUs;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\CabCategoriesPage;
use App\Livewire\CancelPage;
use App\Livewire\CaroselCard;
use App\Livewire\CartPage;
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
use App\Livewire\VendorRegistration;
use Illuminate\Support\Facades\Route;

Route::get('/', Homepage::class)->name('homepage');

Route::get('/cab-categories', CabCategoriesPage::class)->name('cab-categories');
Route::get('/rides', RidesPage::class)->name('rides');
Route::get('/routes', RidesPage::class)->name('routes');
Route::get('/edit-query', EditQueryPage::class)->name('edit-query');
Route::get('/about-us', AboutUs::class)->name('about');
Route::get('/my-account', MyAccount::class)->name('my-account');
Route::get('/terms-and-conditions', TermsAndConditions::class)->name('terms-and-conditions');
Route::get('/cart', CartPage::class)->name('cart');

Route::get('/route/{slug}', ProductDetailedPage::class)->name('route.show');
Route::get('/pages/{slug}', Page::class)->name('pages.show');
Route::get('/page/{slug}', Page::class)->name('page.show');

Route::get('/reviews', Reviews::class)->name('reviews');
Route::get('/card', CaroselCard::class)->name('card');
Route::get('/counter', Counter::class)->name('counter');

Route::get('/vendor-register', VendorRegistration::class)->name('vendor-register');
Route::get('/contact-us', ContactUs::class)->name('contact-us');

Route::get('/razorpay', RazorePay::class)->name('razorpay');

Route::get('/auth/google', [SocialController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [SocialController::class, 'handleGoogleCallback'])->name('auth.google.callback');

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
    Route::get('/my-orders/{order_id}', MyOrderDetailPage::class)->name('my-orders.show');
    Route::get('/success', SuccessPage::class)->name('success');
    Route::get('/cancel', CancelPage::class)->name('cancel');
});

Route::get('/sitemap.xml', [SiteMapController::class, 'index'])->name('sitemap');

Route::get('/razorpay-payment', [RazorpayPaymentController::class, 'index'])->name('razorpay.payment.index');
Route::post('/razorpay-payment', [RazorpayPaymentController::class, 'store'])->name('razorpay.payment.store');
