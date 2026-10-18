<?php

namespace App\Modules\Music\Services;

use App\Modules\Music\Models\SongLesson;

class SaveSongLessonLoopsService
{
    // Most stretches kept per lesson.
    public const MAX = 30;

    /**
     * Saves the stretches of the lesson's video or audio to be repeated.
     */
    public function execute(SongLesson $lesson, array $loops): SongLesson
    {
        $lesson->loops = self::clean($loops);
        $lesson->save();

        return $lesson;
    }

    /**
     * Valid stretches only (an end after the start), in seconds with hundredths, a label (or "start–end"), ordered by
     * their start; the ones saved by a musician on the public page keep that mark (`contributed`).
     */
    public static function clean(array $loops): array
    {
        return collect($loops)
            ->filter(fn ($loop) => is_array($loop) && is_numeric($loop['start'] ?? null) && is_numeric($loop['end'] ?? null))
            ->map(fn (array $loop) => [
                'start' => round(max(0, (float) $loop['start']), 2),
                'end'   => round(max(0, (float) $loop['end']), 2),
                'label' => trim((string) ($loop['label'] ?? '')),
                'contributed' => filter_var($loop['contributed'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ])
            ->filter(fn (array $loop) => $loop['end'] - $loop['start'] >= 0.2)
            ->map(fn (array $loop) => array_filter(
                [...$loop, 'label' => mb_substr($loop['label'] !== '' ? $loop['label'] : self::time($loop['start']) . '–' . self::time($loop['end']), 0, 60)],
                fn ($value) => $value !== false,
            ))
            ->sortBy('start')
            ->take(self::MAX)
            ->values()
            ->all();
    }

    private static function time(float $seconds): string
    {
        $whole = (int) floor($seconds);

        return intdiv($whole, 60) . ':' . str_pad((string) ($whole % 60), 2, '0', STR_PAD_LEFT);
    }
}
