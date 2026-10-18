<?php

// API of the music module, loaded by MusicServiceProvider with the "api" middleware and the /api prefix.

use App\Modules\Music\Http\Controllers\Api\StudySongController;
use App\Modules\Music\Http\Middleware\EnsureStudyToken;
use Illuminate\Support\Facades\Route;

// Study app (icd-chords): songs and their chord maps, with the STUDY_API_TOKEN token.
Route::prefix('study')->middleware(EnsureStudyToken::class)->name('study.')->group(function () {
    Route::get('/songs', [StudySongController::class, 'index'])->name('songs.index');
    Route::get('/songs/{song}', [StudySongController::class, 'show'])->name('songs.show');
    Route::put('/songs/{song}/map', [StudySongController::class, 'updateMap'])->name('songs.map');
    Route::post('/songs/{song}/audio', [StudySongController::class, 'updateAudio'])->name('songs.audio');
});
