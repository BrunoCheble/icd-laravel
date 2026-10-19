<?php

// Routes of the announcements module (admin pages). Loaded by AnnouncementsServiceProvider with the "web" middleware.

use App\Modules\Announcements\Http\Controllers\AnnouncementController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::resource('announcements', AnnouncementController::class)->except('show');
    Route::patch('/announcements/{announcement}/approval', [AnnouncementController::class, 'approval'])->name('announcements.approval');
});
