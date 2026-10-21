<?php

namespace App\Modules\Discipleship\Providers;

use App\Support\Menu;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * The discipleship module: members followed by a mentor ("responsável"), their meetings (scheduled one by one,
 * marked as held or not), comments, and the mentor's reminders. A mentor who is not an administrator sees only
 * their own (see DiscipleshipAccess). Views: "discipleship::".
 */
class DiscipleshipServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'discipleship');

        Route::middleware('web')->group(__DIR__ . '/../routes/web.php');

        $this->app->make(Menu::class)->section('discipleship', 'Discipleship', 15, [
            ['label' => 'Discipleships', 'icon' => 'fa-solid fa-seedling', 'route' => 'discipleships.index'],
        ]);
    }
}
