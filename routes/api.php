<?php

use App\Http\Controllers\Site\ApiController;
use App\Modules\Announcements\Http\Controllers\PublicCommentController;
use Illuminate\Support\Facades\Route;

Route::prefix('public')->group(function () {
    Route::get('/announcements', [ApiController::class, 'index'])->middleware('throttle:60,1,public-announcements');
    // A member's comment about an announcement (from the site, by the NIF); it waits for approval.
    Route::post('/announcements/{announcement}/comments', [PublicCommentController::class, 'store'])
        ->whereNumber('announcement')->middleware('throttle:5,1,announcement-comments')->name('api.announcements.comments');
});
