<?php

// Routes of the discipleship module (admin pages). Loaded by DiscipleshipServiceProvider with the "web" middleware.

use App\Modules\Discipleship\Http\Controllers\DiscipleshipController;
use App\Modules\Discipleship\Http\Controllers\MeetingCommentController;
use App\Modules\Discipleship\Http\Controllers\MeetingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('discipleships')->name('discipleships.')->group(function () {
    Route::get('meetings/create', [MeetingController::class, 'create'])->name('meetings.create');
    Route::post('meetings', [MeetingController::class, 'store'])->name('meetings.store');
    Route::get('meetings/{meeting}', [MeetingController::class, 'show'])->name('meetings.show');
    Route::get('meetings/{meeting}/edit', [MeetingController::class, 'edit'])->name('meetings.edit');
    Route::put('meetings/{meeting}', [MeetingController::class, 'update'])->name('meetings.update');
    Route::patch('meetings/{meeting}/outcome', [MeetingController::class, 'outcome'])->name('meetings.outcome');
    Route::delete('meetings/{meeting}', [MeetingController::class, 'destroy'])->name('meetings.destroy');
    Route::post('meetings/{meeting}/comments', [MeetingCommentController::class, 'store'])->name('meetings.comments.store');
    Route::get('{discipleship}/history', [DiscipleshipController::class, 'history'])->name('history');
    Route::post('{discipleship}/transfer', [DiscipleshipController::class, 'transfer'])->name('transfer');
    Route::delete('meeting-comments/{comment}', [MeetingCommentController::class, 'destroy'])->name('meetings.comments.destroy');
});

Route::middleware('auth')->group(function () {
    Route::resource('discipleships', DiscipleshipController::class)->except(['show']);
});
