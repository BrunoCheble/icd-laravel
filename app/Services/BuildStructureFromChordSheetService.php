<?php

namespace App\Services;

/**
 * Builds a chord map (structure) from chord sheet sections: one block per section and one map line per
 * chord sheet line, so the map reads like the song is sung (line breaks are stored as "|" among the chords).
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
     * Chords of ChordPro lines, in order, with a line break between lines ("{c: ...}" comments are ignored).
     */
    public static function chords(array $lines): array
    {
        $chords = [];

        foreach ($lines as $line) {
            if (self::isComment($line) || ! preg_match_all('/\[([^\]]+)\]/u', (string) $line, $matches)) {
                continue;
            }
            if ($chords !== []) {
                $chords[] = UpdateSongLayoutService::LINE_BREAK;
            }
            array_push($chords, ...$matches[1]);
        }

        return $chords;
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
