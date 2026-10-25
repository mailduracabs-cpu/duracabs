<?php

use App\Http\Controllers\Api\V1\PartnerController;
use App\Services\VendorMonthlyBilling;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum', 'throttle:60,1'])->prefix('api/v1/partner')->group(function (): void {
    Route::get('monthly-bills', [PartnerController::class, 'monthlyBills']);
    Route::get('monthly-bills/{bill}', [PartnerController::class, 'monthlyBill'])->whereNumber('bill');
    Route::get('monthly-bills/{bill}/copy', [PartnerController::class, 'monthlyBillCopy'])->whereNumber('bill');
});
Route::middleware(['web', 'auth:web'])->get('/admin/vendor-monthly-bills/{bill}/copy', function (int $bill) {
    abort_unless(auth()->user()?->canUseAdminLogin(), 403);
    $copy = app(VendorMonthlyBilling::class)->billCopy($bill);
    return response($copy['bytes'])->header('Content-Type', $copy['mime'])
        ->header('Content-Disposition', 'inline; filename="' . $copy['filename'] . '"')
        ->header('Cache-Control', 'private, no-store')->header('X-Content-Type-Options', 'nosniff');
})->whereNumber('bill')->name('vendor-lifecycle.admin-bill');

// Short-lived bearer link delivered to the intended vendor by WhatsApp. App/history access remains authenticated.
Route::middleware(['web', 'signed', 'throttle:20,1'])->get('/vendor-monthly-bill/{bill}/download', function (int $bill) {
    $copy = app(VendorMonthlyBilling::class)->billCopy($bill);
    return response($copy['bytes'])->header('Content-Type', $copy['mime'])
        ->header('Content-Disposition', 'attachment; filename="' . $copy['filename'] . '"')
        ->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer')
        ->header('X-Robots-Tag', 'noindex, nofollow')->header('X-Content-Type-Options', 'nosniff');
})->whereNumber('bill')->name('vendor-lifecycle.shared-bill');
