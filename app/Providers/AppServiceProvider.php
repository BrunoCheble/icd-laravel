<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Support\Menu;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The side menu: one section per module (the modules add their own).
        $this->app->singleton(Menu::class);

        // The WebAuthn (passkeys) library kept in lib/webauthn (no Composer on the production server).
        spl_autoload_register(function (string $class) {
            $prefix = 'lbuchs\\WebAuthn\\';
            if (str_starts_with($class, $prefix)) {
                $file = base_path('lib/webauthn/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php');
                if (is_file($file)) {
                    require $file;
                }
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->make(Menu::class)
            ->section('home', null, 0, [
                ['label' => 'Dashboard', 'icon' => 'fa-solid fa-house', 'route' => 'dashboard', 'active' => 'dashboard'],
            ])
            ->section('people', 'People', 10, [
                ['label' => 'Members', 'icon' => 'fa-solid fa-users', 'route' => 'members.index'],
                ['label' => 'Visitors', 'icon' => 'fa-solid fa-hand', 'route' => 'visitors.index'],
                ['label' => 'Anniversaries', 'icon' => 'fa-solid fa-cake-candles', 'route' => 'report.anniversaries.index', 'active' => 'report.anniversaries.*'],
            ])
            ->section('communication', 'Communication', 20, [
                // The announcements waiting for approval and the members' comments not read yet.
                ['label' => 'Announcements', 'icon' => 'fa-solid fa-bullhorn', 'route' => 'announcements.index',
                    'badge' => fn () => \App\Modules\Announcements\Models\Announcement::where('is_approved', false)->count()
                        + \App\Modules\Announcements\Models\AnnouncementComment::whereNull('read_at')->count()],
                ['label' => 'Prayer Requests', 'icon' => 'fa-solid fa-hands-praying', 'route' => 'prayers.index'],
            ]);

        View::composer('layouts.navigation', fn ($view) => $view->with('menu', $this->app->make(Menu::class)->resolve(request())));
    }
}
