<?php

// Routes of the gallery module (admin pages). Loaded by GalleryServiceProvider with the "web" middleware; the
// "gallery" module of Permissions ("gallery.*": sending photos is creating, deleting one is deleting).

use App\Modules\Gallery\Http\Controllers\GalleryEventController;
use App\Modules\Gallery\Http\Controllers\GalleryPhotoController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::resource('gallery', GalleryEventController::class)->parameters(['gallery' => 'event'])->whereNumber('event');

    Route::post('gallery/{event}/photos', [GalleryPhotoController::class, 'store'])->whereNumber('event')->name('gallery.photos.store');
    Route::patch('gallery/photos/{photo}', [GalleryPhotoController::class, 'update'])->whereNumber('photo')->name('gallery.photos.update');
    Route::patch('gallery/photos/{photo}/move', [GalleryPhotoController::class, 'move'])->whereNumber('photo')->name('gallery.photos.move');
    Route::delete('gallery/photos/{photo}', [GalleryPhotoController::class, 'destroy'])->whereNumber('photo')->name('gallery.photos.destroy');
});
