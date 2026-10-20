<?php

// Routes of the members module (admin pages). Loaded by MembersServiceProvider with the "web" middleware.

use App\Modules\Members\Http\Controllers\AnniversaryController;
use App\Modules\Members\Http\Controllers\CompleteRegistrationController;
use App\Modules\Members\Http\Controllers\MemberController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::resource('members', MemberController::class);
    Route::patch('/members/{id}/photo', [MemberController::class, 'uploadPhoto'])->name('members.uploadPhoto');
    Route::get('/members/{id}/activate', [MemberController::class, 'activate'])->name('members.activate');
    Route::get('/report/anniversaries', [AnniversaryController::class, 'index'])->name('report.anniversaries.index');
    Route::get('/members/{id}/card', [MemberController::class, 'generateMemberCard'])->name('members.generateMemberCard');
});

// The page a pending member opens from the link sent by WhatsApp (signed, see GetRegistrationLinkService).
Route::get('/completar-registo/{member}', [CompleteRegistrationController::class, 'show'])->middleware('throttle:30,1,complete-registration')->name('members.complete');
Route::post('/completar-registo/{member}', [CompleteRegistrationController::class, 'update'])->middleware(['signed', 'throttle:10,1,complete-registration-save'])->name('members.complete.update');
