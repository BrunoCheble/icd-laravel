<?php

namespace App\Modules\Users\Providers;

use App\Support\Menu;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * The users of the admin (administrators only): who signs in, with what access to each module (see
 * App\Support\Permissions), and the temporary password sent by WhatsApp, changed on the first sign-in.
 * Views: "users::".
 */
class UsersServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'users');

        Route::middleware('web')->group(__DIR__ . '/../routes/web.php');

        // Shown only to the administrators (the "users.*" pages are theirs, see Menu).
        $this->app->make(Menu::class)->section('admin', 'Administration', 90, [
            ['label' => 'Users', 'icon' => 'fa-solid fa-user-shield', 'route' => 'users.index'],
        ]);
    }
}
