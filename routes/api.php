<?php

use App\Http\Controllers\Site\ApiController;
use App\Modules\Announcements\Http\Controllers\PublicAnnouncementApiController;
use App\Modules\Announcements\Http\Controllers\PublicCommentController;
use App\Modules\Announcements\Http\Controllers\PublicRatingController;
use App\Modules\Members\Http\Controllers\PublicMemberController;
use App\Modules\Prayers\Http\Controllers\PublicPrayerApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('public')->group(function () {
    Route::get('/announcements', [ApiController::class, 'index'])->middleware('throttle:60,1,public-announcements');
    // A member's private comment about an announcement, for the church's team (from the site, by the NIF).
    Route::post('/announcements/{announcement}/comments', [PublicCommentController::class, 'store'])
        ->whereNumber('announcement')->middleware('throttle:5,1,announcement-comments')->name('api.announcements.comments');
    // A member's stars (1 to 5) for an announcement: one per member, which they can change.
    Route::post('/announcements/{announcement}/ratings', [PublicRatingController::class, 'store'])
        ->whereNumber('announcement')->middleware('throttle:10,1,announcement-ratings')->name('api.announcements.ratings');

    // The church site's own forms: to announce (it waits for approval) and to become a member (pending).
    Route::get('/announcement-categories', [PublicAnnouncementApiController::class, 'categories'])->middleware('throttle:60,1,announcement-categories')->name('api.announcements.categories');
    Route::post('/announcements', [PublicAnnouncementApiController::class, 'store'])->middleware('throttle:5,1,announce-site')->name('api.announcements.store');
    Route::get('/members/check/{document}', [PublicMemberController::class, 'check'])->middleware('throttle:10,1,nif-check-site')->name('api.members.check');
    Route::post('/members', [PublicMemberController::class, 'store'])->middleware('throttle:5,1,member-register')->name('api.members.store');
    // A prayer request from the church's site (anyone).
    Route::post('/prayers', [PublicPrayerApiController::class, 'store'])->middleware('throttle:5,1,prayer-site')->name('api.prayers.store');
});
