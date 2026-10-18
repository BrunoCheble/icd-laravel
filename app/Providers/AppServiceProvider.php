<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Models\Member;
use App\Observers\MemberObserver;
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
        // The side menu: one section per module (the music module adds its own).
        $this->app->singleton(Menu::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Member::observe(MemberObserver::class);

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
                ['label' => 'Announcements', 'icon' => 'fa-solid fa-bullhorn', 'route' => 'announcements.index'],
                ['label' => 'Prayer Requests', 'icon' => 'fa-solid fa-hands-praying', 'route' => 'prayers.index'],
            ]);

        View::composer('layouts.navigation', fn ($view) => $view->with('menu', $this->app->make(Menu::class)->resolve(request())));
    }
}
