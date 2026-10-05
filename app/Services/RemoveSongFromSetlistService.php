<?php

namespace App\Services;

use App\Models\Setlist;
use App\Models\Song;
use Illuminate\Support\Facades\DB;

class RemoveSongFromSetlistService
{
    public function __construct(private ReorderSetlistSongsService $reorder)
    {
    }

    /**
     * Removes only the setlist_songs link and closes the gap in positions.
     */
    public function execute(Setlist $setlist, Song $song): void
    {
        DB::transaction(function () use ($setlist, $song) {
            $setlist->songs()->detach($song->id);

            $this->reorder->execute($setlist, $setlist->songs()->pluck('songs.id')->all());
        });
    }
}
