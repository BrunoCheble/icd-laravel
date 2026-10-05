<?php

namespace App\Services;

use App\Models\Setlist;
use App\Models\Song;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddSongToSetlistService
{
    /**
     * Appends the song at the next position, starting with the song's original key.
     */
    public function execute(Setlist $setlist, Song $song): Song
    {
        return DB::transaction(function () use ($setlist, $song) {
            // Lock the setlist so concurrent additions don't get the same position.
            Setlist::whereKey($setlist->id)->lockForUpdate()->first();

            if ($setlist->songs()->whereKey($song->id)->exists()) {
                throw ValidationException::withMessages([
                    'song_id' => __('This song is already in the setlist.'),
                ]);
            }

            $nextPosition = (int) $setlist->songs()->reorder()->max('setlist_songs.position') + 1;

            $setlist->songs()->attach($song->id, [
                'musical_key' => $song->musical_key,
                'position'    => $nextPosition,
            ]);

            return $setlist->songs()->whereKey($song->id)->firstOrFail();
        });
    }
}
