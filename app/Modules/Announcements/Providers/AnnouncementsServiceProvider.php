<?php

namespace App\Modules\Announcements\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * The announcements module (members' adverts: résumés, services, products, donations): the list to review and
 * approve, and the form. Views: "announcements::". Members send theirs from the site (SiteController), and the
 * site reads them through the API (ApiController).
 */
class AnnouncementsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'announcements');

        Route::middleware('web')->group(__DIR__ . '/../routes/web.php');
    }
}
