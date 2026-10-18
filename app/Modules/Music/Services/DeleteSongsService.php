<?php

namespace App\Modules\Music\Services;

use App\Modules\Music\Models\Song;
use Illuminate\Support\Facades\DB;

class DeleteSongsService
{
    public function __construct(private SaveSongAudioService $audio)
    {
    }

    /**
     * Deletes the songs with these ids, all or none: their versions, suggestions, revisions and places in setlists go
     * with them (cascade). Their audio files are removed once the songs are gone. Returns how many were deleted.
     */
    public function execute(array $ids): int
    {
        $songs = Song::query()->whereIn('id', array_unique(array_map('intval', $ids)))->get();

        DB::transaction(function () use ($songs) {
            $songs->each(fn (Song $song) => $song->delete());
        });
        $songs->each(fn (Song $song) => $this->audio->deleteFile($song));

        return $songs->count();
    }
}
