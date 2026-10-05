<?php

namespace App\Services;

use App\Models\Song;

class SaveSongService
{
    public function execute(array $data, ?Song $song = null): Song
    {
        $values = [
            'title'        => $data['title'],
            'artist'       => $data['artist'],
            'musical_key'  => $data['musical_key'] ?? null,
            'youtube_url'  => $data['youtube_url'] ?? null,
            'source_url'   => $data['source_url'] ?? null,
            'video_lesson' => $this->videoLessons($data['video_lesson'] ?? []),
            // Decoded as-is: the structure comes from the Música project and must not be changed.
            'structure'    => json_decode($data['structure']),
            'chord_sheet'  => isset($data['chord_sheet']) ? json_decode($data['chord_sheet'], true) : null,
        ];

        return $song ? tap($song)->update($values) : Song::create($values);
    }

    private function videoLessons(array $lessons): ?array
    {
        $lessons = collect($lessons)
            ->map(fn (array $lesson) => [
                'instrument' => $lesson['instrument'],
                'title'      => $lesson['title'],
                'link'       => $lesson['link'],
            ])
            ->values()
            ->all();

        return $lessons ?: null;
    }
}
