<?php

namespace App\Services;

use App\Models\Song;

class UpdateSongSectionTimesService
{
    /**
     * Sets or removes the "start" of each chord map (structure) section (index => "m:ss" | seconds | null).
     * Nothing else in the structure is changed.
     */
    public function execute(Song $song, array $starts): Song
    {
        $structure = $song->structure;

        foreach ($structure as $index => $section) {
            if (! is_object($section)) {
                continue;
            }

            $seconds = self::toSeconds($starts[$index] ?? null);

            if ($seconds === null) {
                unset($section->start);
            } else {
                $section->start = self::format($seconds);
            }
        }

        $song->structure = $structure;
        $song->save();

        return $song;
    }

    /**
     * "1:05" / "0:01:05" / "65" / 65 -> 65; null when empty or invalid.
     */
    public static function toSeconds(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return $value >= 0 ? (float) $value : null;
        }

        $value = trim((string) $value);

        if (! preg_match('/^\d+(:\d{1,2}){0,2}(\.\d+)?$/', $value)) {
            return null;
        }

        return array_reduce(explode(':', $value), fn (float $total, string $part) => $total * 60 + (float) $part, 0.0);
    }

    /**
     * 65 -> "1:05"
     */
    public static function format(float $seconds): string
    {
        $total = (int) round($seconds);

        return intdiv($total, 60) . ':' . str_pad((string) ($total % 60), 2, '0', STR_PAD_LEFT);
    }
}
