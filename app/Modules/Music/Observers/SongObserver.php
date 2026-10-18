<?php

namespace App\Modules\Music\Observers;

use App\Modules\Music\Models\Song;
use App\Modules\Music\Services\RecordSongRevisionService;

class SongObserver
{
    public function __construct(private RecordSongRevisionService $revisions)
    {
    }

    /**
     * Before a change to the chord map, chord sheet, key or tempo is saved, the earlier version is kept.
     */
    public function updating(Song $song): void
    {
        $this->revisions->execute($song);
    }
}
