<?php

namespace App\Services;

use App\Enums\MusicalKey;
use App\Models\Setlist;
use App\Models\Song;

class GetPracticeSongService
{
    /**
     * A song for the practice page, with the songs to jump to: those of the setlist (in its order and keys) when
     * practicing a setlist, otherwise the song list by title (original keys). The page can still
     * change the key. Fails with 404 when the song is not in the setlist.
     */
    public function execute(Song $song, ?Setlist $setlist = null): array
    {
        $songs = $setlist
            ? $setlist->songs()->get()
            : Song::query()->orderBy('title')->orderBy('id')->get();
        $index = $songs->search(fn (Song $item) => $item->id === $song->id);
        abort_if($index === false, 404);

        return [
            'setlist'  => $setlist ? ['id' => $setlist->id, 'title' => $setlist->title] : null,
            'song'     => GetPublicSetlistService::songPayload($songs[$index]),
            'keys'     => MusicalKey::pairs(),
            'position' => $index + 1,
            'total'    => $songs->count(),
            // To jump straight to another song.
            'songs'    => $songs->map(fn (Song $item) => [
                'id'     => $item->id,
                'title'  => $item->title,
                'artist' => $item->artist,
                'key'    => $item->pivot?->musical_key ?? $item->musical_key,
            ])->values()->all(),
        ];
    }
}
