<?php

namespace App\Modules\Visitors\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * The visitors module (who came to a service: list, form, WhatsApp, activate / deactivate): its routes and its views
 * ("visitors::visitors.index"). The public sign-up form stays on the site (SiteController). Its menu entry is in the
 * "People" section.
 */
class VisitorsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'visitors');

        Route::middleware('web')->group(__DIR__ . '/../routes/web.php');
    }
}
