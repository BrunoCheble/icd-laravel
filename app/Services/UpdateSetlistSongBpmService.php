<?php

namespace App\Services;

use App\Models\Setlist;
use App\Models\Song;

class UpdateSetlistSongBpmService
{
    /**
     * Changes the tempo used in this setlist only (the same for the whole band); null goes back to the song's own BPM.
     * songs.bpm is never touched.
     */
    public function execute(Setlist $setlist, Song $song, ?float $bpm): void
    {
        $setlist->songs()->updateExistingPivot($song->id, ['bpm' => $bpm === null ? null : round($bpm, 1)]);
    }
}
