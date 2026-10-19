<?php

namespace App\Modules\Dashboard\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * The home page ("Início"): the WhatsApp messages of the day (birthdays, wedding anniversaries, visitors to
 * welcome, pending members to remind), each one ready to send by hand and marked as sent. Views: "dashboard::".
 */
class DashboardServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'dashboard');

        Route::middleware('web')->group(__DIR__ . '/../routes/web.php');
    }
}
