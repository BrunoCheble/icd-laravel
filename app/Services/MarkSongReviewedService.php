<?php

namespace App\Services;

use App\Models\Song;

class MarkSongReviewedService
{
    /**
     * Marks the song as reviewed now, or clears the mark.
     */
    public function execute(Song $song, bool $reviewed): Song
    {
        $song->reviewed_at = $reviewed ? now() : null;
        $song->save();

        return $song;
    }
}
