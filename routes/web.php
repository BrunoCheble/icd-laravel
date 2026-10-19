<?php

use App\Http\Controllers\MinistryController;
use App\Http\Controllers\MinistryMemberController;
use App\Http\Controllers\LockController;
use App\Http\Controllers\PasskeyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Site\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/contact', [SiteController::class, 'contact'])->name('site.contact');
Route::get('/new-visitor', [SiteController::class, 'registerVisitor'])->name('visitor.register');
Route::post('/new-prayer-request', [SiteController::class, 'registerPrayerRequest'])->name('prayer.register');
Route::post('/new-member', [SiteController::class, 'register'])->name('member.register');
Route::post('/new-announcement', [SiteController::class, 'registerAnnouncement'])->name('announcement.register');

Route::get('/check-document-number/{document_number}', [SiteController::class, 'checkDocumentNumber'])->name('member.checkDocumentNumber');

Route::get('/', [SiteController::class, 'member'])->name('site.member');
Route::get('/visitor', [SiteController::class, 'visitor'])->name('site.visitor');
Route::get('/prayer-request', [SiteController::class, 'prayerRequest'])->name('site.prayer');
Route::get('/announcement', [SiteController::class, 'announcement'])->name('site.announcement');
Route::get('/today-visitors', [SiteController::class, 'todayVisitor'])->name('site.todayVisitor');
Route::get('/today-prayers', [SiteController::class, 'todayPrayer'])->name('site.todayPrayer');

Route::middleware('auth')->group(function () {
    // Fingerprint / Face ID: this device registered on the profile, and the app's lock.
    Route::post('/passkeys/options', [PasskeyController::class, 'options'])->middleware('throttle:10,1')->name('passkeys.options');
    Route::post('/passkeys', [PasskeyController::class, 'store'])->middleware('throttle:10,1')->name('passkeys.store');
    Route::delete('/passkeys/{passkey}', [PasskeyController::class, 'destroy'])->name('passkeys.destroy');
    Route::get('/lock', [LockController::class, 'show'])->name('lock');
    Route::post('/lock/ping', [LockController::class, 'ping'])->middleware('throttle:30,1')->name('lock.ping');
    Route::post('/lock/options', [LockController::class, 'options'])->middleware('throttle:20,1')->name('lock.options');
    Route::post('/lock/unlock', [LockController::class, 'unlock'])->middleware('throttle:10,1')->name('lock.unlock');
    Route::post('/lock/password', [LockController::class, 'password'])->middleware('throttle:5,1')->name('lock.password');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/report/families', [ReportController::class, 'families'])->name('report.families.index');

    Route::resource('ministries', MinistryController::class);

    Route::get('/ministries-members/manage/{ministryId}', [MinistryMemberController::class, 'manage'])->name('ministries-members.manage');
    Route::post('/ministries-members/{ministryId}', [MinistryMemberController::class, 'store'])->name('ministries-members.store');
    Route::delete('/ministries-members/{id}', [MinistryMemberController::class, 'destroy'])->name('ministries-members.destroy');

});

// Signing in with fingerprint / Face ID (a passkey registered on the profile).
Route::middleware(['guest', 'throttle:10,1'])->group(function () {
    Route::post('/passkeys/sign-in/options', [PasskeyController::class, 'signInOptions'])->name('passkeys.sign-in.options');
    Route::post('/passkeys/sign-in', [PasskeyController::class, 'signIn'])->name('passkeys.sign-in');
});

require __DIR__.'/auth.php';
