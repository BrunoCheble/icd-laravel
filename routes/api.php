<?php

use App\Http\Controllers\Api\StudySongController;
use App\Http\Controllers\Site\ApiController;
use App\Http\Middleware\EnsureStudyToken;
use Illuminate\Support\Facades\Route;

Route::prefix('public')->group(function () {
    Route::get('/announcements', [ApiController::class, 'index']);
});

// Study app (icd-chords): songs and their chord maps, with the STUDY_API_TOKEN token.
Route::prefix('study')->middleware(EnsureStudyToken::class)->name('study.')->group(function () {
    Route::get('/songs', [StudySongController::class, 'index'])->name('songs.index');
    Route::get('/songs/{song}', [StudySongController::class, 'show'])->name('songs.show');
    Route::put('/songs/{song}/map', [StudySongController::class, 'updateMap'])->name('songs.map');
});
