<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Support\Menu;
use App\Support\Permissions;
use Illuminate\Support\Facades\Gate;
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
        // The session lasts at least as long as the app's lock on a computer (12 hours, see SessionLock): before it,
        // an expired session would come back through "keep me signed in" already locked.
        config(['session.lifetime' => max((int) config('session.lifetime'), \App\Services\Passkeys\SessionLock::DESKTOP_MINUTES)]);

        $this->app->make(Menu::class)
            ->section('home', null, 0, [
                ['label' => 'Dashboard', 'icon' => 'fa-solid fa-house', 'route' => 'dashboard', 'active' => 'dashboard'],
            ])
            ->section('people', 'People', 10, [
                ['label' => 'Members', 'icon' => 'fa-solid fa-users', 'route' => 'members.index'],
                ['label' => 'Visitors', 'icon' => 'fa-solid fa-hand', 'route' => 'visitors.index'],
                ['label' => 'Anniversaries', 'icon' => 'fa-solid fa-cake-candles', 'route' => 'report.anniversaries.index', 'active' => 'report.anniversaries.*'],
                ['label' => 'Ministries', 'icon' => 'fa-solid fa-people-group', 'route' => 'ministries.index', 'active' => ['ministries.*', 'ministries-members.*']],
            ])
            ->section('communication', 'Communication', 20, [
                // The announcements waiting for approval and the members' comments not read yet.
                ['label' => 'Announcements', 'icon' => 'fa-solid fa-bullhorn', 'route' => 'announcements.index',
                    'badge' => fn () => \App\Modules\Announcements\Models\Announcement::where('is_approved', false)->count()
                        + \App\Modules\Announcements\Models\AnnouncementComment::whereNull('read_at')->count()],
                ['label' => 'Prayer Requests', 'icon' => 'fa-solid fa-hands-praying', 'route' => 'prayers.index'],
                ['label' => 'Gallery', 'icon' => 'fa-solid fa-images', 'route' => 'gallery.index'],
            ]);

        // What is new goes to the app's notifications right away (see NotifyNews).
        \App\Modules\Prayers\Models\Prayer::created(fn () => \App\Services\Notifications\NotifyNews::soon());
        \App\Modules\Announcements\Models\Announcement::created(fn ($announcement) => $announcement->is_approved || \App\Services\Notifications\NotifyNews::soon());
        \App\Modules\Announcements\Models\AnnouncementComment::created(fn () => \App\Services\Notifications\NotifyNews::soon());
        \App\Modules\Music\Models\SongSuggestion::created(fn () => \App\Services\Notifications\NotifyNews::soon());
        \App\Modules\Members\Models\Member::created(fn ($member) => $member->membership_status !== \App\Enums\MembershipStatus::PENDING || \App\Services\Notifications\NotifyNews::soon());

        // "members.update", "songs.delete"…: what the user may do (see Permissions); "admin": the administrators.
        Gate::before(fn ($user) => $user->is_admin ? true : null);
        Gate::define('admin', fn ($user) => (bool) $user->is_admin);
        foreach (Permissions::MODULES as $module => [, , $actions]) {
            foreach ($actions as $action) {
                Gate::define("{$module}.{$action}", fn ($user) => Permissions::allows($user, $module, $action));
            }
        }

        View::composer('layouts.navigation', fn ($view) => $view->with('menu', $this->app->make(Menu::class)->resolve(request())));
    }
}
