<?php

namespace App\Services;

use App\Models\Song;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class UpdateChordSheetSectionService
{
    public function __construct(private SyncChordSheetWithStructureService $syncSheet)
    {
    }

    /**
     * Saves the lines of one section of the chord sheet as the layout page shows it (split like the map's blocks):
     * the lyrics and where the chords fall can change, and chords can be swapped, added or removed. In the map's bars
     * an added chord takes the second half of the chord before it, and a removed chord's beats go to the chord before
     * it, so the block keeps its length (see `rebuildChords`). When the sheet has one
     * section per block of the map, that block's lyrics and chords change too, so the sheet keeps the map's chords
     * and the study app and the map keep the same lyrics. With `$same`, the copies of the section (the other sections with the same
     * name and the same lines, e.g. every CHORUS written alike) change the same way; a different one (a longer last
     * chorus) is left as it is. Refused (409) when the song changed since the page read it.
     */
    public function execute(Song $song, int $index, array $lines, string $readAt, bool $same = false): Song
    {
        if (! $song->updated_at || ! $song->updated_at->equalTo(Carbon::parse($readAt))) {
            throw new ConflictHttpException(__('The song was changed meanwhile. Load it again before saving.'));
        }

        $stored = $song->chord_sheet['sections'] ?? [];
        $sections = $stored ? $this->syncSheet->execute($stored, $song->structure) : [];
        $section = $sections[$index] ?? null;
        if (! $section || ($section['jump'] ?? null) === 'start') {
            throw ValidationException::withMessages(['index' => __('This part of the chord sheet no longer exists. Load the page again.')]);
        }

        $lines = array_values(array_map(fn ($line) => rtrim(str_replace("\r", '', (string) $line)), $lines));
        while ($lines !== [] && trim(end($lines)) === '') {
            array_pop($lines);
        }
        $newChords = self::chords($lines);
        $chordsChanged = $newChords !== self::chords($section['lines'] ?? []);
        if ($chordsChanged && $newChords === []) {
            throw ValidationException::withMessages(['lines' => __('A part of the map needs at least one chord.')]);
        }

        $oldLyrics = self::lyrics($section['lines'] ?? []);
        $targets = $same ? self::copies($sections, $index) : [$index];
        foreach ($targets as $target) {
            $sections[$target]['lines'] = $lines;
        }

        // The blocks of these sections get the same lyrics (one section per block, in order).
        $structure = json_decode(json_encode($song->structure ?? []), true) ?? [];
        $timedSections = array_keys(array_filter($sections, fn ($item) => ($item['jump'] ?? null) !== 'start'));
        $timedBlocks = array_keys(array_filter($structure, fn ($block) => is_array($block) && ! Song::isReturnMarker($block)));
        if ($chordsChanged && count($timedSections) !== count($timedBlocks)) {
            throw ValidationException::withMessages(['lines' => __('The chords can only change here when the chord sheet follows the map block by block.')]);
        }
        if (count($timedSections) === count($timedBlocks)) {
            $newLyrics = self::lyrics($lines);
            foreach ($targets as $target) {
                $blockIndex = $timedBlocks[array_search($target, $timedSections, true)];
                $block = $structure[$blockIndex];
                // An anchor that was the first lyric line follows it.
                if (($block['anchor'] ?? null) === null || $block['anchor'] === ($oldLyrics[0] ?? null)) {
                    $block['anchor'] = $newLyrics[0] ?? null;
                }
                $block['lyrics'] = $newLyrics === [] ? null : implode("\n", $newLyrics);
                if ($chordsChanged) {
                    $block = count($newChords) === count(UpdateSongLayoutService::chordSequence([$block]))
                        ? array_merge($block, ['chords' => self::swapChords($block['chords'] ?? [], $newChords)])
                        : self::rebuildChords($block, $newChords);
                }
                $structure[$blockIndex] = $block;
            }
            $song->structure = $structure;
        }

        // Stored without the "back to the start" markers, which come from the map.
        $song->chord_sheet = array_merge($song->chord_sheet ?? [], [
            'sections' => array_values(array_filter($sections, fn ($item) => ($item['jump'] ?? null) !== 'start')),
        ]);
        $song->save();

        return $song;
    }

    /**
     * A block's chords with each one swapped, in order, for the new ones; its line breaks stay where they are.
     */
    private static function swapChords(array $chords, array $newChords): array
    {
        $next = 0;
        foreach ($chords as $i => $chord) {
            if ($chord !== UpdateSongLayoutService::LINE_BREAK) {
                $chords[$i] = $newChords[$next++] ?? $chord;
            }
        }

        return $chords;
    }

    /**
     * A block with other chords (some added or removed): each new chord is matched to an old one (same or swapped
     * chord) in order, keeping its beats; an added chord takes the second half of the chord before it (or the first
     * half of the next one when that one is too short), and a removed chord's beats go to the chord before it (the
     * next one at the start), so the block keeps its length. Line breaks and passing chords stay on the chords they
     * were on.
     */
    private static function rebuildChords(array $block, array $newChords): array
    {
        $old = [];
        $breakBefore = [];
        foreach ($block['chords'] ?? [] as $chord) {
            if ($chord === UpdateSongLayoutService::LINE_BREAK) {
                $breakBefore[count($old)] = $old !== [];
            } else {
                $old[] = $chord;
            }
        }
        $durations = is_array($block['durations'] ?? null) && count($block['durations']) === count($old)
            ? array_map('floatval', $block['durations']) : null;

        // Each entry: the chord, its beats, and the old chord it comes from (null: added).
        $result = [];
        $leftover = 0.0;
        foreach (self::align($old, $newChords) as [$i, $j]) {
            if ($j === null) {
                if ($result !== []) {
                    $result[count($result) - 1]['beats'] += $durations[$i] ?? 0;
                } else {
                    $leftover += $durations[$i] ?? 0;
                }
                continue;
            }
            $result[] = ['chord' => $newChords[$j], 'beats' => $i === null ? 0.0 : ($durations[$i] ?? 0) + $leftover, 'from' => $i];
            if ($i !== null) {
                $leftover = 0.0;
            }
        }
        if ($leftover > 0 && $result !== []) {
            $result[0]['beats'] += $leftover;
        }

        if ($durations !== null) {
            $step = UpdateSongLayoutService::DURATION_STEP;
            $total = array_sum(array_column($result, 'beats'));
            $halved = self::halveForAdded($result, $step);
            if ($halved !== null) {
                $result = $halved;
            } else {
                // Too many chords added in one place for halves: the block's beats shared by all its chords (in half
                // beats, the first ones a little longer), the block keeping its length; when it has fewer half beats
                // than chords (a chord sheet written doubled, say), it grows to the whole bars giving each chord a beat.
                $count = count($result);
                if ($total < $count * $step) {
                    $perBar = max(1, (int) ($block['beats_per_bar'] ?? 4));
                    $total = (float) (ceil($count / $perBar) * $perBar);
                }
                $each = floor($total / $count / $step) * $step;
                $extra = (int) round(($total - $each * $count) / $step);
                foreach ($result as $k => $entry) {
                    $result[$k]['beats'] = $each + ($k < $extra ? $step : 0);
                }
            }
        }

        $chords = [];
        $passing = [];
        $oldPassing = is_array($block['passing'] ?? null) ? $block['passing'] : [];
        foreach ($result as $k => $entry) {
            if ($entry['from'] !== null && ($breakBefore[$entry['from']] ?? false)) {
                $chords[] = UpdateSongLayoutService::LINE_BREAK;
            }
            $chords[] = $entry['chord'];
            if ($entry['from'] !== null && in_array($entry['from'], $oldPassing, true)) {
                $passing[] = $k;
            }
        }
        $block['chords'] = UpdateSongLayoutService::cleanBreaks($chords);
        if ($passing !== []) {
            $block['passing'] = $passing;
        } else {
            unset($block['passing']);
        }
        if ($durations !== null) {
            $block['durations'] = array_map(fn ($entry) => floor($entry['beats']) == $entry['beats'] ? (int) $entry['beats'] : $entry['beats'], $result);
        }

        return $block;
    }

    /**
     * Each added chord (no beats yet) takes half of a neighbour (the chord before, or the next one with room); null
     * when some added chord finds no room that way.
     */
    private static function halveForAdded(array $result, float $step): ?array
    {
        foreach ($result as $k => $entry) {
            if ($entry['from'] !== null || $entry['beats'] > 0) {
                continue;
            }
            $giver = null;
            foreach ([$k - 1, ...array_keys(array_slice($result, $k + 1, null, true))] as $candidate) {
                if (isset($result[$candidate]) && $result[$candidate]['beats'] >= 2 * $step) {
                    $giver = $candidate;
                    break;
                }
            }
            if ($giver === null) {
                return null;
            }
            $half = floor($result[$giver]['beats'] / 2 / $step) * $step;
            $result[$giver]['beats'] -= $half;
            $result[$k]['beats'] = $half;
        }

        return $result;
    }

    /**
     * How the old chords became the new ones (fewest changes): pairs [old index, new index], with null for a chord
     * removed (new index) or added (old index). A swapped chord keeps its pair.
     */
    private static function align(array $old, array $new): array
    {
        $n = count($old);
        $m = count($new);
        $cost = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
        for ($i = $n; $i >= 0; $i--) {
            for ($j = $m; $j >= 0; $j--) {
                if ($i === $n || $j === $m) {
                    $cost[$i][$j] = ($n - $i) + ($m - $j);
                    continue;
                }
                $cost[$i][$j] = min(
                    $cost[$i + 1][$j + 1] + ($old[$i] === $new[$j] ? 0 : 1),
                    $cost[$i + 1][$j] + 1,
                    $cost[$i][$j + 1] + 1,
                );
            }
        }

        $pairs = [];
        $i = 0;
        $j = 0;
        while ($i < $n || $j < $m) {
            if ($i < $n && $j < $m && $cost[$i][$j] === $cost[$i + 1][$j + 1] + ($old[$i] === $new[$j] ? 0 : 1)) {
                $pairs[] = [$i++, $j++];
            } elseif ($j < $m && ($i === $n || $cost[$i][$j] === $cost[$i][$j + 1] + 1)) {
                $pairs[] = [null, $j++];
            } else {
                $pairs[] = [$i++, null];
            }
        }

        return $pairs;
    }

    /**
     * The section and its copies: the other sections with the same name and exactly the same lines.
     */
    private static function copies(array $sections, int $index): array
    {
        $name = $sections[$index]['section'] ?? null;

        return array_keys(array_filter($sections, fn ($item, $i) => $i === $index || ($name !== null
            && ($item['jump'] ?? null) !== 'start'
            && ($item['section'] ?? null) === $name
            && ($item['lines'] ?? []) === ($sections[$index]['lines'] ?? [])), ARRAY_FILTER_USE_BOTH));
    }

    private static function chords(array $lines): array
    {
        return UpdateSongLayoutService::chordSequence([['chords' => BuildStructureFromChordSheetService::chords($lines)]]);
    }

    /**
     * Lyric lines of chord sheet lines: without chords and notes, lines with chords only left out.
     */
    private static function lyrics(array $lines): array
    {
        $lyrics = [];
        foreach ($lines as $line) {
            $line = (string) $line;
            if (str_starts_with(ltrim($line), '{')) {
                continue;
            }
            $text = trim(preg_replace('/\s+/u', ' ', preg_replace('/\[[^\]]+\]/u', '', $line)));
            if ($text !== '' && ! preg_match('/^[()|\s]+$/u', $text)) {
                $lyrics[] = $text;
            }
        }

        return $lyrics;
    }
}
