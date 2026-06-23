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
use App\Livewire\Homepage;
use App\Livewire\MyAccount;
use App\Livewire\MyOrderDetailPage;
use App\Livewire\MyOrdersPage;
use App\Livewire\Page;
use App\Livewire\ProductDetailedPage;
use App\Livewire\RazorePay;
use App\Livewire\RidesPage;
use App\Livewire\SuccessPage;
use App\Livewire\VendorRegistration;
use App\Livewire\TermsAndConditions;
use App\Livewire\EditQueryPage;
use Illuminate\Support\Facades\Route;

use App\Livewire\Counter;
 
//public

Route::get('optimize-clear', function(){
	Artisan::call('optimize:clear');
	return back();
});

Route::get('/foo', function () {
    Artisan::call('storage:link');
});

Route::get('/link', function () {        
    $target = '/home/fav1u6gtugzi/public_html/duracabs/storage/app/public';
   $shortcut = '/home/fav1u6gtugzi/public_html/';
   symlink($target, $shortcut);
});

Route::get("/storage-link", function () {
    $targetFolder = storage_path("app/public");
    $linkFolder = $_SERVER['DOCUMENT_ROOT'] . '/storage';
    dd($targetFolder, $linkFolder);
});


Route::get('auth/google', [SocialController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [SocialController::class, 'handleGoogleCallback']);
Route::get('/counter', Counter::class);
Route::get('/razorpay', RazorePay::class)->name('razorpay');
Route::get('/', Homepage::class);
Route::get('/cab-categories', CabCategoriesPage::class);
Route::get('/rides', RidesPage::class)->name('rides');
Route::get('/edit-query', EditQueryPage::class)->name('edit-query');
Route::get('/routes', action: RidesPage::class)->name('routes');
Route::get('/about-us', AboutUs::class)->name('about');
Route::get('/my-account', MyAccount::class)->name('my-account');
Route::get('/terms-and-conditions', TermsAndConditions::class)->name('terms-and-conditions');
Route::get('/cart', CartPage::class)->name('cart');
Route::get('/route/{slug}', ProductDetailedPage::class);
Route::get('/pages/{slug}', Page::class);
Route::get('/page/{slug}', Page::class);

Route::get('reviews', Reviews::class );

Route::get('/card', CaroselCard::class);

// guest
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class);
   
    Route::get('/forgot', ForgotPassword::class)->name('password.request');
    Route::get('/reset/{token}', ResetPassword::class)->name('password.reset');
});

Route::get('/vendor-register', VendorRegistration::class);
Route::get('/contact-us', ContactUs::class);


// auth
Route::middleware('auth')->group(function () {
    Route::get('/logout', function(){
        auth()->logout();
        return redirect('/');
    });
    Route::get('/checkout', CheckoutPage::class)->name('checkout');
    Route::get('/my-orders', MyOrdersPage::class);
    Route::get('/my-orders/{order_id}', MyOrderDetailPage::class)->name('my-orders.show');
    Route::get('/success', SuccessPage::class)->name('success');
    Route::get('/cancel', CancelPage::class)->name('cancel');

});
Route::get('sitemap.xml', [SiteMapController::class, 'index']);


Route::get('razorpay-payment', [RazorpayPaymentController::class, 'index']);
Route::post('razorpay-payment', [RazorpayPaymentController::class, 'store'])->name('razorpay.payment.store');





 