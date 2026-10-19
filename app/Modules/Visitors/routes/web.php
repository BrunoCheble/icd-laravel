<?php

// Routes of the visitors module (admin pages). Loaded by VisitorsServiceProvider with the "web" middleware.

use App\Modules\Visitors\Http\Controllers\VisitorController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::resource('visitors', VisitorController::class)->except('show');
    Route::get('/visitors/{id}/activate', [VisitorController::class, 'activate'])->name('visitors.activate');
});
