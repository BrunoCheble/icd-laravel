<?php

// Routes of the visitors module (admin pages). Loaded by VisitorsServiceProvider with the "web" middleware.

use App\Modules\Visitors\Http\Controllers\PublicVisitorController;
use App\Modules\Visitors\Http\Controllers\VisitorController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::resource('visitors', VisitorController::class)->except('show');
    Route::get('/visitors/{id}/activate', [VisitorController::class, 'activate'])->name('visitors.activate');
});

// The reception's page to register visitors (the same addresses as before; registering is a POST now).
Route::get('/visitor', [PublicVisitorController::class, 'create'])->name('site.visitor');
Route::post('/new-visitor', [PublicVisitorController::class, 'store'])->middleware('throttle:30,1')->name('visitor.register');
Route::get('/new-visitor', fn () => redirect()->route('site.visitor'));
Route::post('/visitor/new-family', [PublicVisitorController::class, 'newFamily'])->name('site.visitor.new-family');
