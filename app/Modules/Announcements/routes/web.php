<?php

// Routes of the announcements module (admin pages). Loaded by AnnouncementsServiceProvider with the "web" middleware.

use App\Modules\Announcements\Http\Controllers\AnnouncementCommentController;
use App\Modules\Announcements\Http\Controllers\AnnouncementController;
use App\Modules\Announcements\Http\Controllers\PublicAnnouncementController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::resource('announcements', AnnouncementController::class)->except('show');
    Route::patch('/announcements/{announcement}/approval', [AnnouncementController::class, 'approval'])->name('announcements.approval');
    // The members' private comments about an announcement: read or deleted by the team.
    Route::patch('/announcements/comments/{comment}/read', [AnnouncementCommentController::class, 'read'])->whereNumber('comment')->name('announcements.comments.read');
    Route::delete('/announcements/comments/{comment}', [AnnouncementCommentController::class, 'destroy'])->whereNumber('comment')->name('announcements.comments.destroy');
});

// The public page to send an announcement (the same addresses as before); it waits for approval.
Route::get('/announcement', [PublicAnnouncementController::class, 'create'])->name('site.announcement');
Route::post('/new-announcement', [PublicAnnouncementController::class, 'store'])->middleware('throttle:5,1,announce')->name('announcement.register');

// The files of announcements sent before they went to the public folder (in storage/app/public).
Route::get('/announcements/files/{file}', function (string $file) {
    $path = \App\Modules\Announcements\Models\Announcement::storedFile('storage/announcements/' . $file);
    abort_unless($path, 404);
    return response()->file($path, ['Cache-Control' => 'public, max-age=2592000']);
})->where('file', '[A-Za-z0-9_-]+\.(?:jpe?g|png|gif|pdf)')->name('announcements.file');
