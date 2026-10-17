<?php

namespace App\Services;

use App\Models\Song;
use App\Models\SongLesson;

class ListSongLessonsService
{
    /**
     * The song's lessons grouped by instrument: [{ instrument, lessons: [payload, ...] }], instruments in alphabetical
     * order and each one's lessons in their order.
     */
    public function execute(Song $song): array
    {
        return self::group($song->lessons()->get()->all());
    }

    /**
     * @param  SongLesson[]  $lessons
     */
    public static function group(array $lessons): array
    {
        return collect($lessons)
            ->sortBy([['instrument', 'asc'], ['position', 'asc'], ['id', 'asc']])
            ->groupBy('instrument')
            ->map(fn ($items, $instrument) => ['instrument' => (string) $instrument, 'lessons' => $items->map->payload()->values()->all()])
            ->values()
            ->all();
    }
}
