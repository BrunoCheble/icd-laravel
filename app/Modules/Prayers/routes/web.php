<?php

// Routes of the prayer requests module (admin pages). Loaded by PrayersServiceProvider with the "web" middleware.

use App\Modules\Prayers\Http\Controllers\PrayerController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::resource('prayers', PrayerController::class)->only(['index', 'destroy']);
});
