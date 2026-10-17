<?php

namespace App\Services;

use App\Models\SongLesson;
use Illuminate\Validation\ValidationException;

class ContributeLessonLoopService
{
    /**
     * A stretch of the lesson's video or audio saved by a musician on the public page (an intro, a solo…): it is kept
     * in the lesson for everyone, marked as sent by a musician (only the team changes or removes it). The same stretch
     * already there is not added again.
     */
    public function execute(SongLesson $lesson, float $start, float $end, ?string $label): SongLesson
    {
        $loops = $lesson->loops ?? [];
        $same = collect($loops)->first(fn ($loop) => abs($loop['start'] - $start) < 0.05 && abs($loop['end'] - $end) < 0.05);
        if ($same) {
            return $lesson;
        }
        if (count($loops) >= SaveSongLessonLoopsService::MAX) {
            throw ValidationException::withMessages(['start' => __('This lesson already has :max stretches.', ['max' => SaveSongLessonLoopsService::MAX])]);
        }

        $lesson->loops = SaveSongLessonLoopsService::clean([...$loops, ['start' => $start, 'end' => $end, 'label' => $label, 'contributed' => true]]);
        $lesson->save();

        return $lesson;
    }
}
