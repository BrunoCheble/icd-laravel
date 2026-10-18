<?php

namespace App\Modules\Music\Services;

use App\Modules\Music\Models\Song;

class MarkSongReviewedService
{
    /**
     * Marks the song as reviewed now (its review was finished), or undoes it.
     */
    public function execute(Song $song, bool $reviewed): Song
    {
        $song->reviewed_at = $reviewed ? now() : null;
        $song->save();

        return $song;
    }
}
