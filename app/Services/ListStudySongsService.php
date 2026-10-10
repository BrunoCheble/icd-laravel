<?php

namespace App\Services;

use App\Models\Song;

class ListStudySongsService
{
    /**
     * Songs for the study app: what is needed to pick one (title, key, whether it has audio and a map).
     */
    public function execute(): array
    {
        return Song::query()
            ->orderBy('title')
            ->get()
            ->map(fn (Song $song) => [
                'id'         => $song->id,
                'title'      => $song->title,
                'artist'     => $song->artist,
                'key'        => $song->musical_key,
                'audio_url'  => $song->audioUrl(),
                'youtube_url' => $song->youtube_url,
                'blocks'     => is_array($song->structure) ? count($song->structure) : 0,
                'updated_at' => $song->updated_at?->toIso8601String(),
            ])
            ->all();
    }
}
