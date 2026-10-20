<?php

use App\Http\Controllers\Site\ApiController;
use App\Modules\Announcements\Http\Controllers\PublicCommentController;
use App\Modules\Announcements\Http\Controllers\PublicRatingController;
use Illuminate\Support\Facades\Route;

Route::prefix('public')->group(function () {
    Route::get('/announcements', [ApiController::class, 'index'])->middleware('throttle:60,1,public-announcements');
    // A member's private comment about an announcement, for the church's team (from the site, by the NIF).
    Route::post('/announcements/{announcement}/comments', [PublicCommentController::class, 'store'])
        ->whereNumber('announcement')->middleware('throttle:5,1,announcement-comments')->name('api.announcements.comments');
    // A member's stars (1 to 5) for an announcement: one per member, which they can change.
    Route::post('/announcements/{announcement}/ratings', [PublicRatingController::class, 'store'])
        ->whereNumber('announcement')->middleware('throttle:10,1,announcement-ratings')->name('api.announcements.ratings');
});
