<?php

namespace App\Services;

use App\Models\Song;
use App\Models\SongRevision;

class RestoreSongRevisionService
{
    /**
     * Puts a revision's chord map, chord sheet, key and tempo back on the song. What the song had becomes a revision
     * too (see RecordSongRevisionService), so a restore can be undone.
     */
    public function execute(Song $song, SongRevision $revision): Song
    {
        $song->structure = $revision->structure ?? [];
        $song->chord_sheet = $revision->chord_sheet;
        $song->musical_key = $revision->musical_key;
        $song->bpm = $revision->bpm;
        $song->save();

        return $song;
    }
}
