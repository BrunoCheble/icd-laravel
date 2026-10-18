<?php

namespace App\Modules\Music\Services;

use App\Modules\Music\Models\Setlist;
use App\Modules\Music\Models\Song;

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
