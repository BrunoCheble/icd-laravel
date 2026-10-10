<?php

namespace App\Services;

use App\Models\Song;

class GetStudySongService
{
    /**
     * A song for the study app: its chord map (structure, as stored), chord sheet sections, key and audio.
     * `updated_at` goes back with the edited map, so a song changed meanwhile is not overwritten.
     */
    public function execute(Song $song): array
    {
        return [
            'id'          => $song->id,
            'title'       => $song->title,
            'artist'      => $song->artist,
            'key'         => $song->musical_key,
            'bpm'         => $song->bpm,
            'audio_url'   => $song->audioUrl(),
            'youtube_url' => $song->youtube_url,
            'structure'   => json_decode($song->getRawOriginal('structure') ?? 'null', true) ?? [],
            'chord_sheet' => $song->chord_sheet['sections'] ?? null,
            'updated_at'  => $song->updated_at?->toIso8601String(),
        ];
    }
}
