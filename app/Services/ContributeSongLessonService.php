<?php

namespace App\Services;

use App\Models\Song;
use App\Models\SongLesson;

class ContributeSongLessonService
{
    /**
     * A YouTube video for an instrument sent by a musician: a lesson of the song, marked as sent by a musician, at the
     * end of that instrument's lessons. The same video already there for the instrument is not added again.
     */
    public function execute(Song $song, string $instrument, string $videoUrl): SongLesson
    {
        $instrument = trim($instrument);
        $videoId = self::youtubeId($videoUrl);
        $existing = SongLesson::where('song_id', $song->id)->where('instrument', $instrument)->where('type', 'youtube')->get()
            ->first(fn (SongLesson $lesson) => $videoId !== null && self::youtubeId($lesson->video_url) === $videoId);
        if ($existing) {
            return $existing;
        }

        $lesson = new SongLesson([
            'instrument'  => $instrument,
            'title'       => __('Sent by a musician'),
            'type'        => 'youtube',
            'video_url'   => trim($videoUrl),
            'contributed' => true,
            'position'    => (int) SongLesson::where('song_id', $song->id)->where('instrument', $instrument)->max('position') + 1,
        ]);
        $lesson->song_id = $song->id;
        $lesson->save();

        return $lesson;
    }

    /**
     * The id of a YouTube video (11 characters) from its link, or null.
     */
    public static function youtubeId(?string $url): ?string
    {
        return preg_match('~(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([\w-]{11})~', (string) $url, $match) ? $match[1] : null;
    }
}
