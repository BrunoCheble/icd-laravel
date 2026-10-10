<?php

namespace App\Services;

use App\Models\Setlist;
use App\Models\Song;
use App\Services\AlignSectionStartsService;

class GetPublicSetlistService
{
    /**
     * Setlist data for the public page, songs ordered by position.
     * Song data always comes from songs; key and position come from setlist_songs.
     */
    public function execute(Setlist $setlist): array
    {
        $setlist->load('songs.versions');

        return [
            'id'            => $setlist->id,
            'title'         => $setlist->title,
            'event_date'    => $setlist->event_date?->format('d/m/Y'),
            'songs'         => $setlist->songs->map(fn (Song $song) => self::songPayload($song))->all(),
        ];
    }

    /**
     * A song as used by the public page; expects the setlist_songs pivot to be loaded.
     */
    public static function songPayload(Song $song): array
    {
        return [
            'id'           => $song->id,
            'title'        => $song->title,
            'artist'       => $song->artist,
            'original_key' => $song->musical_key,
            // Tempo for the live mode (bars counted by it).
            'bpm'          => $song->bpm,
            'setlist_key'  => $song->pivot?->musical_key,
            'minister_name' => $song->pivot?->minister_name,
            'position'     => $song->pivot?->position,
            'structure'    => $song->structure,
            'youtube_url'  => $song->youtube_url,
            // Audio file played instead of the YouTube video (also offline).
            'audio_url'    => $song->audioUrl(),
            // The map for each instrument that has its own (see SongVersion), shown to whoever picks it.
            'versions'     => $song->versions->map(fn ($version) => [
                'instrument'  => $version->instrument,
                'musical_key' => $version->musical_key,
                'structure'   => $version->structure,
            ])->values()->all(),
            // Chord sheet split like the chord map's blocks, which give it their section times.
            'chord_sheet'  => isset($song->chord_sheet['sections'])
                ? app(AlignSectionStartsService::class)->execute(
                    app(SyncChordSheetWithStructureService::class)->execute($song->chord_sheet['sections'], $song->structure),
                    $song->structure,
                )
                : null,
        ];
    }
}
