<?php

namespace App\Services;

class UpdateChordSheetChordsService
{
    /**
     * The chord sheet with its chords changed from `$old` to `$new` (both without line breaks), when the sheet has
     * exactly the `$old` chords; null otherwise. Lyrics stay where they are: a changed chord keeps its place, a
     * removed one is taken out of its line and an added one goes right after the chord before it (or before the
     * first one). Notes ("{c: ...}") are not touched.
     */
    public function execute(array $sections, array $old, array $new): ?array
    {
        if (self::sheetChords($sections) !== $old) {
            return null;
        }
        if ($old === $new) {
            return $sections;
        }

        [$replace, $after] = self::changes($old, $new);
        $number = 0;

        return array_map(function (array $section) use (&$number, $replace, $after) {
            $section['lines'] = array_map(function ($line) use (&$number, $replace, $after) {
                if (! is_string($line) || self::isNote($line)) {
                    return $line;
                }

                $edited = preg_replace_callback('/\[([^\]]+)\]/u', function () use (&$number, $replace, $after) {
                    $i = $number++;
                    $marks = fn (array $chords) => implode('', array_map(fn ($chord) => "[{$chord}]", $chords));
                    $before = $i === 0 ? $marks($after[-1] ?? []) : '';

                    return $before . (isset($replace[$i]) ? $marks($replace[$i]) : '') . $marks($after[$i] ?? []);
                }, $line);

                // A line with only chords keeps them one space apart.
                if (trim(preg_replace('/\[[^\]]*\]/u', '', $line)) === '') {
                    $edited = implode(' ', preg_match_all('/\[[^\]]+\]/u', $edited, $found) ? $found[0] : []);
                }

                return $edited;
            }, $section['lines'] ?? []);

            return $section;
        }, $sections);
    }

    /**
     * Chords of the sheet sections, in order, without line breaks (as the layout page counts them).
     */
    public static function sheetChords(array $sections): array
    {
        return UpdateSongLayoutService::chordSequence(array_map(
            fn ($section) => ['chords' => BuildStructureFromChordSheetService::chords($section['lines'] ?? [])],
            $sections,
        ));
    }

    /**
     * [chords that take the place of each old chord (none: removed), chords added after each old chord (-1: before
     * the first)], from the longest common subsequence of the two lists; removed and added chords next to each
     * other become changed chords.
     */
    private static function changes(array $old, array $new): array
    {
        $n = count($old);
        $m = count($new);
        $lcs = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
        for ($i = $n - 1; $i >= 0; $i--) {
            for ($j = $m - 1; $j >= 0; $j--) {
                $lcs[$i][$j] = $old[$i] === $new[$j] ? $lcs[$i + 1][$j + 1] + 1 : max($lcs[$i + 1][$j], $lcs[$i][$j + 1]);
            }
        }

        $replace = [];
        $after = [];
        $removed = [];
        $added = [];
        $previous = -1;
        $flush = function () use (&$removed, &$added, &$replace, &$after, &$previous) {
            foreach ($removed as $k => $i) {
                $replace[$i] = isset($added[$k]) ? [$added[$k]] : [];
                $previous = $i;
            }
            foreach (array_slice($added, count($removed)) as $chord) {
                $after[$previous][] = $chord;
            }
            $removed = [];
            $added = [];
        };

        $i = 0;
        $j = 0;
        while ($i < $n || $j < $m) {
            if ($i < $n && $j < $m && $old[$i] === $new[$j]) {
                $flush();
                $replace[$i] = [$old[$i]];
                $previous = $i;
                $i++;
                $j++;
            } elseif ($j >= $m || ($i < $n && $lcs[$i + 1][$j] >= $lcs[$i][$j + 1])) {
                $removed[] = $i++;
            } else {
                $added[] = $new[$j++];
            }
        }
        $flush();

        return [$replace, $after];
    }

    private static function isNote(string $line): bool
    {
        return str_starts_with(ltrim($line), '{');
    }
}
