<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Player flow (no login, no personal data)
|--------------------------------------------------------------------------
*/
Route::redirect('/', '/play');

Route::livewire('/play', 'play-landing')->name('play.landing');
Route::livewire('/play/upload', 'upload-photo')->name('play.upload');
Route::post('/play/upload-image', [\App\Http\Controllers\UploadController::class, 'store'])->name('play.upload-image');
Route::livewire('/play/processing/{uuid}', 'processing-avatar')->name('play.processing');
Route::livewire('/play/result/{uuid}', 'result-avatar')->name('play.result');

/*
|--------------------------------------------------------------------------
| Back of house (HTTP Basic guarded)
|--------------------------------------------------------------------------
*/
Route::middleware('access.guard')->group(function () {
    Route::livewire('/staff/coupon', 'staff-coupon-redeem')->name('staff.coupon');

    Route::livewire('/admin/dashboard', 'admin-dashboard')->name('admin.dashboard');
    Route::livewire('/admin/sessions', 'admin-session-list')->name('admin.sessions');

    Route::get('/admin/sessions/export', \App\Http\Controllers\AdminExportController::class)
        ->name('admin.sessions.export');
});
