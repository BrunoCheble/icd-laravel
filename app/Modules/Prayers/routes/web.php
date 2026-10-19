<?php

// Routes of the prayer requests module (admin pages). Loaded by PrayersServiceProvider with the "web" middleware.

use App\Modules\Prayers\Http\Controllers\PrayerController;
use App\Modules\Prayers\Http\Controllers\PublicPrayerController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::resource('prayers', PrayerController::class)->only(['index', 'destroy']);
});

// The public page to leave a prayer request (the same addresses as before).
Route::get('/prayer-request', [PublicPrayerController::class, 'create'])->name('site.prayer');
Route::post('/new-prayer-request', [PublicPrayerController::class, 'store'])->middleware('throttle:5,1')->name('prayer.register');
