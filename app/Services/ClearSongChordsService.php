<?php

namespace App\Services;

use App\Models\Song;
use Illuminate\Validation\ValidationException;

class ClearSongChordsService
{
    // A chord not known yet (to be written by ear).
    public const UNKNOWN = '?';

    public function __construct(private SyncChordSheetWithStructureService $sync)
    {
    }

    /**
     * Takes the chords out to write them again by ear, keeping the blocks and their bars: every bar gets a "?" in the
     * map (one per bar, so each one can be written on its own), and the chord sheet gets the same "?"s, spread over the
     * lyrics of each block (its chords taken out). Map and chord sheet keep the same blocks with the same number of
     * chords, so making the bars again from the chord sheet gives the same blocks and bars. The version before stays
     * in the song's history.
     */
    public function execute(Song $song): Song
    {
        if (! $song->hasBars()) {
            throw ValidationException::withMessages(['song' => __('This song has no bars yet.')]);
        }

        $structure = json_decode(json_encode($song->structure ?? []), true) ?? [];
        // The chord sheet split like the blocks (when it follows the map), to give each block its own lines.
        $sheet = $song->chord_sheet['sections'] ?? [];
        $split = $sheet ? array_values(array_filter($this->sync->execute($sheet, $song->structure), fn ($section) => ($section['jump'] ?? null) !== 'start')) : [];
        $timed = array_keys(array_filter($structure, fn ($block) => is_array($block) && ! Song::isReturnMarker($block)));
        $followsMap = count($split) === count($timed);

        // Beats from the start of bar 1, so each piece ends on a bar line.
        $position = 0.0;
        $perBar = 4;
        $sections = [];
        foreach ($timed as $n => $index) {
            $block = $structure[$index];
            $perBar = (int) ($block['beats_per_bar'] ?? $perBar) ?: 4;
            if ($n === 0) {
                $position = (float) ($block['lead_beats'] ?? 0);
            }
            $beats = array_sum(array_map('floatval', $block['durations'] ?? []));
            $pieces = [];
            while ($beats > 0) {
                $piece = min($perBar - fmod($position, $perBar), $beats);
                $pieces[] = floor($piece) == $piece ? (int) $piece : $piece;
                $position += $piece;
                $beats -= $piece;
            }
            unset($block['passing']);
            $block['chords'] = array_fill(0, count($pieces), self::UNKNOWN);
            $block['durations'] = $pieces;
            $structure[$index] = $block;

            $source = $followsMap ? $split[$n] : null;
            $lines = $source ? $source['lines'] ?? [] : preg_split('/\R/u', (string) ($block['lyrics'] ?? ''));
            $sections[] = [
                'section' => $source['section'] ?? strtoupper((string) ($block['section'] ?? 'PART')),
                'label'   => $source['label'] ?? null,
                'lines'   => self::withMarks(self::lyricsOnly($lines), count($pieces)),
            ];
        }

        $song->structure = $structure;
        $song->chord_sheet = array_merge($song->chord_sheet ?? [], ['sections' => $sections]);
        $song->save();

        return $song;
    }

    /**
     * Chord sheet lines without their chords; lines that only had chords go away (notes like "{c: Intro}" stay).
     */
    private static function lyricsOnly(array $lines): array
    {
        $result = [];
        foreach ($lines as $line) {
            $line = (string) $line;
            if (str_starts_with(ltrim($line), '{')) {
                $result[] = $line;
                continue;
            }
            $text = rtrim(preg_replace('/\[[^\]]*\]/u', '', $line));
            if (trim($text) !== '') {
                $result[] = $text;
            }
        }

        return $result;
    }

    /**
     * `$count` "?"s over the lyric lines, in order: the lines share them evenly and each line spreads its own over its
     * letters (to be dragged to the right syllable). Without lyrics, a line of "?"s.
     */
    private static function withMarks(array $lines, int $count): array
    {
        $lyric = array_keys(array_filter($lines, fn ($line) => ! str_starts_with(ltrim((string) $line), '{')));
        if ($count === 0) {
            return $lines;
        }
        if ($lyric === []) {
            $lines[] = trim(str_repeat('[' . self::UNKNOWN . '] ', $count));

            return $lines;
        }
        $perLine = array_fill_keys($lyric, 0);
        for ($k = 0; $k < $count; $k++) {
            $perLine[$lyric[intdiv($k * count($lyric), $count)]]++;
        }
        foreach ($perLine as $index => $marks) {
            if ($marks === 0) {
                continue;
            }
            $text = (string) $lines[$index];
            $length = mb_strlen($text);
            $result = '';
            $from = 0;
            for ($j = 0; $j < $marks; $j++) {
                $at = min($length, intdiv($j * $length, $marks));
                // On the start of a word (where a chord is dragged from), never before the previous mark.
                while ($at > 0 && $at < $length && mb_substr($text, $at - 1, 1) !== ' ') {
                    $at++;
                }
                $at = max($at, $j === 0 ? 0 : $from + 1);
                $at = min($at, $length);
                $result .= mb_substr($text, $from, $at - $from) . '[' . self::UNKNOWN . ']';
                $from = $at;
            }
            $lines[$index] = $result . mb_substr($text, $from);
        }

        return $lines;
    }
}
