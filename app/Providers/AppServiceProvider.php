<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Models\Member;
use App\Models\Song;
use App\Observers\MemberObserver;
use App\Observers\SongObserver;
use Illuminate\Support\Facades\App;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Member::observe(MemberObserver::class);
        Song::observe(SongObserver::class);
    }
}
