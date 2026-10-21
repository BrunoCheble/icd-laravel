<?php

namespace App\Modules\Ministries\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * The ministries module: each ministry with its roles and members (role, order, active, biography); the pastoral
 * one is the site's "Pastores" (public API). Views: "ministries::".
 */
class MinistriesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'ministries');

        Route::middleware('web')->group(__DIR__ . '/../routes/web.php');
    }
}
