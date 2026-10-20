<?php

// Routes of the announcements module (admin pages). Loaded by AnnouncementsServiceProvider with the "web" middleware.

use App\Modules\Announcements\Http\Controllers\AnnouncementController;
use App\Modules\Announcements\Http\Controllers\PublicAnnouncementController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::resource('announcements', AnnouncementController::class)->except('show');
    Route::patch('/announcements/{announcement}/approval', [AnnouncementController::class, 'approval'])->name('announcements.approval');
});

// The public page to send an announcement (the same addresses as before); it waits for approval.
Route::get('/announcement', [PublicAnnouncementController::class, 'create'])->name('site.announcement');
Route::post('/new-announcement', [PublicAnnouncementController::class, 'store'])->middleware('throttle:5,1,announce')->name('announcement.register');
