<?php

namespace App\Services;

/**
 * Builds an initial chord map (structure) from chord sheet sections, for songs that have no structure yet.
 */
class BuildStructureFromChordSheetService
{
    public function execute(array $sections): array
    {
        return collect($sections)
            ->values()
            ->map(function (array $section, int $index) {
                $lines = $section['lines'] ?? [];
                $lyrics = self::lyrics($lines);

                return [
                    'order'   => $index + 1,
                    'section' => $section['section'] ?? 'PART',
                    'anchor'  => $lyrics === '' ? null : strtok($lyrics, "\n"),
                    'start'   => $section['start'] ?? null,
                    'chords'  => self::chords($lines),
                    'lyrics'  => $lyrics === '' ? null : $lyrics,
                ];
            })
            ->all();
    }

    /**
     * Chords of ChordPro lines, in order ("{c: ...}" comments are ignored).
     */
    public static function chords(array $lines): array
    {
        $text = collect($lines)->reject(fn ($line) => self::isComment($line))->implode(' ');
        preg_match_all('/\[([^\]]+)\]/u', $text, $matches);

        return $matches[1];
    }

    /**
     * Lyrics of ChordPro lines: chords, comments and chord-only lines removed.
     */
    public static function lyrics(array $lines): string
    {
        return collect($lines)
            ->reject(fn ($line) => self::isComment($line))
            ->map(fn ($line) => trim(preg_replace('/\s+/u', ' ', preg_replace('/\[[^\]]*\]/u', '', (string) $line))))
            ->reject(fn ($line) => $line === '' || preg_match('/^[()|\s]*$/u', $line))
            ->implode("\n");
    }

    private static function isComment(mixed $line): bool
    {
        return str_starts_with(trim((string) $line), '{');
    }
}
