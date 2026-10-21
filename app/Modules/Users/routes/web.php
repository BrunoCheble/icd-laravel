<?php

// Routes of the users module. Loaded by UsersServiceProvider with the "web" middleware. The "users.*" routes belong
// to no module of Permissions: only administrators reach them (see EnsurePermission).

use App\Modules\Users\Http\Controllers\ChangePasswordController;
use App\Modules\Users\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::resource('users', UserController::class)->except(['show']);
    Route::post('users/{user}/temporary-password', [UserController::class, 'temporaryPassword'])->name('users.temporary-password');
    Route::get('users/{user}/temporary-password', [UserController::class, 'showTemporaryPassword'])->name('users.temporary-password.show');

    // The first sign-in with a temporary password: a password of one's own (see EnsurePasswordChanged).
    Route::get('password/change', [ChangePasswordController::class, 'edit'])->name('password.change');
    Route::put('password/change', [ChangePasswordController::class, 'update'])->name('password.change.update');
});
