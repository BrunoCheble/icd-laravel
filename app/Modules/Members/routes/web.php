<?php

// Routes of the members module (admin pages). Loaded by MembersServiceProvider with the "web" middleware.

use App\Modules\Members\Http\Controllers\AnniversaryController;
use App\Modules\Members\Http\Controllers\MemberController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::resource('members', MemberController::class);
    Route::patch('/members/{id}/photo', [MemberController::class, 'uploadPhoto'])->name('members.uploadPhoto');
    Route::get('/members/{id}/activate', [MemberController::class, 'activate'])->name('members.activate');
    Route::get('/report/anniversaries', [AnniversaryController::class, 'index'])->name('report.anniversaries.index');
    Route::get('/members/{id}/card', [MemberController::class, 'generateMemberCard'])->name('members.generateMemberCard');
});
