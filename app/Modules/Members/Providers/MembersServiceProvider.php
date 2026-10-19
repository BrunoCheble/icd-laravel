<?php

namespace App\Modules\Members\Providers;

use App\Modules\Members\Models\Member;
use App\Modules\Members\Observers\MemberObserver;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * The members module (the church's members: list, profile, form, photo, activate / deactivate): its routes, its views
 * ("members::members.index") and the observer that keeps spouses linked. Its menu entry is in the "People" section.
 */
class MembersServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'members');

        Route::middleware('web')->group(__DIR__ . '/../routes/web.php');

        Member::observe(MemberObserver::class);
    }
}
