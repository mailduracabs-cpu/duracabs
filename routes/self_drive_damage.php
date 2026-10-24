<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SelfDriveDamageReportController;
Route::middleware(['web','auth'])->prefix('admin/self-drive-damage')->group(function () {
    Route::get('/',[SelfDriveDamageReportController::class,'index'])->name('self-drive-damage.index');
    Route::get('/{report}',[SelfDriveDamageReportController::class,'show'])->whereNumber('report')->name('self-drive-damage.show');
    Route::get('/{report}/photo/{key}',[SelfDriveDamageReportController::class,'photo'])->whereNumber('report')->where('key','(before|after)_[0-9]+')->name('self-drive-damage.photo');
});
