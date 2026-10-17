<?php

namespace App\Services;

use App\Models\Song;
use App\Models\SongLesson;
use Illuminate\Http\UploadedFile;

class SaveSongLessonService
{
    // Folder under public/ with the files of the lessons.
    public const FOLDER = 'lessons';

    /**
     * Creates a lesson for the song (`$lesson` null, at the end of its instrument's lessons) or updates it. A lesson
     * has one type: a YouTube link, or a file (a new one replaces the old). Changing the type drops what belonged to
     * the old one (its link or file, and the stretches of its video or audio). Each upload gets a new name, so browsers
     * never keep an old file.
     */
    public function execute(Song $song, array $data, ?UploadedFile $file = null, ?SongLesson $lesson = null): SongLesson
    {
        $lesson ??= new SongLesson();
        $lesson->song_id = $song->id;
        $instrument = trim($data['instrument']);
        if (! $lesson->exists || $lesson->instrument !== $instrument) {
            $lesson->position = (int) SongLesson::where('song_id', $song->id)->where('instrument', $instrument)->max('position') + 1;
        }
        $type = $data['type'];
        if ($lesson->exists && $lesson->type !== $type) {
            $lesson->loops = null;
        }
        $lesson->fill([
            'instrument' => $instrument,
            'title'      => trim($data['title']),
            'type'       => $type,
            'notes'      => self::text($data['notes'] ?? null),
            'video_url'  => $type === 'youtube' ? self::text($data['video_url'] ?? null) : null,
        ]);

        if ($file || ! SongLesson::isFileType($type)) {
            $this->deleteFile($lesson);
            $lesson->file_path = null;
            $lesson->file_name = null;
        }
        if ($file && SongLesson::isFileType($type)) {
            $name = $song->id . '-' . bin2hex(random_bytes(6)) . '.' . strtolower($file->getClientOriginalExtension() ?: 'bin');
            $lesson->file_name = mb_substr($file->getClientOriginalName(), 0, 255);
            $file->move(public_path(self::FOLDER), $name);
            $lesson->file_path = $name;
        }

        $lesson->save();

        return $lesson;
    }

    public function deleteFile(SongLesson $lesson): void
    {
        $path = $lesson->file_path ? public_path(self::FOLDER . '/' . $lesson->file_path) : null;

        if ($path && is_file($path)) {
            unlink($path);
        }
    }

    private static function text(mixed $value): ?string
    {
        $value = is_string($value) ? trim(str_replace("\r\n", "\n", $value)) : '';

        return $value === '' ? null : $value;
    }
}
