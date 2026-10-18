<?php

namespace App\Modules\Music\Services;

use App\Modules\Music\Models\SongLesson;

class DeleteSongLessonService
{
    public function __construct(private SaveSongLessonService $save)
    {
    }

    /**
     * Deletes the lesson and its file.
     */
    public function execute(SongLesson $lesson): void
    {
        $this->save->deleteFile($lesson);
        $lesson->delete();
    }
}
