<?php

namespace App\Modules\Music\Services;

use App\Modules\Music\Models\Setlist;
use App\Modules\Music\Models\Song;

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
