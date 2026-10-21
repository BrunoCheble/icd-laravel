<?php

// Routes of the ministries module (admin pages). Loaded by MinistriesServiceProvider with the "web" middleware; in
// the "members" module of Permissions ("ministries.*", "ministries-members.*").

use App\Modules\Ministries\Http\Controllers\MembershipController;
use App\Modules\Ministries\Http\Controllers\MinistryController;
use App\Modules\Ministries\Http\Controllers\MinistryRoleController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::resource('ministries', MinistryController::class);
    Route::post('ministries/{ministry}/roles', [MinistryRoleController::class, 'store'])->name('ministries.roles.store');
    Route::put('ministry-roles/{role}', [MinistryRoleController::class, 'update'])->name('ministries.roles.update');
    Route::delete('ministry-roles/{role}', [MinistryRoleController::class, 'destroy'])->name('ministries.roles.destroy');

    Route::post('ministries/{ministry}/members', [MembershipController::class, 'store'])->name('ministries-members.store');
    Route::get('ministries-members/{membership}/edit', [MembershipController::class, 'edit'])->name('ministries-members.edit');
    Route::put('ministries-members/{membership}', [MembershipController::class, 'update'])->name('ministries-members.update');
    Route::delete('ministries-members/{membership}', [MembershipController::class, 'destroy'])->name('ministries-members.destroy');
});
