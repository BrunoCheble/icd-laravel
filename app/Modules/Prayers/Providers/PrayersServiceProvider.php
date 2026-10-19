<?php

namespace App\Modules\Prayers\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * The prayer requests module (sent from the site's form): the list to pray for, by period, and deleting.
 * Views: "prayers::". The public form and the "today" page stay on the site (SiteController).
 */
class PrayersServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'prayers');

        Route::middleware('web')->group(__DIR__ . '/../routes/web.php');
    }
}
