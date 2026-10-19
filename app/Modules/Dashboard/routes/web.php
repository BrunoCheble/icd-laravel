<?php

// Routes of the home page. Loaded by DashboardServiceProvider with the "web" middleware.

use App\Modules\Dashboard\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/sent-messages', [DashboardController::class, 'markSent'])->name('dashboard.sent.store');
    Route::delete('/dashboard/sent-messages', [DashboardController::class, 'unmarkSent'])->name('dashboard.sent.destroy');
});
