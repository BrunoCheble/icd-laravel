<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\PrayerController;
use App\Http\Controllers\FinancialBalanceController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\FinancialCategoryController;
use App\Http\Controllers\FinancialMovementController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\VisitorController;
use App\Http\Controllers\MinistryController;
use App\Http\Controllers\MinistryMemberController;
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

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('wallets', WalletController::class);
    Route::resource('financial-balances', FinancialBalanceController::class);
    Route::resource('financial-categories', FinancialCategoryController::class);
    Route::resource('financial-movements', FinancialMovementController::class);

    Route::resource('visitors', VisitorController::class);
    Route::get('/visitors/{id}/activate', [VisitorController::class, 'activate'])->name('visitors.activate');

    Route::resource('members', MemberController::class);
    Route::patch('/members/{id}/photo', [MemberController::class, 'uploadPhoto'])->name('members.uploadPhoto');
    Route::get('/members/{id}/activate', [MemberController::class, 'activate'])->name('members.activate');
    Route::get('/members/{id}/card', [MemberController::class, 'generateMemberCard'])->name('members.generateMemberCard');

    Route::get('/report/families', [ReportController::class, 'families'])->name('report.families.index');
    Route::get('/report/anniversaries', [ReportController::class, 'anniversaries'])->name('report.anniversaries.index');

    Route::resource('ministries', MinistryController::class);

    Route::get('/ministries-members/manage/{ministryId}', [MinistryMemberController::class, 'manage'])->name('ministries-members.manage');
    Route::post('/ministries-members/{ministryId}', [MinistryMemberController::class, 'store'])->name('ministries-members.store');
    Route::delete('/ministries-members/{id}', [MinistryMemberController::class, 'destroy'])->name('ministries-members.destroy');

    Route::resource('announcements', AnnouncementController::class);
    Route::resource('prayers', PrayerController::class);
});


require __DIR__.'/auth.php';
