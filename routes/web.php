<?php

use App\Http\Controllers\FinancialCategoryController;
use App\Http\Controllers\FinancialController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MinistryMemberController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Site\SiteController;
use App\Models\FinancialCategory;
use Illuminate\Support\Facades\Route;

Route::get('/contact', [SiteController::class, 'contact'])->name('site.contact');
Route::post('/new-member', [SiteController::class, 'register'])->name('member.register');

Route::get('/', [SiteController::class, 'member'])->name('site.member');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('/financial-categories', FinancialCategoryController::class);
    Route::resource('/financials', FinancialController::class);

    Route::resource('members', MemberController::class);
    Route::patch('/members/{id}/photo', [MemberController::class, 'uploadPhoto'])->name('members.uploadPhoto');
    Route::get('/members/{id}/card', [MemberController::class, 'generateMemberCard'])->name('members.generateMemberCard');

    Route::get('/report/families', [ReportController::class, 'families'])->name('report.families.index');

    Route::prefix('ministries')->group(function () {
        Route::get('/', [MinistryMemberController::class, 'index'])->name('ministries.index');
        Route::get('/manage/{ministryId}', [MinistryMemberController::class, 'manage'])->name('ministries.manage');
        Route::post('/save/{ministryId}', [MinistryMemberController::class, 'save'])->name('ministries.save');
        Route::delete('/remove/{ministryId}/{memberId}', [MinistryMemberController::class, 'removeMember'])->name('ministries.removeMember');
    });
});


require __DIR__.'/auth.php';
