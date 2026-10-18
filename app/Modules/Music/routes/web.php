<?php

// Routes of the music module: the public setlist pages ("repertoire") and the admin pages of songs, setlists, lessons
// and chord suggestions. Loaded by MusicServiceProvider with the "web" middleware.

use App\Modules\Music\Http\Controllers\SetlistController;
use App\Modules\Music\Http\Controllers\SetlistImportController;
use App\Modules\Music\Http\Controllers\SongChordSheetController;
use App\Modules\Music\Http\Controllers\SongController;
use App\Modules\Music\Http\Controllers\SongHistoryController;
use App\Modules\Music\Http\Controllers\SongLayoutController;
use App\Modules\Music\Http\Controllers\SongLessonController;
use App\Modules\Music\Http\Controllers\SongPracticeController;
use App\Modules\Music\Http\Controllers\SongSheetController;
use App\Modules\Music\Http\Controllers\SongSuggestionController;
use App\Modules\Music\Http\Controllers\SongVersionController;
use App\Modules\Music\Http\Controllers\Site\SetlistController as SiteSetlistController;
use Illuminate\Support\Facades\Route;

Route::prefix('repertoire')->name('site.setlist')->controller(SiteSetlistController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/songs/search', 'searchSongs')->name('.songs.search');
    Route::get('/songs/catalog', 'catalog')->name('.songs.catalog');
    Route::get('/{setlist}', 'show')->whereNumber('setlist')->name('.show');
    Route::post('/{setlist}/songs', 'addSong')->whereNumber('setlist')->name('.songs.add');
    Route::delete('/{setlist}/songs/{song}', 'removeSong')->whereNumber(['setlist', 'song'])->name('.songs.remove');
    Route::patch('/{setlist}/songs/{song}/key', 'updateKey')->whereNumber(['setlist', 'song'])->name('.songs.key');
    Route::patch('/{setlist}/songs/{song}/bpm', 'updateBpm')->whereNumber(['setlist', 'song'])->name('.songs.bpm');
    Route::put('/{setlist}/order', 'reorder')->whereNumber('setlist')->name('.order');
    Route::post('/{setlist}/songs/{song}/lessons/{lesson}/loops', 'contributeLessonLoop')->whereNumber(['setlist', 'song', 'lesson'])->middleware('throttle:20,1')->name('.songs.lessons.loops');
    Route::post('/{setlist}/songs/{song}/lessons', 'contributeLesson')->whereNumber(['setlist', 'song'])->middleware('throttle:10,1')->name('.songs.lessons');
    Route::post('/{setlist}/songs/{song}/suggestions', 'suggest')->whereNumber(['setlist', 'song'])->middleware('throttle:20,1')->name('.songs.suggest');
});

Route::middleware('auth')->group(function () {
    Route::get('/songs/bookmarklet', [SongChordSheetController::class, 'bookmarklet'])->name('songs.bookmarklet');
    Route::post('/songs/chord-sheet/structure', [SongChordSheetController::class, 'structure'])->name('songs.chord-sheet.structure');
    Route::post('/songs/chord-sheet/parse', [SongChordSheetController::class, 'parse'])->name('songs.chord-sheet.parse');
    Route::post('/songs/{song}/chord-sheet/import', [SongChordSheetController::class, 'import'])->whereNumber('song')->name('songs.chord-sheet.import');
    Route::delete('/songs', [SongController::class, 'bulkDestroy'])->name('songs.bulk-destroy');
    Route::resource('songs', SongController::class);
    Route::get('/songs/{song}/layout', [SongLayoutController::class, 'edit'])->name('songs.layout.edit');
    Route::get('/songs/{song}/sheet', [SongSheetController::class, 'edit'])->name('songs.sheet.edit');
    Route::put('/songs/{song}/sheet', [SongSheetController::class, 'update'])->name('songs.sheet.update');
    Route::post('/songs/{song}/sheet/bars', [SongSheetController::class, 'bars'])->name('songs.sheet.bars');
    Route::put('/songs/{song}/layout', [SongLayoutController::class, 'update'])->name('songs.layout.update');
    Route::put('/songs/{song}/bars', [SongLayoutController::class, 'updateBars'])->name('songs.bars.update');
    Route::put('/songs/{song}/sheet-section', [SongLayoutController::class, 'updateSheetSection'])->name('songs.sheet-section.update');
    Route::post('/songs/{song}/versions', [SongVersionController::class, 'store'])->name('songs.versions.store');
    Route::put('/songs/{song}/versions/{version}', [SongVersionController::class, 'update'])->name('songs.versions.update');
    Route::delete('/songs/{song}/versions/{version}', [SongVersionController::class, 'destroy'])->name('songs.versions.destroy');
    Route::post('/songs/{song}/bars', [SongLayoutController::class, 'createBars'])->name('songs.bars.create');
    Route::post('/songs/{song}/bars/clear', [SongLayoutController::class, 'clearChords'])->name('songs.bars.clear');
    Route::get('/songs/{song}/practice', [SongPracticeController::class, 'show'])->name('songs.practice');
    Route::get('/songs/{song}/lessons', [SongLessonController::class, 'index'])->name('songs.lessons.index');
    Route::post('/songs/{song}/lessons', [SongLessonController::class, 'store'])->name('songs.lessons.store');
    Route::put('/songs/{song}/lessons/{lesson}', [SongLessonController::class, 'update'])->name('songs.lessons.update');
    Route::delete('/songs/{song}/lessons/{lesson}', [SongLessonController::class, 'destroy'])->name('songs.lessons.destroy');
    Route::put('/songs/{song}/lessons/{lesson}/loops', [SongLessonController::class, 'loops'])->name('songs.lessons.loops');
    Route::get('/songs/{song}/history', [SongHistoryController::class, 'index'])->name('songs.history');
    Route::get('/song-suggestions', [SongSuggestionController::class, 'index'])->name('song-suggestions.index');
    Route::post('/song-suggestions/{suggestion}/approve', [SongSuggestionController::class, 'approve'])->name('song-suggestions.approve');
    Route::post('/song-suggestions/{suggestion}/reject', [SongSuggestionController::class, 'reject'])->name('song-suggestions.reject');
    Route::post('/songs/{song}/history/{revision}/restore', [SongHistoryController::class, 'restore'])->name('songs.history.restore');

    Route::get('/setlists/songs/search', [SetlistController::class, 'searchSongs'])->name('setlists.songs.search');
    Route::get('/setlists/import', [SetlistImportController::class, 'create'])->name('setlists.import');
    Route::post('/setlists/import', [SetlistImportController::class, 'store'])->name('setlists.import.store');
    Route::resource('setlists', SetlistController::class)->except('show');
    Route::get('/setlists/{setlist}/practice/{song?}', [SongPracticeController::class, 'setlist'])->name('setlists.practice');
});
