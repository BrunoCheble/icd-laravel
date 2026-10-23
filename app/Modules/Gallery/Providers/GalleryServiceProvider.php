<?php

namespace App\Modules\Gallery\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * The gallery module: the church's events with their photos, shown on the site's "Galeria" (public API).
 * Views: "gallery::".
 */
class GalleryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'gallery');

        Route::middleware('web')->group(__DIR__ . '/../routes/web.php');
    }
}
