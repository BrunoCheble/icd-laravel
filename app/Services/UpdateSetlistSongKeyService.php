<?php

namespace App\Services;

use App\Models\Setlist;
use App\Models\Song;

class UpdateSetlistSongKeyService
{
    /**
     * Changes the key used in this setlist only; songs.musical_key is never touched.
     */
    public function execute(Setlist $setlist, Song $song, ?string $musicalKey): void
    {
        $setlist->songs()->updateExistingPivot($song->id, ['musical_key' => $musicalKey]);
    }
}
