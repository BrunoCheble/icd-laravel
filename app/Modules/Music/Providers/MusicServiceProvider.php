<?php

namespace App\Modules\Music\Providers;

use App\Modules\Music\Console\Commands\CreateSongBars;
use App\Modules\Music\Models\Song;
use App\Modules\Music\Models\SongSuggestion;
use App\Modules\Music\Observers\SongObserver;
use App\Support\Menu;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * The music module (songs, setlists, lessons and the study app's API): its routes, its views ("music::songs.index"),
 * its section of the side menu, the observer that keeps each song's history and its console commands.
 */
class MusicServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'music');

        Route::middleware('web')->group(__DIR__ . '/../routes/web.php');
        Route::middleware('api')->prefix('api')->group(__DIR__ . '/../routes/api.php');

        Song::observe(SongObserver::class);

        $this->app->make(Menu::class)->section('music', 'Music', 30, [
            ['label' => 'Songs', 'icon' => 'fa-solid fa-music', 'route' => 'songs.index'],
            ['label' => 'Setlists', 'icon' => 'fa-solid fa-list-ol', 'route' => 'setlists.index'],
            ['label' => 'Suggestions', 'icon' => 'fa-solid fa-lightbulb', 'route' => 'song-suggestions.index',
                'badge' => fn () => SongSuggestion::where('status', 'pending')->count()],
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([CreateSongBars::class]);
        }
    }
}
