<?php

namespace App\Modules\Music\Console\Commands;

use App\Modules\Music\Models\Song;
use App\Modules\Music\Services\CreateSongBarsService;
use Illuminate\Console\Command;

class CreateSongBars extends Command
{
    protected $signature = 'songs:create-bars {--meter=4/4 : Time signature of the new bars (4/4, 3/4 or 6/8)} {--dry-run : Only list the songs that would change}';

    protected $description = 'Gives every song whose map has blocks without bars one bar per chord';

    public function handle(CreateSongBarsService $bars): int
    {
        $meter = $this->option('meter');
        if (! isset(CreateSongBarsService::METERS[$meter])) {
            $this->error('Unknown time signature: ' . $meter);

            return self::FAILURE;
        }

        $changed = 0;
        Song::query()->whereNotNull('structure')->orderBy('id')->each(function (Song $song) use ($bars, $meter, &$changed) {
            $missing = $bars->missingBlocks($song);
            if ($missing === 0) {
                return;
            }
            $changed++;
            $this->line(sprintf('#%d %s: %d block(s) without bars', $song->id, $song->title, $missing));
            if (! $this->option('dry-run')) {
                // A song that already has bars in some blocks keeps its time signature.
                $bars->execute($song, str_contains(json_encode($song->structure), 'beats_per_bar') ? null : CreateSongBarsService::METERS[$meter]);
            }
        });

        $this->info(($this->option('dry-run') ? 'Would change ' : 'Changed ') . $changed . ' song(s).');

        return self::SUCCESS;
    }
}
